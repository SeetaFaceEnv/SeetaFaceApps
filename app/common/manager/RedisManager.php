<?php

namespace SeetaAiBuildingCommunity\Common\Manager;

/*
 * Cache Manager
 * */

use Predis\Client;

class RedisManager
{
    const DEF_INFO_PREFIX = 1;
    const DEF_INFO_LIFETIME = 2;
    const DEF_INFO_IS_HASH = 3;

    /**
     *
     * @var Client
     * */
    protected $redis;

    /**
     * key的前缀
     * @var string
     * */
    protected $prefix;

    /**
     * @param $host
     * @param null $password
     * @param string $prefix
     * @param int $database
     */
    public function __construct($host = "", $password = null, $prefix = "", $database = 0)
    {
        $options = [];
        if(!empty($password)){
            $options['parameters']['password'] = $password;
        }

        if(is_array($host)){
            $options['cluster'] = 'redis';
        }
        else{
            $options['parameters']['database'] = $database;
        }

        $this->prefix = $prefix;

        $this->redis = new Client($host, $options);
    }

    public function __destruct()
    {
        $this->redis->disconnect();
    }

    /**
     * 添加字符串
     *
     * @param string $keyName
     * @param string $val
     * @param integer $lifetime (Seconds)
     */
    public function set($keyName, $val, $lifetime = null) {
        $key = $this->prefix.$keyName;
        $this->redis->set($key, $val);
        if(isset($lifetime)){
            $this->redis->expire($key, $lifetime);
        }
    }

    /**
     * 添加Hash值
     *
     * @param string $keyName
     * @param string $field
     * @param string $val
     * @param integer $lifetime
     */
    public function hSet($keyName, $field, $val, $lifetime = null) {
        $key = $this->prefix.$keyName;
        $this->redis->hSet($key, $field, $val);
        if(isset($lifetime)){
            $this->redis->expire($key, $lifetime);
        }
    }

    /**
     * 删除Hash值
     *
     * @param string $keyName
     * @param string $field
     * @return integer
     */
    public function hDel($keyName, $field) {
        $key = $this->prefix.$keyName;
        return $this->redis->hDel($key, $field);
    }

    /**
     * 重置TTL
     *
     * @param string $keyName
     * @param integer $lifetime
     */
    public function expire($keyName, $lifetime){
        $key = $this->prefix.$keyName;
        $this->redis->expire($key, $lifetime);
    }

    /**
     * 获取 TTL
     *
     * @param string $keyName
     * @return integer
     * */
    public function ttl($keyName){
        $key = $this->prefix.$keyName;
        return $this->redis->ttl($key);
    }

    /**
     * 获取所有的key
     *
     * @param string $keyName
     * @return integer
     * */
    public function keys($keyName){
        $key = $this->prefix.$keyName;
        return $this->redis->keys($key);
    }

