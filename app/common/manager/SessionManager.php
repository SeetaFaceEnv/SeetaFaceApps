<?php

namespace SeetaAiBuildingCommunity\Common\Manager;

use Phalcon\Di;

class SessionManager extends RedisManager
{
    //网页登录的会话
    protected $infoWebSession = [
        self::DEF_INFO_PREFIX => 'session:',
        self::DEF_INFO_LIFETIME => 7200,
        self::DEF_INFO_IS_HASH => true,
    ];

    //User ID 对于会话的反向映射
    protected $infoWebIdToSession = [
        self::DEF_INFO_PREFIX => 'session_user:',
        self::DEF_INFO_LIFETIME => 7200,
        self::DEF_INFO_IS_HASH => false,
    ];

    //小程序登录的会话
    protected $infoMobileSession = [
        self::DEF_INFO_PREFIX => 'session_mobile:',
        self::DEF_INFO_LIFETIME => null,
        self::DEF_INFO_IS_HASH => true,
    ];

    //User ID 对于会话的反向映射
    protected $infoMobileIdToSession = [
        self::DEF_INFO_PREFIX => 'session_mobile_user:',
        self::DEF_INFO_LIFETIME => null,
        self::DEF_INFO_IS_HASH => false,
    ];

    protected $platformType;
    protected $infoSession;
    protected $infoIdToSession;

    const FIELD_NAME_TOKEN = 'token';
    const FIELD_NAME_PRIVILEGE = 'privilege';
    const FIELD_NAME_USER_ID = 'user_id';
    const FIELD_NAME_USER_TYPE = 'user_type';

    public function __construct($platformType = PLATFORM_TYPE_WEB)
    {
        parent::__construct(Di::getDefault()->get('redis'));
        $this->platformType = $platformType;
        if ((int) $platformType == PLATFORM_TYPE_WEB){
            $this->infoSession = $this->infoWebSession;
            $this->infoIdToSession = $this->infoWebIdToSession;
        }else{
            $this->infoSession = $this->infoMobileSession;
            $this->infoIdToSession = $this->infoMobileIdToSession;
        }
    }

    /**
     * 添加Session
     * @param string $session_id
     * @param string $field
     * @param string $val
     * @throws \Exception
     * @return boolean
     * */
    public function setSession($session_id, $field, $val)
    {
        return $this->setHashCache($this->infoSession, $session_id, $field, $val);
    }

    /**
     * 获取Session
     * @param string $session_id
     * @param string $field
     * @throws \Exception
     * @return string | boolean
     * */
    public function getSession($session_id, $field)
    {
        return $this->getHashCache($this->infoSession, $session_id, $field);
    }

    /**
     * 刷新Session
     * @param string $session_id
     * @throws \Exception
     * @return string | boolean
     * */
    public function refreshSession($session_id)
    {
        $res1 = $this->refreshCache($this->infoSession, $session_id);
        $user_id = $this->getSession($session_id, self::FIELD_NAME_USER_ID);
        $res2 = $this->refreshCache($this->infoIdToSession, $user_id);
        return $res1 && $res2;
    }

    /**
     * 删除Session
     * @param string $session_id
     * @throws \Exception
     * @return boolean
     * */
    public function deleteSession($session_id)
    {
        $user_id = $this->getSession($session_id, self::FIELD_NAME_USER_ID);
        $res1 = $this->delCache($this->infoIdToSession, $user_id);
        $res2 = $this->delCache($this->infoSession, $session_id);
        return $res1 && $res2;
    }

    /**
     * 设置user_id=>session_id的键值对
     * @param string $session_id
     * @param string $user_id
     * @throws \Exception
     * @return boolean
     */
    public function setSessionIdByUserId($user_id, $session_id)
    {
        return $this->setCache($this->infoIdToSession, $user_id, $session_id);
    }

    /**
     * 获取session id
     * @param string $user_id
     * @throws \Exception
     * @return string
     */
    public function getSessionIdByUserId($user_id)
    {
        return $this->getCache($this->infoIdToSession, $user_id);
    }


    /**
     * 获取session id ,获取小程序的
     * @param string $user_id
     * @throws \Exception
     * @return string
     */
    public function getSessionIdByUserIdMobile($user_id)
    {
        return $this->getCache($this->infoMobileIdToSession, $user_id);
    }


    /**
     * 删除Session,删除小程序的
     * @param string $session_id
     * @throws \Exception
     * @return boolean
     * */
    public function deleteSessionMobile($session_id)
    {
        $user_id = $this->getSession($session_id, self::FIELD_NAME_USER_ID);
        $res1 = $this->delCache($this->infoMobileIdToSession, $user_id);
        $res2 = $this->delCache($this->infoMobileSession, $session_id);
        return $res1 && $res2;
    }
}