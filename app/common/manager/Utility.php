<?php
namespace SeetaAiBuildingCommunity\Common\Manager;

use Phalcon\Di;
use Phalcon\Text;
use function Psy\debug;

class Utility
{
    /**
     * 生成32字符的md5 登录token
     * @param string $user_id
     * @param string $password
     * @return string
     */
    static public function makeAccessToken($user_id, $password)
    {
        $time = time();
        $strVal = $time . $user_id . $password;
        return md5($strVal);
    }


    /**
     * 生成32字符的UUID
     * @param string $prefix
     * @return string
     */
    static public function create_uuid($prefix = ""){
        static $guid = '';
        $uid = uniqid("", true);
        $data = $prefix;
        $data .= $_SERVER['REQUEST_TIME'];
        $data .= $_SERVER['HTTP_USER_AGENT'];
        $data .= $_SERVER['SERVER_ADDR'];
        $data .= $_SERVER['SERVER_PORT'];
        $data .= $_SERVER['REMOTE_ADDR'];
        $data .= $_SERVER['REMOTE_PORT'];
        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($data)));
        $guid = str_replace('-','',$hash);
        return $guid;
    }

    /**
     * 读取固定格式文字message，将数组内文字，插入message中的<key_name>等占位符处
     * @param string $message
     * @param array $textToInsert
     * @return string
     * */
    static public function makeText($message, $textToInsert){
        foreach ($textToInsert as $key => $text){
            $message = str_replace("<$key>", $text, $message);
        }
        return $message;
    }

    /**
     * 提前结束fastcgi的调用并在原有线程上继续执行后面步骤
     * @param $resArray
     */
    static public function responseEarly($resArray)
    {
        // 清空缓冲区
        ob_clean();
        // 设置响应头信息
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST,OPTIONS,GET');
        header('Access-Control-Allow-Headers: x-requested-with,content-type');
        // 输出内容(如果前端需要json格式还需要加一个json类型的响应头)
        $resArray['timestamp'] = time() * 1000;
        $feed = json_encode($resArray, JSON_UNESCAPED_UNICODE);
        echo $feed;
        // 结束并输出缓冲区内容
        ob_end_flush();
        // 关闭fastcgi的调用
        fastcgi_finish_request();
        // 设置客户端断开连接时是否中断脚本的执行
        ignore_user_abort(true);
        // 设置脚本最大执行时间(超时会中断脚本运行,0为无时间限制)
        set_time_limit(7200);
    }

    /**
     * 检查签名
     *
     * @param string $private_key
     * @param array $data
     * @param string $ip
     * @param integer $platformType
     * @return integer
     * @throws
     */
    static public function checkSignature($private_key, $data, $ip, $platformType){
        $signature = $data['signature'];
        switch ($platformType) {
            case PLATFORM_TYPE_WEB:
                $hash2 = RsaManager::decrypt(base64_decode($signature), $private_key);
                break;
            case PLATFORM_TYPE_WECHAT:
                $hash2 = RsaManager::decryptPkcs1(base64_decode($signature), $private_key);
                break;
            case PLATFORM_TYPE_ANDROID:
                $hash2 = RsaManager::decryptPkcs1(base64_decode($signature), $private_key);
                break;
            default:
                $hash2 = RsaManager::decrypt(base64_decode($signature), $private_key);
                break;
        }


        $timestamp = $data['timestamp'];
        unset($data['signature']);
        unset($data['timestamp']);
        $session_manager = new SessionManager($platformType);

        if ($platformType != PLATFORM_TYPE_ANDROID){
            //进行升序排序
            if($platformType == PLATFORM_TYPE_WECHAT && (!empty($data))) {
                ksort($data);
            }
            $session_id = $data['session_id'];
            unset($data['session_id']);
            // 获取 Token
            $token = $session_manager->getSession($session_id, SessionManager::FIELD_NAME_TOKEN);

            //检查签名
            $string = json_encode([
                'access_token' => $token,
                'data' => empty($data) ? null : $data,
                'session_id' => $session_id,
                'timestamp' => (int)$timestamp,
            ], JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
        }
        else{
            $serial_code = $data['serial_code'];
            unset($data['serial_code']);
            // 获取 Token
            $token = $session_manager->getSession($serial_code, SessionManager::FIELD_NAME_TOKEN);

            //检查签名
            $string = json_encode([
                'access_token' => $token,
                'data' => empty($data) ? null : $data,
                'serial_code' => $serial_code,
                'timestamp' => (int) $timestamp,
            ], JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
        }

        $hash1 = bin2hex(hash('sha256', $string, true));

        //比对签名哈希值,时间戳
        if($hash1 != $hash2){
            //签名有误
            $msg = $ip."签名错误" ;
            self::log('secure_logger', $msg,__METHOD__,__LINE__);
            return ERR_SIGNATURE_WRONG;
        }
        else if((time() - (int) $timestamp) > 200){
            //或时间戳有误
            $msg = $ip."签名错误" ;
            self::log('secure_logger', $msg,__METHOD__,__LINE__);
            return ERR_REQUEST_TIMESTAMP_WRONG;
        }
        else{
            return ERR_SUCCESS;
        }
    }

    /**
     * 日志写入函数
     *
     * @param string $loggerName
     * @param string $message
     * @param string $action 错误发生的函数名
     * @param string $line 错误发生的行数
     * */
    static public function log($loggerName, $message, $action = "", $line = 0){
        Di::getDefault()->get($loggerName)->warning($message, ['location' => $action.':'.$line]);
    }

    /**
     * 刷新session存活时间
     * @param string $session_id
     * @param integer $platformType
     * @throws
     * */
    static public function refreshSession($session_id, $platformType = PLATFORM_TYPE_WEB){
        $session_manager = new SessionManager($platformType);
        $session_manager->refreshSession($session_id);

        $rsa_manager = new RsaManager($platformType);
        $rsa_manager->refreshRsaKey($session_id);

    }

    /**
     * 产生随机码
     *
     * @param integer $charNum
     * @param bool $noAlpha 纯数字
     * @return string
     * */
    static public function generateRandomCode($charNum, $noAlpha = false){
        if ($noAlpha){
            return Text::random(Text::RANDOM_NUMERIC, $charNum);
        }else{
            return Text::random(Text::RANDOM_ALNUM, $charNum);
        }
    }

    /**
     * 图片路径转换成URL
     *
     * @param string $server_url
     * @param string $path
     * @return string
     *
     * */
    static public function imagePathToDownloadUrl($server_url, $path){
        $key = self::create_uuid($path);
        $manager = new ImageManager();
        $manager->setImageKey($key, $path);

        return $server_url.IMAGE_DOWNLOAD_URL.$key;
    }

    /**
     * 文件路径转换成URL
     *
     * @param string $server_url
     * @param string $path
     * @return string
     *
     * */
    static public function filePathToDownloadUrl($server_url, $path){
        $key = self::create_uuid($path);
        $manager = new FileManager();
        $manager->setFileKey($key, $path);

        return $server_url.File_DOWNLOAD_URL.$key;
    }

    /**发送post请求
     * @param string $url
     * @param array $data
     * @return array|bool|false|string
     */
    static public function sendUrlPost($url = null, $data = null)
    {
        try {
            $ci = curl_init(); // 启动一个CURL会话
            curl_setopt($ci, CURLOPT_URL, $url); // 要访问的地址
            curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ci, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
            curl_setopt($ci, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
            curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
            curl_setopt($ci, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
            curl_setopt($ci, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
            curl_setopt($ci, CURLOPT_POST, 1); // 发送一个常规的Post请求
            curl_setopt($ci, CURLOPT_POSTFIELDS, json_encode($data));// 执行操作
            $output = curl_exec($ci); // 执行操作
            curl_close($ci); // 关闭CURL会话
        } catch (\Exception $e) {
            $output = [];
            $output['res'] = ERR_CODE_UNKNOW;
            $output['message'] = '未知错误';
            $output = json_encode($output);
        }
        return $output;
    }

    /**发送get请求
     * @param string $url
     * @return array|bool|false|string
     */
    static public function sendUrlGet($url = null)
    {
        try {
            $ci = curl_init(); // 启动一个CURL会话
            curl_setopt($ci, CURLOPT_URL, $url); // 要访问的地址
            curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ci, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
            $output = curl_exec($ci); // 执行操作
            curl_close($ci); // 关闭CURL会话
        } catch (\Exception $e) {
            $output = [];
            $output['res'] = ERR_CODE_UNKNOW;
            $output['message'] = '未知错误';
            $output = json_encode($output);
        }
        return $output;
    }
}