<?php

namespace SeetaAiBuildingCommunity\Common\Manager;

use Phalcon\Di;
use phpseclib\Crypt\RSA;

class RsaManager extends RedisManager
{
    const DEF_INFO_PRE_RSA = [
        self::DEF_INFO_PREFIX => 'rsa_pre:',
        self::DEF_INFO_LIFETIME => 120,
        self::DEF_INFO_IS_HASH => true,
    ];

    protected $defInfoWeb = [
        self::DEF_INFO_PREFIX => 'rsa_short:',
        self::DEF_INFO_LIFETIME => 7200,
        self::DEF_INFO_IS_HASH => true,
    ];

    protected $defInfoMobile = [
        self::DEF_INFO_PREFIX => 'rsa_long:',
        self::DEF_INFO_LIFETIME => NULL,
        self::DEF_INFO_IS_HASH => true,
    ];

    protected $platformType;
    protected $def_info;

    const FIELD_NAME_PRIVATE_KEY = 'private_key';
    const FIELD_NAME_PUBLIC_KEY = 'public_key';

    public function __construct($platformType = PLATFORM_TYPE_WEB)
    {
        parent::__construct(Di::getDefault()->get('redis'));
        $this->platformType = $platformType;
        if ((int) $platformType == PLATFORM_TYPE_WEB)
        {
            $this->def_info = $this->defInfoWeb;
        }
        else{
            $this->def_info = $this->defInfoMobile;
        }
    }

    /**
     * 创建rsa公钥和私钥，并以session_id为键名，存入redis，同时返回公钥
     *
     * @param string $session_id
     * @throws \Exception
     * @return string $public_key
     * */
    public function createRsaKeyPairs($session_id)
    {
        $rsa = new RSA();
        $result = $rsa->createKey();

        $private_key = preg_replace('/\\\/i','',preg_replace('/\r\n/','',$result["privatekey"]));
        $public_key = preg_replace('/\\\/i','',preg_replace('/\r\n/','',$result["publickey"]));

        $this->setHashCache(self::DEF_INFO_PRE_RSA, $session_id, self::FIELD_NAME_PRIVATE_KEY, $private_key);
        $this->setHashCache(self::DEF_INFO_PRE_RSA, $session_id, self::FIELD_NAME_PUBLIC_KEY, $public_key);

        return $public_key;
    }

