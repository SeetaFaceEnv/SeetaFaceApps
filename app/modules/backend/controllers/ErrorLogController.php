<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\TDevice;
use SeetaAiBuildingCommunity\Models\TErrorLog;

class ErrorLogController extends ControllerBase
{
    /*
     * 检索
     * */
    public function listAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $startIndex = $this->request->getPost("start_index");
        $getCount = $this->request->getPost("get_count");
        $deviceName = $this->request->getPost("device_name");
        $timeBegin = $this->request->getPost("time_begin");
        $timeEnd = $this->request->getPost("time_end");

        if (empty($sessionId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        $data = [];

        //模糊搜索设备名称
        if (!empty($deviceName)) {
            try {
                $devices = TDevice::findByName($deviceName);
            } catch ( \Exception $exception ) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }

            if (empty($devices)) {
                return parent::getResponse(array(
                    CODE => ERR_SUCCESS,
                    "error_logs" => [],
                    "total" => 0,
                ));
            }

            $deviceCodes = [];
            foreach ($devices as $device) {
                $deviceCodes[] = $device['code'];
            }
            $data['device_code']['$in'] = $deviceCodes;
        }

        if (!empty($timeBegin)) {
            $data['time_begin'] = (int)$timeBegin;
        }
        if (!empty($timeEnd)) {
            $data['time_end'] = (int)$timeEnd + 86400000;
        }

        $data['field'] = "_id";
        $data['order'] = -1;

        try {
            $errorLogs = TErrorLog::search($data, (int)$startIndex, (int)$getCount);
            $errorLogs = TErrorLog::addMoreInfo($errorLogs);

            $count = TErrorLog::searchCount($data);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "error_logs" => $errorLogs,
            "total" => $count,
        ));
    }

}