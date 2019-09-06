<?php

namespace SeetaAiBuildingCommunity\Common\Manager;

class SeetaDeviceManager
{
    /**
     * 向设备管理平台发送请求
     * @param string $url
     * @param array $data
     * @return int
     */
    static public function sendRequest($url = null, $data = null)
    {
        //发送请求
        if (!isset($data)) {
            $result = Utility::sendUrlGet($url);
        } else {
            $result = Utility::sendUrlPost($url, $data);
        }
        $result = json_decode($result,true);

        if (!isset($result['res'])) {
            Utility::log('logger', "同步接口请求失败，接口地址：".$url, __METHOD__, __LINE__);
            return ERR_SYNCHRO_POST_WRONG;
        }
        if ($result['res'] != ERR_SUCCESS){
            Utility::log('logger', "同步数据失败，接口地址：".$url." 返回结果：".$result['res'], __METHOD__, __LINE__);
            return ERR_SEETA_DEVICE_CODES[$result['res']];
        }

        return $result;
    }

}