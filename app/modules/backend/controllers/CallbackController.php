<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use SeetaAiBuildingCommunity\Common\Library\Gateway;
use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\TDevice;
use SeetaAiBuildingCommunity\Models\TErrorLog;

class CallbackController extends ControllerBase
{
    /*
     * 设备状态变更回调
     * */
    public function deviceStatusAction()
    {
        $callbackInfo = json_decode($this->request->getRawBody(),true);
        $deviceCode = $callbackInfo["device_code"];
        $timestamp = $callbackInfo["timestamp"];

        try {
            $device = TDevice::findByCode($deviceCode);
            if (empty($device)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_NOT_EXIST));
            }
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        $msg = array(
            "command" =>"device_status_change",
            "content" => [
                "device_name" => $device->name,
                "device_code" => $device->code,
                "status_type" => "",
                "time" => $timestamp,
            ]
        );

        $log = array(
            'device_code' => $device->code,
            'device_name' => $device->name,
            'level' => 1,
            'time' => $timestamp,
            'content' => "",
        );

        if (isset($callbackInfo["camera_status"])) {
            if ($callbackInfo["camera_status"] != true) {
                $msg['content']['status_type'] = DEVICE_CAMERA_WRONG;
                $log['content'] = "摄像头异常";
            } else {
                $msg['content']['status_type'] = DEVICE_CAMERA_VALID;
                $log['content'] = "摄像头恢复正常";
            }
            Gateway::sendToGroup("admin", json_encode($msg));
            $errorLog = new TErrorLog($log);
            $errorLog->save();
        }

        if (isset($callbackInfo["display_status"])) {
            if ($callbackInfo["display_status"] != true) {
                $msg['content']['status_type'] = DEVICE_DISPLAY_WRONG;
                $log['content'] = "应用显示异常";
            } else {
                $msg['content']['status_type'] = DEVICE_DISPLAY_VALID;
                $log['content'] = "应用显示恢复正常";
            }
            Gateway::sendToGroup("admin", json_encode($msg));
            $errorLog = new TErrorLog($log);
            $errorLog->save();
        }

        if (isset($callbackInfo["alive"])) {
            if ($callbackInfo["alive"] != true) {
                $msg['content']['status_type'] = DEVICE_OFFLINE;
                $log['content'] = "设备离线";
            } else {
                $msg['content']['status_type'] = DEVICE_ONLINE;
                $log['content'] = "设备恢复在线";
            }
            Gateway::sendToGroup("admin", json_encode($msg));
            $errorLog = new TErrorLog($log);
            $errorLog->save();
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }

    /*
     * 照片注册错误回调
     * */
    public function registerImageAction()
    {
        $callbackInfo = json_decode($this->request->getRawBody(),true);
        $deviceCode = $callbackInfo["device_code"];
        $imageId = $callbackInfo["image_id"];
        $data = $callbackInfo["data"];
        $timestamp = $callbackInfo["timestamp"];

        try {
            //设备
            $device = TDevice::findByCode($deviceCode);
            if (empty($device)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_NOT_EXIST));
            }
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        Utility::log('logger', "照片注册错误，设备编码：".$device->code ."，照片：".$imageId."，原因：".$data, __METHOD__, __LINE__);

        $log = new TErrorLog([
            'device_code' => $device->code,
            'device_name' => $device->name,
            'level' => 1,
            'time' => $timestamp,
            'content' => $imageId.$data,
        ]);

        try {
            $log->save();
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }

    /*
     * 设备日志回调
     * */
    public function logAction()
    {
        $callbackInfo = json_decode($this->request->getRawBody(),true);
        $deviceCode = $callbackInfo["device_code"];
        $level = $callbackInfo["level"];
        $log = $callbackInfo["log"];
        $timestamp = $callbackInfo["timestamp"];

        try {
            $device = TDevice::findByCode($deviceCode);
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        $errorLog = new TErrorLog([
            "device_code" => $deviceCode,
            "device_name" => $device->name,
            "level" => $level,
            "time" => $timestamp,
            "content" => $log,
        ]);

        try {
            $errorLog->save();
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }
}