    /**
     * 检查缓存是否存在(普通或Hash)
     * @param array $info
     * @param string $key
     * @return boolean
     * @throws \Exception
     * */
    protected function existCache($info, $key)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        try {
            if ($info[self::DEF_INFO_IS_HASH]) {
                $res = $this->redis->hGetAll($this->prefix.$key);
            } else {
                $res = $this->redis->get($this->prefix.$key);
            }
            return isset($res);
        } catch ( \Exception $e ) {
            throw $e;
        }
    }

    /**
     * 从缓存中获取TTL
     * @param array $info
     * @param string $key
     * @return integer
     * @throws \Exception
     * */
    protected function getTTL($info, $key)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        try {
            return $this->ttl($key);
        } catch ( \Exception $e ) {
            throw $e;
        }
    }

    /**
     * 获取缓存，只适用于普通类型(不适用Hash)
     * 如果缓存的类型是Hash，则返回false
     * @param array $info
     * @param string $key
     * @return mixed | boolean
     * @throws \Exception
     * */
    protected function getCache($info, $key)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        if ($info[self::DEF_INFO_IS_HASH]) {
            return false;
        }

        try {
            return $this->redis->get($this->prefix.$key);
        } catch ( \Exception $e ) {
            throw $e;
        }
    }

    /**
     * 获取Hash缓存，只对Hash表有效
     * 如果缓存的类型不是Hash，则返回false
     * @param array $info
     * @param string $key
     * @param string $field
     * @return mixed | boolean
     * @throws \Exception
     * */
    protected function getHashCache($info, $key, $field)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        if (!$info[self::DEF_INFO_IS_HASH]) {
            return false;
        }

        try {
            return $this->redis->hGet($this->prefix.$key, $field);
        } catch ( \Exception $e ) {
            throw $e;
        }
    }

    /**
     * 获取整个 Hash缓存，只对 Hash表有效
     * 如果缓存的类型不是Hash，则返回false
     * @param array $info
     * @param string $key
     * @return array | boolean
     * @throws \Exception
     * */
    protected function getAllHashCache($info, $key)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        if (!$info[self::DEF_INFO_IS_HASH]) {
            return false;
        }

        try {
            return $res = $this->redis->hGetAll($this->prefix.$key);
        } catch ( \Exception $e ) {
            throw $e;
        }
    }

    /**
     * 设置缓存，只适用于普通类型(不适用 Hash表)。
     * 如果缓存的类型不是Hash，则返回false
     * @param array $info
     * @param string $key
     * @param integer | string $value
     * @return boolean
     * @throws \Exception
     * */
    protected function setCache($info, $key, $value)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        if ($info[self::DEF_INFO_IS_HASH]) {
            return false;
        }

        try {
            $this->set($key, $value, $info[self::DEF_INFO_LIFETIME]);
            return true;
        } catch ( \Exception $e ) {
            throw $e;
        }
    }

    /**
     * 设置 Hash缓存，只对 Hash表类型有效。
     * 如果缓存的类型不是Hash，则返回false
     * @param array $info
     * @param string $key
     * @param string $field
     * @param integer | string $value
     * @return boolean
     * @throws \Exception
     * */
    protected function setHashCache($info, $key, $field, $value)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        if (!$info[self::DEF_INFO_IS_HASH]) {
            return false;
        }

        try {
            $this->hSet($key, $field, $value, $info[self::DEF_INFO_LIFETIME]);
            return true;
        } catch ( \Exception $e ) {
            throw $e;
        }
    }

    /**
     * 删除缓存
     * @param array $info
     * @param string $key
     * @return boolean
     * @throws \Exception
     * */
    protected function delCache($info, $key)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        try {
            $this->redis->del([$this->prefix.$key]);
        } catch ( \Exception $e ) {
            throw $e;
        }
    }

    /**
     * 删除Hash缓存
     * @param array $info
     * @param string $key
     * @param string $field
     * @return boolean
     * @throws \Exception
     * */
    protected function delHashCache($info, $key, $field)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        try {
            return $this->hDel($key, $field);
        } catch ( \Exception $e ) {
            throw $e;
        }
    }


    /**
     * 将缓存(整数)增加1，如果不退出，则插入1。
     * 只适用于普通类型(不适用 Hash表)。
     * 如果缓存的类型是散列表，则返回false。
     * @param array $info
     * @param string $key
     * @return boolean
     * @throws \Exception
     * */
    protected function incrCache($info, $key)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        if ($info[self::DEF_INFO_IS_HASH]) {
            return false;
        }

        try {
            $this->redis->incr($this->prefix.$key);
            $res = $this->redis->get($this->prefix.$key);
            return $res;
        } catch ( \Exception $e ) {
            throw $e;
        }
    }

    /**
     * 刷新Key的存在时间
     * @param array $info
     * @param string $key
     * @return boolean
     * @throws \Exception
     * */
    protected function refreshCache($info, $key)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        try {
            if ($info[self::DEF_INFO_IS_HASH]) {
                $res = $this->redis->hGetAll($this->prefix.$key);
            } else {
                $res = $this->redis->get($this->prefix.$key);
            }

            if (isset($res) && !empty($info[self::DEF_INFO_LIFETIME])) {
                $this->expire($key, $info[self::DEF_INFO_LIFETIME]);
                return true;
            }
        } catch ( \Exception $e ) {
            throw $e;
        }
        return false;
    }
}