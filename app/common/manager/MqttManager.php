<?php

namespace SeetaAiBuildingCommunity\Common\Manager;

use Phalcon\Di;
use \Mosquitto\Client;
class MqttManager
{


    protected $mqttHandle;

    public function __construct()
    {
        $this->mqttHandle = new \Mosquitto\Client();

        $config = Di::getDefault()->get('config')->mqtt;;
        if (!empty($config->user) && !empty($config->passwd)) {
            $this->mqttHandle->setCredentials($config->user, $config->passwd);
        }
        $this->mqttHandle->connect($config->host, $config->port);
    }

    /**
     * 推送mqtt消息
     * @param string $topic
     * @param string $payload
     * @param int $qos
     * @param bool $retain
     */
    public function sendMsg($topic, $payload, $qos = 1, $retain = false)
    {
        $this->mqttHandle->loop();
        $this->mqttHandle->publish($topic, $payload, $qos, $retain);
        sleep(1);
    }


    public function __destruct()
    {
    }
}