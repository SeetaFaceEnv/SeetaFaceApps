<?php
/**
 * Created by PhpStorm.
 * User: jiawei
 * Date: 2018/6/19
 * Time: 14:25
 */

namespace SeetaAiBuildingCommunity\Common\Library;

use Predis\Client;

/*
 * For using it, you need to install the Predis/Predis by using Composer
 * Like: composer require predis/predis
 * */

class SeetaRedis {

    /**
     *
     * @var Client
     * */
    protected $client;

    /**
     * the prefix of all the key
     * @var string
     * */
    protected $prefix;

    /**
     * Initialize
     *
     * @param string | array $host
     * @param string        $password
     * @param string        $prefix
     *      If you need set a fixed prefix for all the key-value, you can set this field
     * @param integer        $database
     *
     * example:
     *
     * For a node:
     *
     * $host = 'tcp://127.0.0.1:7000'
     *
     * $database = 2
     * $password = "123456"
     * $redis = new Redis($host, $password, $database)
     *
     * For cluster:
     * $host = [
     *  'tcp://127.0.0.1:7000',
     *  'tcp://127.0.0.1:7001',
     *  'tcp://127.0.0.1:7002',
     *  'tcp://127.0.0.1:7003',
     *  'tcp://127.0.0.1:7004',
     *  'tcp://127.0.0.1:7005',
     * ]
     *
     * Attention: Cluster mode doesn't support the SELECT command, which means we can't change database in this mode
     *
     **/

    public function __construct($host, $password = null, $prefix = "", $database = 0)
    {
        $options = [];
        if(!empty($password)){
            //Password is set
            $options['parameters']['password'] = $password;
        }

        if(is_array($host)){
            //Cluster mode
            $options['cluster'] = 'redis';
        }
        else{
            //Normal mode
            $options['parameters']['database'] = $database;
        }

        $this->prefix = $prefix;

        $this->client = new Client($host, $options);
    }

    public function __destruct()
    {
        $this->client->disconnect();
    }

    /**
     * insert a string
     *
     * @param string $keyName
     * @param string $val
     * @param integer $lifetime (Seconds)
     */
    public function set($keyName, $val, $lifetime = null) {
        $key = $this->prefix.$keyName;
        $this->client->set($key, $val);
        if(isset($lifetime)){
            $this->client->expire($key, $lifetime);
        }
    }

    /**
     * get the value by key
     *
     * @param string $keyName
     * @return string
     */
    public function get($keyName) {
        return $this->client->get($this->prefix.$keyName);
    }

    /**
     * increase the value by key
     *
     * @param string $keyName
     * @param integer $num
     */
    public function increm($keyName, $num) {
        $this->client->incrby($this->prefix.$keyName, $num);
    }

    /**
     * incr the value
     *
     * @param string $keyName
     * @param integer $num
     */
    public function incr($keyName) {
        $this->client->incr($this->prefix.$keyName);
    }

    /**
     * delete a record by key
     *
     * @param string $keyName
     * @return integer
     */
    public function del($keyName) {
        return $this->client->del([$this->prefix.$keyName]);
    }

    /**
     * get a hash table by key
     *
     * @param string $keyName
     * @return array
     */
    public function hGetAll($keyName) {
        return $this->client->hGetAll($this->prefix.$keyName);
    }

    /**
     * get a record from a hash table
     *
     * @param string $keyName
     * @param string $field
     * @return string
     */
    public function hGet($keyName, $field) {
        return $this->client->hGet($this->prefix.$keyName, $field);
    }

    /**
     * set a record in a hash table
     *
     * @param string $keyName
     * @param string $field
     * @param string $val
     * @param integer $lifetime
     */
    public function hSet($keyName, $field, $val, $lifetime = null) {
        $key = $this->prefix.$keyName;
        $this->client->hSet($key, $field, $val);
        if(isset($lifetime)){
            $this->client->expire($key, $lifetime);
        }
    }

    /**
     * delete a record in a hash table
     *
     * @param string $keyName
     * @param string $field
     * @return integer
     */
    public function hDel($keyName, $field) {
        $key = $this->prefix.$keyName;
        return $this->client->hDel($key, $field);
    }

    /**
     * Reset TTL for a record
     *
     * @param string $keyName
     * @param integer $lifetime
     */
    public function expire($keyName, $lifetime){
        $key = $this->prefix.$keyName;
        $this->client->expire($key, $lifetime);
    }

    /**
     * Get TTL
     *
     * @param string $keyName
     * @return integer
     * */
    public function ttl($keyName){
        $key = $this->prefix.$keyName;
        return $this->client->ttl($key);
    }

    /**
     * Get All the keys
     *
     * @param string $keyName
     * @return integer
     * */
    public function keys($keyName){
        $key = $this->prefix.$keyName;
        return $this->client->keys($key);
    }
}