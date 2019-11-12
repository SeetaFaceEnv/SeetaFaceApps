<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use MongoDB\BSON\ObjectId;
use SeetaAiBuildingCommunity\Common\Manager\SeetaDeviceManager;
use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\TDevice;
use SeetaAiBuildingCommunity\Models\TErrorLog;
use SeetaAiBuildingCommunity\Models\TStream;
use SeetaAiBuildingCommunity\Models\TSystem;
use SeetaAiBuildingCommunity\Models\TTimeTemplate;

class StreamController extends ControllerBase
{
    /*
     * 添加
     * */
    public function addAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $name = $this->request->getPost("name");
        $timeTemplateId = $this->request->getPost("time_template_id") ?: "";
        $cameraParams = json_decode($this->request->getPost("camera_params")) ?: (object)[];

        if (empty($sessionId) || empty($name)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        try {
            $system = TSystem::findSystem();
            if (!empty($timeTemplateId)) {
                $timeTemplate = TTimeTemplate::findById(new ObjectId($timeTemplateId));
                $cameraParams->time_slots = $timeTemplate->time_slots;
            }
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }


        //设置默认流参数
        $cameraParams->threshold_11 = STREAM_DEFAULT_THRESHOLD_11;
        $cameraParams->confidence = STREAM_DEFAULT_CONFIDENCE;
        $cameraParams->unsure = STREAM_DEFAULT_UNSURE;
        $cameraParams->min_clarity = STREAM_DEFAULT_MIN_CLARITY;
        $cameraParams->recognition_mode = STREAM_DEFAULT_RECOGNITION_MODE;
        $cameraParams->min_face = STREAM_DEFAULT_MIN_FACE;
        $cameraParams->max_angle = STREAM_DEFAULT_MAX_ANGLE;
        $cameraParams->crop_ratio = STREAM_DEFAULT_CROP_RATIO;
        $cameraParams->detect_box = STREAM_DEFAULT_DETECT_BOX;
        $cameraParams->report_11_url = $system->server_url . REPORT_11_URL;
        $cameraParams->report_1n_url = $system->server_url . REPORT_1N_URL;
        $cameraParams->recognize_type = STREAM_DEFAULT_RECOGNIZE_TYPE;
        $cameraParams->capture_max_interval = STREAM_DEFAULT_CAPTURE_MAX_INTERVAL;
        $cameraParams->is_light = STREAM_DEFAULT_IS_LIGHT;
        $cameraParams->is_living_detect = STREAM_DEFAULT_IS_LIVING_DETECT;
        $cameraParams->control_signal_out = STREAM_DEFAULT_CONTROL_SIGNAL_OUT;
        $cameraParams->top_n = STREAM_DEFAULT_TOP_N;
        $cameraParams->not_pass_report = STREAM_DEFAULT_NOT_PASS_REPORT;
        $cameraParams->is_working = STREAM_DEFAULT_TOP_IS_WORKING;

        //本地添加设备
        $stream = new TStream([
            "name" => $name,
            "camera_params" => (object)$cameraParams,
            "time_template_id" => $timeTemplateId,
            "status" => STREAM_STATUS_VALID,
        ]);

        try {
            $stream->save();
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }

    /*
     * 编辑
     * */
    public function editAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $id = $this->request->getPost("id");
        $name = $this->request->getPost("name");
        $timeTemplateId = $this->request->getPost("time_template_id");
        $cameraParams = json_decode($this->request->getPost("camera_params")) ?: (object)[];

        if (empty($sessionId) || empty($id)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        try {
            $stream = TStream::findById(new ObjectId($id));
            if (empty($stream)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_STREAM_NOT_EXIST));
            }

            //更换时间模板
            if ($timeTemplateId != $stream->time_template_id) {
                $timeTemplate = TTimeTemplate::findById(new ObjectId($timeTemplateId));
                $cameraParams->time_slots = $timeTemplate->time_slots;
            }

            //查询使用这个流数据的设备
            $devices = TDevice::findByStreamId($id);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //保存名称
        try {
            $stream->name = $name;
            $stream->save();
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        $deviceCodes = [];
        $deviceMap = [];
        foreach ($devices as $device) {
            $deviceCodes[] = $device->code;
            $deviceMap[$device->code] = $device->name;
        }

        //正在被设备使用的流，参数变动时
        if (!empty($deviceCodes) && json_encode($stream->camera_params) != json_encode($cameraParams)) {

            $seetaDeviceManager = new SeetaDeviceManager(SeetaDeviceManager::SYSEND_CAMERA_EDIT_URL);
            foreach ($deviceCodes as $deviceCode) {
                //请求设备管理平台，更改对应设备的流参数
                $cameraParams->id = $id;
                $postData = [];
                $postData['device_code'] = $deviceCode;
                $postData['camera_params'] = [$cameraParams];

                $seetaDeviceManager->setParams($postData);
                $result = $seetaDeviceManager->sendRequest("POST");
                if ($result['res'] != ERR_SUCCESS) {
                    return parent::getResponse(parent::makeErrorResponse($result['res']));
                }
                //将编辑流参数失败的设备记录
                if ($result['device_result'] != true) {
                    $failData[] = $deviceCode;

                    //存储操作失败的日志
                    $log = new TErrorLog([
                        "device_code" => $deviceCode,
                        "level" => 1,
                        "time" => time() * 1000,
                        "content" => "流参数编辑失败",
                    ]);

                    $log->save();
                }
            }
        }

        //保存本地数据
        try {
            $stream->camera_params = (object)$cameraParams;
            $stream->time_template_id = $timeTemplateId;
            $stream->save();
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        if (!empty($failData)) {
            $massage = "";
            foreach ($failData as $item) {
                $massage .= $deviceMap[$item] . ",";
            }
            $massage .= "流媒体参数编辑失败";

            return parent::getResponse(array(
                CODE => ERR_FAILED,
                MESSAGE => $massage
            ));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }

    /*
     * 获取
     * */
    public function getAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $id = $this->request->getPost("id");

        if (empty($sessionId) || empty($id)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        try {
            $stream = TStream::findById(new ObjectId($id));
            if (empty($stream)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_STREAM_NOT_EXIST));
            }
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "stream" => $stream,
        ));
    }

    /*
     * 删除
     * */
    public function deleteAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $id = $this->request->getPost("id");

        if (empty($sessionId) || empty($id)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        try {
            $stream = TStream::findById(new ObjectId($id));
            if (empty($stream)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_STREAM_NOT_EXIST));
            }

            //正在被设备使用的流不允许删除
            $device = TDevice::findByStreamId($id);
            if (!empty($device)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_STREAM_DEVICE_EXIST));
            }

            //假删除
            TStream::falseDeleteById(new ObjectId($id));
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }

    /*
     * 检索
     * */
    public function listAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $startIndex = $this->request->getPost("start_index");
        $getCount = $this->request->getPost("get_count");

        if (empty($sessionId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        $data = [];
        $data['field'] = "_id";
        $data['order'] = -1;

        try {
            $streams = TStream::search($data, (int)$startIndex, (int)$getCount);
            $count = TStream::searchCount($data);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "streams" => $streams,
            "total" => $count,
        ));
    }
}