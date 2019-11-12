<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use MongoDB\BSON\ObjectId;
use Phalcon\Di;
use SeetaAiBuildingCommunity\Common\Manager\MqttManager;
use SeetaAiBuildingCommunity\Common\Manager\RsaManager;
use SeetaAiBuildingCommunity\Common\Manager\SessionManager;
use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Common\Manager\VerifyCodeManager;
use SeetaAiBuildingCommunity\Models\TAdmin;

class AccountController extends ControllerBase
{
    /*
     * 预登录，与前端交互，换取密钥
     * */
    public function preLoginAction()
    {
        $account = $this->request->getPost("account");
        $publicKeyFrontend = $this->request->getPost("public_key");
        $verifyCode = $this->request->getPost("verify_code");
        $codeTag = $this->request->getPost("code_tag");

        if (empty($account) || empty($publicKeyFrontend) || empty($verifyCode) || empty($codeTag)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //检查图片验证码
        $verifyCodeManager = new VerifyCodeManager();
        $result = $verifyCodeManager->checkCode($codeTag, $verifyCode);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        try {
            $user = TAdmin::findByUsername($account);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //用户不存在
        if (empty($user)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PASSWORD_NOT_MATCHED));
        }

        $rsaManager = new RsaManager();

        $sessionId = Utility::create_uuid();

        try {
            $publicKey = $rsaManager->createRsaKeyPairs($sessionId);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_CACHE_WRONG));
        }

        //这里使用OAEP的加密方式，而非NONE， 因为JS前端的诉求
        $publicKey = RsaManager::encryptOaep($publicKey, $publicKeyFrontend);
        if (!$publicKey) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "public_key" => base64_encode($publicKey),
            "session_id" => $sessionId,
        ));

    }

    /*
     * 密码登录
     * */
    public function loginAction()
    {
        $pwd = $this->request->getPost("password");
        $sessionId = $this->request->getPost("session_id");
        $account = $this->request->getPost("account");

        if (empty($account) || empty($pwd) || empty($sessionId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //获取密钥
        $rsaManager = new RsaManager();
        try {
            $privateKey = $rsaManager->getPrePrivateKeyBySessionId($sessionId);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_CACHE_WRONG));
        }
        if (empty($privateKey)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_USER_SESSION_EXPIRED));
        }

        try {
            $user = TAdmin::findByUsername($account);
            $userId = (string)$user->_id;
            $userType = USER_TYPE_ADMIN;
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //用户不存在
        if (empty($user)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PASSWORD_NOT_MATCHED));
        }

        //对密码，进行rsa解密
        $pwd = RsaManager::decrypt(base64_decode($pwd), $privateKey);
        if (empty($pwd)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }


        //密码错误
        if ($user->password != bin2hex(hash('sha256', $pwd, true))) {
            //删除RSA密钥
            $rsaManager->clearKeys($sessionId);
            return parent::getResponse(parent::makeErrorResponse(ERR_PASSWORD_NOT_MATCHED));
        } else if ($user->status != ADMIN_STATUS_VALID) {
            //账户未生效
            return parent::getResponse(parent::makeErrorResponse(ERR_PASSWORD_NOT_MATCHED));
        }


        try {

            $sessionManager = new SessionManager();

            // 登录本地redis记录token
            $accessToken = $sessionManager->getSession($sessionId, SessionManager::FIELD_NAME_TOKEN);
            if (empty($accessToken)) {
                $accessToken = Utility::makeAccessToken($sessionId, $user->username);
                $sessionManager->setSession($sessionId, SessionManager::FIELD_NAME_TOKEN, $accessToken);
            }

            //rsa加密
            $accessToken = RsaManager::encrypt($accessToken, $privateKey);
            $accessToken = base64_encode($accessToken);

            // 注册session使用者
            // $userIdInCache = parent::getUserIdBySession($sessionId);
            $userIdInCache = $sessionManager->getSession($sessionId, SessionManager::FIELD_NAME_USER_ID);

            if (empty($userIdInCache)) {
                $sessionManager->setSession($sessionId, SessionManager::FIELD_NAME_USER_ID, $userId);
            }

            // 注册session使用者用户类型
            $userTypeTnCache = $sessionManager->getSession($sessionId, SessionManager::FIELD_NAME_USER_TYPE);
            if (empty($userTypeTnCache)) {
                $sessionManager->setSession($sessionId, SessionManager::FIELD_NAME_USER_TYPE, $userType);
            }

            // 检测是否有其他人登陆此账号
            $sessionIdInCache = $sessionManager->getSessionIdByUserId($userId);

            if (empty($sessionIdInCache)) {
                $sessionManager->setSessionIdByUserId($userId, $sessionId);
            } else {
                //假如存在，给前者推送被挤下线消息，删除前者session
                $msg = [
                    "command" => "repeat_login",
                    "content" => [
                        "time" => 1000 * time(),
                    ]
                ];

                //推送消息到指定topic
                $mqtt = new MqttManager();
                $topic_id = "admin:topic_id:" . $userId;
                $mqtt->sendMsg($topic_id, json_encode($msg, JSON_UNESCAPED_UNICODE));


                $rsaManager->clearKeys($sessionIdInCache);
                $sessionManager->deleteSession($sessionIdInCache);
                $sessionManager->setSessionIdByUserId($userId, $sessionId);
            }

            //成功登陆，刷新rsa密钥存活时间
            $rsaManager->refreshRsaKey($sessionId);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_CACHE_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "access_token" => $accessToken,
            "username" => $user->username,
            "topic_id" => $userId,
            "user_type" => $userType,
        ));
    }

    /*
     * 登出
     * */
    public function logoutAction()
    {
        $sessionId = $this->request->getPost("session_id");

        if (empty($sessionId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        $sessionManager = new SessionManager();

        try {
            //删除session
            $sessionManager->deleteSession($sessionId);

            //清除密钥
            $rsaManager = new RsaManager();
            $rsaManager->clearKeys($sessionId);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_CACHE_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS));

    }

    /*
     * 生成图片验证码
     * */
    public function generateVerifyCodeAction()
    {
        $codeTag = $this->request->get("code_tag");

        if (empty($codeTag)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        $code = Utility::generateRandomCode(4);

        try {
            $verifyCodeManager = new VerifyCodeManager();
            $verifyCodeManager->addCode($codeTag, $code);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_CACHE_WRONG));
        }

        $res = VerifyCodeManager::getCaptcha($code);
        header("Content-Type:image/png");
        imagepng($res);
        imagedestroy($res);
        exit;
    }

    /*
     * 通过原密码修改密码
     * */
    public function resetPasswordAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $pwd = $this->request->getPost("password");
        $pwdNew = $this->request->getPost("password_new");

        if (empty($sessionId) || empty($pwd) || empty($pwdNew)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }


        //获取用户id
        $userId = parent::getUserIdBySession($sessionId);

        $pwd = RsaManager::decrypt(base64_decode($pwd), $privateKey);
        $pwdNew = RsaManager::decrypt(base64_decode($pwdNew), $privateKey);
        if ($pwd == false || $pwdNew == false) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //获取用户
        try {
            $user = TAdmin::findById(new ObjectId($userId));
            if (empty($user)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_ADMIN_NOT_EXIST));
            }
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        if ($user->password != bin2hex(hash('sha256', $pwd, true))) {
            //密码错误
            return parent::getResponse(parent::makeErrorResponse(ERR_ORIGINAL_PASSWORD_WRONG));
        }

        $user->password = bin2hex(hash('sha256', $pwdNew, true));

        try {
            $user->save();
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }

    /**
     *  获取mqtt服务地址
     * @return string
     */
    public function getMqttInfoAction()
    {

        $sessionId = $this->request->getPost("session_id");
        if (empty($sessionId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }
        $config = Di::getDefault()->get('config')->mqtt;
        $mqtt_url = 'ws://' . $config->host . ':' . $config->webPort . '/mqtt';

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "mqtt_url" => base64_encode($mqtt_url),
            "mqtt_user" => base64_encode($config->user),
            "mqtt_pwd" => base64_encode($config->passwd)
        ));

    }

}