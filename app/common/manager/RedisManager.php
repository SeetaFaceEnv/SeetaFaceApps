<?php
/**
 * Created by PhpStorm.
 * User: jiawei
 * Date: 2018/6/21
 * Time: 16:18
 */
namespace SeetaAiBuildingCommunity\Common\Manager;

/*
 * Cache Manager Example
 * */
use SeetaAiBuildingCommunity\Common\Library\SeetaRedis;

abstract class RedisManager
{
    /*
     * Info table's keynames
     * */
    const DEF_INFO_PREFIX = 1;
    const DEF_INFO_LIFETIME = 2;
    const DEF_INFO_IS_HASH = 3;

    /**
     * Redis Client
     * @var SeetaRedis
     * */
    protected $redis;

    /**
     * Modify the constructor according to your need
     * @param SeetaRedis $redis
     * @return RedisManager
     * */
    public function __construct($redis)
    {
        $this->redis = $redis;
        return $this;
    }

    /**
     * check if the cache exists (normal or hash table)
     * @param array $info
     * @param string $key
     * @return boolean
     * @throws \Exception
     * */
    protected function existCache($info, $key)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        try {
            if($info[self::DEF_INFO_IS_HASH]){
                $res = $this->redis->hGetAll($key);
            }
            else{
                $res = $this->redis->get($key);
            }
            return isset($res);
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * get the TTL of the cache
     * @param array $info
     * @param string $key
     * @return integer
     * @throws \Exception
     * */
    protected function getTTL($info, $key){
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        try {
            return $this->redis->ttl($key);
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get Cache, works only for Normal type (not for Hash table)
     * if the type of the cache is Hash table, return false
     * @param array $info
     * @param string $key
     * @return mixed | boolean
     * @throws \Exception
     * */
    protected function getCache($info, $key)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        if($info[self::DEF_INFO_IS_HASH]){
            return false;
        }

        try {
            return $this->redis->get($key);
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get Hash Cache, works only for Hash table
     * if the type of the cache is not Hash table, return false
     * @param array $info
     * @param string $key
     * @param string $field
     * @return mixed | boolean
     * @throws \Exception
     * */
    protected function getHashCache($info, $key, $field)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        if(!$info[self::DEF_INFO_IS_HASH]){
            return false;
        }

        try {
            return $this->redis->hGet($key, $field);
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get entire Hash Cache, works only for Hash table
     * if the type of the cache is not Hash table, return false
     * @param array $info
     * @param string $key
     * @return array | boolean
     * @throws \Exception
     * */
    protected function getAllHashCache($info, $key)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        if(!$info[self::DEF_INFO_IS_HASH]){
            return false;
        }

        try {
            return $this->redis->hGetAll($key);
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Set Cache, works only for Normal type (not for Hash table).
     * If the type of the cache is Hash table, return false.
     * @param array $info
     * @param string $key
     * @param integer | string $value
     * @return boolean
     * @throws \Exception
     * */
    protected function setCache($info, $key, $value)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        if($info[self::DEF_INFO_IS_HASH]){
            return false;
        }

        try {
            $this->redis->set($key, $value, $info[self::DEF_INFO_LIFETIME]);
            return true;
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Set Hash Cache, works only for Hash table type.
     * If the type of the cache is not Hash table, return false.
     * @param array $info
     * @param string $key
     * @param string $field
     * @param integer | string $value
     * @return boolean
     * @throws \Exception
     * */
    protected function setHashCache($info, $key, $field, $value){
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        if(!$info[self::DEF_INFO_IS_HASH]){
            return false;
        }

        try {
            $this->redis->hSet($key, $field, $value,$info[self::DEF_INFO_LIFETIME]);
            return true;
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * delete Cache
     * @param array $info
     * @param string $key
     * @return boolean
     * @throws \Exception
     * */
    protected function delCache($info, $key)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        try {
            $this->redis->del($key);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * delete Cache
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
            return $this->redis->hDel($key,$field);
        }
        catch (\Exception $e) {
            throw $e;
        }
    }


    /**
     * Increment the cache (integer) by 1, if not exit, insert one.
     * Works only for Normal type (not for Hash table).
     * If the type of the cache is Hash table, return false.
     * @param array $info
     * @param string $key
     * @return boolean
     * @throws \Exception
     * */
    protected function incrCache($info, $key)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        if($info[self::DEF_INFO_IS_HASH]){
            return false;
        }

        try {
            $this->redis->incr($key);
            $res = $this->redis->get($key);
            return $res;
        }
        catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * refresh the life time
     * @param array $info
     * @param string $key
     * @return boolean
     * @throws \Exception
     * */
    protected function refreshCache($info, $key)
    {
        $key = $info[self::DEF_INFO_PREFIX] . $key;

        try {
            if($info[self::DEF_INFO_IS_HASH]){
                $res = $this->redis->hGetAll($key);
            }
            else{
                $res = $this->redis->get($key);
            }

            if(isset($res) && !empty($info[self::DEF_INFO_LIFETIME])){
                $this->redis->expire($key, $info[self::DEF_INFO_LIFETIME]);
                return true;
            }
        }
        catch (\Exception $e) {
            throw $e;
        }
        return false;
    }
}