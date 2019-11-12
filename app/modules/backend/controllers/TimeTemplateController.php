<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use MongoDB\BSON\ObjectId;
use SeetaAiBuildingCommunity\Common\Manager\SeetaDeviceManager;
use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\TDevice;
use SeetaAiBuildingCommunity\Models\TStream;
use SeetaAiBuildingCommunity\Models\TTimeTemplate;

class TimeTemplateController extends ControllerBase
{
    /*
     * 添加
     * */
    public function addAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $name = $this->request->getPost("name");
        $description = $this->request->getPost("description") ?: "";
        $validDate = json_decode($this->request->getPost("valid_date"));
        $invalidDate = json_decode($this->request->getPost("invalid_date"));
        $validTime = json_decode($this->request->getPost("valid_time"));
        $excludeWeekend = (string)$this->request->getPost("exclude_weekend");
        $specialValidDate = json_decode($this->request->getPost("special_valid_date"));
        $specialInvalidDate = json_decode($this->request->getPost("special_invalid_date"));
        $timeSlots = json_decode($this->request->getPost("time_slots"));

        if (empty($sessionId) || empty($name) || empty($timeSlots)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        //本地添加模板
        $template = new TTimeTemplate([
            "name" => $name,
            "description" => $description,
            "valid_date" => $validDate,
            "invalid_date" => $invalidDate,
            "valid_time" => $validTime,
            "exclude_weekend" => $excludeWeekend,
            "special_valid_date" => $specialValidDate,
            "special_invalid_date" => $specialInvalidDate,
            "time_slots" => $timeSlots,
            "status" => TIME_TEMPLATE_STATUS_VALID,
        ]);

        try{
            $template->save();
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
     * 编辑
     * */
    public function editAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $id = $this->request->getPost("id");
        $name = $this->request->getPost("name");
        $description = $this->request->getPost("description");
        $validDate = json_decode($this->request->getPost("valid_date"));
        $invalidDate = json_decode($this->request->getPost("invalid_date"));
        $validTime = json_decode($this->request->getPost("valid_time"));
        $excludeWeekend = $this->request->getPost("exclude_weekend");
        $specialValidDate = json_decode($this->request->getPost("special_valid_date"));
        $specialInvalidDate = json_decode($this->request->getPost("special_invalid_date"));
        $timeSlots = json_decode($this->request->getPost("time_slots"));

        if (empty($sessionId) || empty($id) || empty($timeSlots)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        try {
            $template = TTimeTemplate::findById(new ObjectId($id));
            if (empty($template)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_TIME_TEMPLATE_NOT_EXIST));
            }
            //查找正在使用此模板的设备
            $devices = TDevice::findByTemplateId($id);
            //查找正在使用此模板的流
            $streams = TStream::findByTemplateId($id);

            $streamIds = [];
            $streamObIds = [];
            foreach ($streams as $stream) {
                $streamIds[] = (string)$stream->_id;
                $streamObIds[] = $stream->_id;
            }

            //查找出正在使用此模板的流所属的设备
            $streamDevices = TDevice::findByStreamIds($streamIds);
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        $deviceObIds = [];
        foreach ($devices as $device) {
            $deviceObIds[] = $device->_id;
        }

        $seetaDeviceManager = new SeetaDeviceManager(SeetaDeviceManager::SYSEND_CAMERA_EDIT_URL);
        //更改 使用模板的设备 的流参数
        foreach ($devices as $device){
            $device->camera_params['time_slots'] = $timeSlots;
            $cameraParams = $device->camera_params;

            //请求设备管理平台，更改对应设备的流参数
            $postData = [];
            $postData['device_code'] = $device->code;
            $postData['camera_params'] = [$cameraParams];

            $seetaDeviceManager->setParams($postData);
            $result = $seetaDeviceManager->sendRequest("POST");
            if ($result['res'] != ERR_SUCCESS) {
                return parent::getResponse(parent::makeErrorResponse($result['res']));
            }
            if ($result['device_result'] != true) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));
            }
        }

        //更改 流里面包含模板 设备的流参数
        foreach ($streamDevices as $device){
            foreach ($streams as $stream) {
                $streamId = (string)$stream->_id;
                if (in_array($streamId , (array)$device->stream_ids)) {

                    $stream->camera_params['time_slots'] = $timeSlots;
                    $cameraParams = $stream->camera_params;
                    $cameraParams['_id'] = $streamId;

                    //请求设备管理平台，更改对应设备的流参数
                    $postData = [];
                    $postData['device_code'] = $device->code;
                    $postData['camera_params'] = [$cameraParams];

                    $result = $seetaDeviceManager->sendRequest("POST");
                    if ($result['res'] != ERR_SUCCESS) {
                        return parent::getResponse(parent::makeErrorResponse($result['res']));
                    }
                    if ($result['device_result'] != true) {
                        return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));
                    }
                }
            }
        }

        //保存本地数据
        try {
            $template->name = $name;
            $template->description = $description;
            $template->valid_date = $validDate;
            $template->invalid_date = $invalidDate;
            $template->valid_time = $validTime;
            $template->exclude_weekend = $excludeWeekend;
            $template->special_valid_date = $specialValidDate;
            $template->special_invalid_date = $specialInvalidDate;
            $template->time_slots = $timeSlots;
            $template->save();

            //更改流媒体流参数的时间
            TStream::changeTime($streamObIds, $timeSlots);

            //更改设备流参数的时间
            TDevice::changeTime($deviceObIds, $timeSlots);
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
            $template = TTimeTemplate::findById(new ObjectId($id));
            if (empty($template)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_TIME_TEMPLATE_NOT_EXIST));
            }
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "time_template" => $template,
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
            //被使用的模板不允许删除
            $stream = TStream::findByTemplateId($id);
            if (!empty($stream)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_TIME_TEMPLATE_STREAM_EXIST));
            }
            $device = TDevice::findByTemplateId($id);
            if (!empty($device)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_TIME_TEMPLATE_DEVICE_EXIST));
            }

            //假删除
            TTimeTemplate::falseDeleteById(new ObjectId($id));
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

        $data=[];
        $data['field'] = "_id";
        $data['order'] = -1;

        try {
            $templates = TTimeTemplate::search($data, (int)$startIndex, (int)$getCount);
            $count = TTimeTemplate::searchCount($data);
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "time_templates" => $templates,
            "total" => $count,
        ));
    }
}