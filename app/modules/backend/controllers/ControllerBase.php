<?php
/**
 * Created by PhpStorm.
 * User: ZHU
 * Date: 20/12/2018
 * Time: 17:37
 */

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use Phalcon\Di;
use Phalcon\Mvc\Controller;
use SeetaAiBuildingCommunity\Common\Manager\FileManager;
use SeetaAiBuildingCommunity\Common\Manager\RsaManager;
use SeetaAiBuildingCommunity\Common\Manager\SessionManager;
use SeetaAiBuildingCommunity\Common\Manager\Utility;

class ControllerBase extends Controller
{
    /**
     * 从session中获取用户名
     *
     * @param string $session_id 会话ID
     * @return string
     * */
    public function getUserIdBySession($session_id)
    {
        $platformType = $this->request->getHeader("x-request-source-type") ?: PLATFORM_TYPE_WEB;
        $session_manager = new SessionManager($platformType);
        return $session_manager->getSession($session_id, SessionManager::FIELD_NAME_USER_ID);
    }

    /**
     * 从session中获取用户类型
     *
     * @param string $session_id 会话ID
     * @return string
     * @throws \Exception
     * */
    public function getUserTypeBySession($session_id)
    {
        $platformType = $this->request->getHeader("x-request-source-type") ?: PLATFORM_TYPE_WEB;
        $session_manager = new SessionManager($platformType);
        return $session_manager->getSession($session_id, SessionManager::FIELD_NAME_USER_TYPE);
    }

    /**
     * 返回数据
     *
     * @param array $resArray
     * @return string
     * */
    public function getResponse($resArray)
    {
        // Getting a response instance
        $response = $this->response;

        $response->setHeader("Access-Control-Allow-Origin", "*");
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS');

        $response->setHeader("Access-Control-Allow-Headers", "x-requested-with, content-type, x-request-source-type");

        $resArray['timestamp'] = time();
        $feed = json_encode($resArray, JSON_UNESCAPED_UNICODE);
        // Set the content of the response
        $response->setContent($feed);

        // Return the response
        return $response;
    }


    /**
     * 以错误码，返回错误信息
     *
     * @param integer $code
     * @return array
     */
    public function makeErrorResponse($code)
    {
        return array(
            MESSAGE => ERROR_MSGS[$code],
            CODE => $code
        );
    }

    /**
     * 生成文件路径名，并HTTP上传到本地
     *
     * @param string $dir
     * @param string $upload_file 文件字段名
     * @param string $store_file_name 另存为的文件名
     * @return string
     * @throws
     */
    public function uploadFile($dir, $upload_file, &$store_file_name)
    {
        $file_path = null;


        if (!$this->request->hasFiles()) {
            return null;
        }

        //文件上传
        foreach ($this->request->getUploadedFiles() as $file) {
            if ($file->getKey() == $upload_file) {

                $file_extension = $file->getExtension();

                //验证文件大小
                if ($file->getSize() > FILE_SIZE_MAX) {
                    throw new \Exception("上传文件过大，文件大小：" . $file->getSize(), ERR_FILE_SIZE_TOO_LARGE);
                }

                //生成文件名
                if (empty($store_file_name)) {
                    $store_file_name = $file->getName();
                } else {
                    $store_file_name = $store_file_name . '.' . $file_extension;
                }

                $file_path = FileManager::storeFile($dir, $file, $store_file_name);
                break;
            }
        }

        return $file_path;
    }

    /**
     * 接口安全性检验函数
     *
     * @param string $sessionId 会话ID
     * @param string $privateKey 私钥引用传递
     * @param bool $refreshSession 是否刷新会话
     * @return integer 错误码
     * */
    public function actionChecker($sessionId, &$userType, &$privateKey, $refreshSession = true)
    {
        $platformType = $this->request->getHeader("x-request-source-type") ?: PLATFORM_TYPE_WEB;
        if (Di::getDefault()->get('config')->mode == SERVER_MODE_DEBUG) {
            return ERR_SUCCESS;
        }

        //获取密钥
        $rsa_manager = new RsaManager($platformType);
        try {
            $privateKey = $rsa_manager->getPrivateKeyBySessionId($sessionId);
        } catch (\Exception $exception) {
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return ERR_CACHE_WRONG;
        }

        if ($privateKey == null) {
            return ERR_USER_SESSION_EXPIRED;
        } else if ($refreshSession) {
            //刷新session
            Utility::refreshSession($sessionId, $platformType);
        }

        //获取所有POST参数
        $data = $this->request->getPost();

        //检查签名
//        return Utility::checkSignature($privateKey, $data, $this->request->getClientAddress(), $platformType);
    }
}