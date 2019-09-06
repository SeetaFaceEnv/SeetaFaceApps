<?php

namespace SeetaAiBuildingCommunity\Common\Manager;

use Phalcon\Di;

class GatewayManager extends RedisManager
{
    protected $gateway_token;
    protected $user;

    const FIELD_NAME_GWTOKEN = 'gw_token';
    const FIELD_NAME_CLIENT_ID = 'client_id';   //客户端连接gateway时获得的id
    const FIELD_NAME_USER_ID = 'user_id';

    public function __construct()
    {
        parent::__construct(Di::getDefault()->get('redis'));
        $this->gateway_token = [
            self::DEF_INFO_PREFIX => 'gw_token:',
            self::DEF_INFO_LIFETIME => 120,
            self::DEF_INFO_IS_HASH => true,
        ];
    }

    /**
     * 创建gatewayToken
     * @throws \Exception
     * @return string
     * */
    public function createGWToken(){
        $gwToken = Utility::generateRandomCode(8);
        return $gwToken;
    }

    /**
     * 添加后台管理员gatewayToken
     * @param string $gwToken
     * @param string $sessionId
     * @throws \Exception
     * @return boolean
     * */
    public function setAdminGWToken($gwToken,$sessionId){
        return $this->setHashCache($this->gateway_token, $gwToken, self::FIELD_NAME_USER_ID, $sessionId);
    }

}