    /**
     * 创建rsa公钥和私钥，并以session_id为键名，存入redis，同时返回公钥
     *
     * @param string $session_id
     * @throws \Exception
     * @return string $public_key
     * */
    public function createPkcs1RsaKeyPairs($session_id)
    {
        $rsa = new RSA();
        $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_PKCS1);
        $rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_PKCS1);
        $result = $rsa->createKey();

        $private_key = preg_replace('/\\\/i','',preg_replace('/\r\n/','',$result["privatekey"]));
        $public_key = preg_replace('/\\\/i','',preg_replace('/\r\n/','',$result["publickey"]));

        $this->setHashCache(self::DEF_INFO_PRE_RSA, $session_id, self::FIELD_NAME_PRIVATE_KEY, $private_key);
        $this->setHashCache(self::DEF_INFO_PRE_RSA, $session_id, self::FIELD_NAME_PUBLIC_KEY, $public_key);

        return $public_key;
    }

    /**
     * 创建rsa公钥和私钥
     *
     * @return array $result
     * */
    public function createRsaKeyPairsWithoutSession()
    {
        $rsa = new RSA();
        $result = $rsa->createKey();

        $private_key = preg_replace('/\\\/i','',preg_replace('/\r\n/','',$result["privatekey"]));
        $public_key = preg_replace('/\\\/i','',preg_replace('/\r\n/','',$result["publickey"]));

        return [
            'public_key' => $public_key,
            'private_key' => $private_key,
        ];
    }

    /**
     * 以session_id刷新rsa密钥存活时间
     *
     * @param string $session_id
     * @throws \Exception
     * @return boolean
     * */
    public function refreshRsaKey($session_id)
    {
        //获取预登陆时创建的密钥对
        $private_key = $this->getHashCache(self::DEF_INFO_PRE_RSA, $session_id, self::FIELD_NAME_PRIVATE_KEY);
        $public_key = $this->getHashCache(self::DEF_INFO_PRE_RSA, $session_id, self::FIELD_NAME_PUBLIC_KEY);

        if(isset($private_key) && isset($public_key)){
            //删除预登陆密钥对
            $this->delCache(self::DEF_INFO_PRE_RSA, $session_id);

            //重新插入正式密钥对
            $this->setHashCache($this->def_info, $session_id, self::FIELD_NAME_PRIVATE_KEY, $private_key);
            $this->setHashCache($this->def_info, $session_id, self::FIELD_NAME_PUBLIC_KEY, $public_key);
        }
        else{
            //假如不存在预登陆密钥对，则刷新现存的正式密钥对
            $this->refreshCache($this->def_info, $session_id);
        }
        return true;
    }


    /**
     * 以session_id返回预登陆生成的私钥
     *
     * @param string $session_id
     * @throws \Exception
     * @return string $private_key
     * */
    public function getPrePrivateKeyBySessionId($session_id){
        return $this->getHashCache(self::DEF_INFO_PRE_RSA, $session_id, self::FIELD_NAME_PRIVATE_KEY);
    }

    /**
     * 以session_id返回私钥
     *
     * @param string $session_id
     * @throws \Exception
     * @return string $private_key
     * */
    public function getPrivateKeyBySessionId($session_id){
        return $this->getHashCache($this->def_info, $session_id, self::FIELD_NAME_PRIVATE_KEY);
    }


    /**
     * 清除所有密钥
     * @param string $session_id
     * @throws \Exception
     */
    public function clearKeys($session_id)
    {
        //删除预登陆密钥对
        $this->delCache(self::DEF_INFO_PRE_RSA, $session_id);

        //删除正式密钥对
        $this->delCache($this->def_info, $session_id);
        $this->delCache($this->def_info, $session_id);
    }

    /**
 * rsa加密
 *
 * @param string $msg
 * @param string $key
 * @return string
 * */
    public static function encryptOaep($msg, $key)
    {
        $rsa = new RSA();
        $rsa->loadKey($key);

        $rsa->setEncryptionMode(RSA::ENCRYPTION_OAEP);

        return $rsa->encrypt($msg);
    }

    /**
     * rsa解密
     *
     * @param string $msg
     * @param string $key
     * @return string
     * */
    public static function decryptOaep($msg, $key)
    {
        $rsa = new RSA();
        $rsa->loadKey($key);

        $rsa->setEncryptionMode(RSA::ENCRYPTION_OAEP);

        return $rsa->decrypt($msg);
    }

    /**
     * rsa加密
     *
     * @param string $msg
     * @param string $key
     * @return string
     * */
    public static function encrypt($msg, $key)
    {
        $rsa = new RSA();
        $rsa->loadKey($key);

        $rsa->setEncryptionMode(RSA::ENCRYPTION_NONE);

        return $rsa->encrypt($msg);
    }

    /**
     * rsa加密 PKCS1
     *
     * @param string $msg
     * @param string $key
     * @return string
     * */
    public static function encryptPkcs1($msg, $key)
    {
        $rsa = new RSA();
        $rsa->loadKey($key);

        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return $rsa->encrypt($msg);
    }

    /**
     * rsa解密 PKCS1
     *
     * @param string $msg
     * @param string $key
     * @return string
     * */
    public static function decryptPkcs1($msg, $key)
    {
        $rsa = new RSA();
        $rsa->loadKey($key);

        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return $rsa->decrypt($msg);
    }

    /**
     * rsa解密
     *
     * @param string $msg
     * @param string $key
     * @return string
     * */
    public static function decrypt($msg, $key)
    {
        $rsa = new RSA();
        $rsa->loadKey($key);

        //$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return $rsa->decrypt($msg);
    }

}