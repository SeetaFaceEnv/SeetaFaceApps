<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use SeetaAiBuildingCommunity\Common\Manager\Utility;
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
        $deviceCode = $this->request->getPost("device_code");
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
        if (!empty($deviceCode)) {
            $data['device_code'] = $deviceCode;
        }
        if (!empty($timeBegin)) {
            $data['time_begin'] = (int)$timeBegin;
        }
        if (!empty($timeEnd)) {
            $data['time_end'] = (int)$timeEnd + 86400;
        }

        $data['field'] = "_id";
        $data['order'] = -1;

        try {
            $errorLogs = TErrorLog::search($data, (int)$startIndex, (int)$getCount);
            $count = TErrorLog::searchCount($data);
        } catch (\Exception $exception) {
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