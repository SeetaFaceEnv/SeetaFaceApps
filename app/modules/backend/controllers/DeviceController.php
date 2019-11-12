<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use MongoDB\BSON\ObjectId;
use SeetaAiBuildingCommunity\Common\Manager\SeetaDeviceBase;
use SeetaAiBuildingCommunity\Common\Manager\SeetaDeviceManager;
use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\TDevice;
use SeetaAiBuildingCommunity\Models\TGroup;
use SeetaAiBuildingCommunity\Models\TMember;
use SeetaAiBuildingCommunity\Models\TStream;
use SeetaAiBuildingCommunity\Models\TSystem;
use SeetaAiBuildingCommunity\Models\TTimeTemplate;

class DeviceController extends ControllerBase
{
    /*
     * 添加
     * */
    public function addAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $name = $this->request->getPost("name");
        $code = $this->request->getPost("code");
        $groupId = $this->request->getPost("group_id") ?: "";

        if (empty($sessionId) || empty($name) || empty($code)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        //发现未知设备
        $seetaDeviceManager = new SeetaDeviceManager(SeetaDeviceManager::SYSEND_DEVICE_DISCOVER_URL);
        $findResult = $seetaDeviceManager->sendRequest("GET");
        if ($result['res'] != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result['res']));
        }
        $discoverDevices = $findResult['devices'];
        if (empty($discoverDevices)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_NOT_EXIST));
        }
        //根据设备编码，查找设备的类型
        foreach ($discoverDevices as $discoverDevice) {
            if ($discoverDevice['device_code'] == $code) {
                $deviceType = (int)$discoverDevice['type'];
            }
        }

        if (empty($deviceType)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_NOT_EXIST));
        }

        //请求安卓设备管理平台同步添加设备
        $postData = [];
        $postData['device_codes'] = [$code];
        $postData['group_id'] = $groupId;

        $seetaDeviceManager->setUrl(SeetaDeviceManager::SYSEND_DEVICE_ADD_URL);
        $seetaDeviceManager->setParams($postData);
        $result = $seetaDeviceManager->sendRequest("POST");
        if ($result['res'] != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result['res']));
        }


        $deviceParams = empty($result["device_results"][0]['device_params']) ? (object)[] : (object)$result["device_results"][0]['device_params'];
        $cameraParams = empty($result["device_results"][0]['camera_params'][0]) ? (object)[] : (object)$result["device_results"][0]['camera_params'][0];

        $deviceInfo = [
            "name" => $name,
            "code" => $code,
            "type" => $deviceType,
            "group_id" => $groupId,
            "stream_ids" => [],
            "device_params" => $deviceParams,
            "camera_params" => $cameraParams,
            "report_1n" => "",
            "report_11" => "",
            "time_template_id" => "",
            "status" => DEVICE_STATUS_VALID,
        ];
        //本地添加设备
        $device = new TDevice($deviceInfo);

        try {
            //如果选择了设备组，则修改设备的默认参数
            if (!empty($groupId)) {
                $group = TGroup::findById(new ObjectId($groupId));
                $device->device_params = $group->device_params;
            }
            $device->save();
            $system = TSystem::findSystem();
            $serverUrl = $system->server_url;
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        if ($deviceType == DEVICE_TYPE_FACE_AND_CARD_MACHINE || $deviceType == DEVICE_TYPE_ACCESS_CONTROL_MACHINE) {
            //给设备添加1:1,1:n地址
            $cameraParams->report_11_url = $serverUrl . REPORT_11_URL;
            $cameraParams->report_1n_url = $serverUrl . REPORT_1N_URL;

            //请求设备管理平台，更改对应设备的流参数
            $postData = [];
            $postData['device_code'] = $code;
            $postData['camera_params'] = [$cameraParams];

            $seetaDeviceManager->setUrl(SeetaDeviceManager::SYSEND_CAMERA_EDIT_URL);
            $seetaDeviceManager->setParams($postData);
            $result = $seetaDeviceManager->sendRequest("POST");
            if ($result['res'] != ERR_SUCCESS) {
                return parent::getResponse(parent::makeErrorResponse($result['res']));
            }
            if ($result['device_result'] != true) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));
            }
        }

        //PC只能网关给设备参数加入上报1:n地址
        if ($deviceType == DEVICE_TYPE_PC_INTELLIGENT_GATEWAY) {
            $deviceParams->report_1n_url = $serverUrl.REPORT_1N_URL;

            //请求设备管理平台，编辑设备
            $postData = [];
            $postData['device_codes'] = [$code];
            $postData['device_params'] = $deviceParams;

            $seetaDeviceManager->setUrl(SeetaDeviceManager::SYSEND_DEVICE_SET_URL);
            $seetaDeviceManager->setParams($postData);
            $result = $seetaDeviceManager->sendRequest("POST");
            if ($result['res'] != ERR_SUCCESS) {
                return parent::getResponse(parent::makeErrorResponse($result['res']));
            }

            try {
                $device->device_params = $deviceParams;
                $device->save();
            } catch ( \Exception $exception ) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }
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
        $groupId = $this->request->getPost("group_id");
        $timeTemplateId = $this->request->getPost("time_template_id");
        $deviceParams = json_decode($this->request->getPost("device_params")) ?: (object)[];
        $cameraParams = json_decode($this->request->getPost("camera_params")) ?: (object)[];
        $report1N = $this->request->getPost("report_1n") ?: "";
        $report11 = $this->request->getPost("report_11") ?: "";

        if (empty($sessionId) || empty($id)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        try {
            $device = TDevice::findById(new ObjectId($id));
            if (empty($device)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_NOT_EXIST));
            }
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //保存本地数据
        try {
            $device->report_1n = trim($report1N);
            $device->report_11 = trim($report11);
            $device->name = $name;
            $device->save();
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        try {
            //如果选择了设备组，则修改设备的默认参数
            if (!empty($groupId) && $groupId != $device->group_id) {
                $group = TGroup::findById(new ObjectId($groupId));
                $deviceParams = $group->device_params;
            }
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        $seetaDeviceManager = new SeetaDeviceManager();
        if ((array)$deviceParams != (array)$device->device_params || $groupId != $device->group_id) {
            //请求设备管理平台，编辑设备

            $deviceData = [];
            $deviceData['device_codes'] = [$device->code];
            $deviceData['device_params'] = $deviceParams;
            $deviceData['group_id'] = isset($groupId) ? $groupId : "default";

            $seetaDeviceManager->setUrl(SeetaDeviceManager::SYSEND_DEVICE_SET_URL);
            $seetaDeviceManager->setParams($deviceData);
            $result = $seetaDeviceManager->sendRequest("POST");
            Utility::log('logger', $result['res'], __METHOD__, __LINE__);
            Utility::log('logger', $result['res'], __METHOD__, __LINE__);
            if ($result['res'] != ERR_SUCCESS) {
                return parent::getResponse(parent::makeErrorResponse($result['res']));
            }

            //保存本地数据
            try {
                $device->group_id = $groupId;
                $device->device_params = $deviceParams;
                $device->save();
            } catch ( \Exception $exception ) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }
        }

        //编辑流参数
        if ($timeTemplateId != $device->time_template_id || json_encode($cameraParams) != json_encode($device->camera_params)) {
            if (!empty($timeTemplateId)) {
                $timeTemplate = TTimeTemplate::findById(new ObjectId($timeTemplateId));
                $cameraParams->time_slots = $timeTemplate->time_slots;
            } else {
                $cameraParams->time_slots = [];
            }

            $system = TSystem::findSystem();
            $cameraParams->report_11_url = $system->server_url . REPORT_11_URL;
            $cameraParams->report_1n_url = $system->server_url . REPORT_1N_URL;

            $cameraData = [];
            $cameraData['device_code'] = $device->code;
            $cameraData['camera_params'] = [$cameraParams];

            $seetaDeviceManager->setUrl(SeetaDeviceManager::SYSEND_CAMERA_EDIT_URL);
            $seetaDeviceManager->setParams($cameraData);
            $result = $seetaDeviceManager->sendRequest("POST");
            if ($result['res'] != ERR_SUCCESS) {
                return parent::getResponse(parent::makeErrorResponse($result['res']));
            }
            if ($result["device_result"] != true) {
                Utility::log('logger', json_encode($result), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));
            }

            try {
                $device->time_template_id = $timeTemplateId;
                $device->camera_params = $cameraParams;
                $device->save();
            } catch ( \Exception $exception ) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }
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
            $device = TDevice::findById(new ObjectId($id));
            if (empty($device)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_NOT_EXIST));
            }
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "device" => $device,
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
            $device = TDevice::findById(new ObjectId($id));
            if (empty($device)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_NOT_EXIST));
            }
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //向设备管理平台请求同步数据
        $postData = [];
        $postData['device_codes'] = [$device->code];

        $seetaDeviceManager = new SeetaDeviceManager(SeetaDeviceManager::SYSEND_DEVICE_DELETE_URL, $postData);
        $result = $seetaDeviceManager->sendRequest("POST");
        if ($result['res'] != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result['res']));
        }
        if ($result["device_results"][0]['result'] != true) {
            return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));
        }

        try {
            //假删除设备
            TDevice::falseDeleteById(new ObjectId($id));

            //删除掉人员的中的此设备
            TMember::deleteDeviceId($id);
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
        $code = $this->request->getPost("code");
        $name = $this->request->getPost("name");

        if (empty($sessionId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        $data = [];
        if (!empty($code)) {
            $data['code'] = trim($code);
        }
        if (!empty($name)) {
            $data['name'] = trim($name);
        }

        $data['field'] = "_id";
        $data['order'] = -1;

        try {
            $devices = TDevice::search($data, (int)$startIndex, (int)$getCount);
            $count = TDevice::searchCount($data);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        $deviceCodes = [];
        foreach ($devices as $device) {
            $deviceCodes[] = $device->code;
        }

        $postData = [];
        $postData['device_codes'] = $deviceCodes;

        $seetaDeviceManager = new SeetaDeviceManager(SeetaDeviceManager::SYSEND_DEVICE_LIST_URL, $postData);
        $result = $seetaDeviceManager->sendRequest("POST");
        $checkAlive = true;
        if ($result['res'] != ERR_SUCCESS) {
            $checkAlive = false;
        }

        $result = $seetaDeviceManager->listDevice($deviceCodes);

        if (($result['res']) != ERR_SUCCESS) {
            $checkAlive = false;
        }

        foreach ($devices as $key => $device1) {
            $streamIds = [];
            //设备状态查询
            $devices[$key]['alive'] = DEVICE_NOT_ALIVE;
            if ($checkAlive == true) {
                foreach ($result['devices'] as $device2) {
                    if ($device1['code'] == $device2['device_code']) {
                        $devices[$key]['alive'] = $device2['alive'];
                    }
                }
            }

            // 处理流类型
            $type = $device1['type'];
            $streamId = [];

            //人证一体机、门禁机
            if (in_array($type, [DEVICE_TYPE_FACE_AND_CARD_MACHINE, DEVICE_TYPE_ACCESS_CONTROL_MACHINE])) {
                if ($type == DEVICE_TYPE_FACE_AND_CARD_MACHINE) {
                    $streamId['stream_name'] = STREAM_FACE_AND_CARD_MACHINE;
                } else {
                    $streamId['stream_name'] = STREAM_TYPE_ACCESS_CONTROL_MACHINE;
                }

                if (!empty($device1['time_template_id'])) {
                    $tTimeTemplate = TTimeTemplate::findById(new ObjectId($device1['time_template_id']));
                    $streamId['time_template_name'] = $tTimeTemplate->name;
                } else {
                    $streamId['time_template_name'] = "";
                }
                $streamIds[] = $streamId;
            }
            //智能网关
            elseif (in_array($type, [DEVICE_TYPE_SEETA_INTELLIGENT_GATEWAY, DEVICE_TYPE_PC_INTELLIGENT_GATEWAY])) {
                if (count($device1['stream_ids']) >= 1) {

                    foreach ($device1['stream_ids'] as $stream_id) {
                        $tStream = TStream::findById(new ObjectId($stream_id));
                        $streamId['stream_name'] = $tStream->name;

                        if (!empty($tStream->time_template_id)) {
                            $tTimeTemplate = TTimeTemplate::findById(new ObjectId($tStream->time_template_id));
                            $streamId['time_template_name'] = $tTimeTemplate->name;
                        } else {
                            $streamId['time_template_name'] = "";
                        }
                        $streamIds[] = $streamId;
                    }

                }
            }
            $devices[$key]['stream_time_template'] = $streamIds;
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "devices" => $devices,
            "total" => $count,
        ));
    }

    /**
     * 发现未知设备
     */
    public function findAction()
    {
        $sessionId = $this->request->getPost("session_id");

        if (empty($sessionId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        //向设备管理平台请求同步数据
        $seetaDeviceManager = new SeetaDeviceManager(SeetaDeviceManager::SYSEND_DEVICE_DISCOVER_URL);
        $result = $seetaDeviceManager->sendRequest("GET");

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            'devices' => $result['devices'],
        ));
    }

    /*
     * 选择流
     * */
    public function editStreamAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $id = $this->request->getPost("id");
        $newStreamIds = json_decode($this->request->getPost("add_stream_ids"));
        $delStreamIds = json_decode($this->request->getPost("del_stream_ids"));

        if (empty($sessionId) || empty($id)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        try {
            $device = TDevice::findById(new ObjectId($id));
            if (empty($device)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_NOT_EXIST));
            }
            if ($device->type != DEVICE_TYPE_SEETA_INTELLIGENT_GATEWAY && $device->type != DEVICE_TYPE_PC_INTELLIGENT_GATEWAY) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_CANT_ADD_STREAM));
            }

            //新添加的流
            $newStreamObjectIds = [];
            foreach ($newStreamIds as $newStreamId) {
                $newStreamObjectIds[] = new ObjectId($newStreamId);
            }
            $newStreams = TStream::findByIds($newStreamObjectIds);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        $webcamCount = 0;
        $cameraParams = [];
        foreach ($newStreams as $stream) {
            $param = $stream->camera_params;
            $param->id = (string)$stream->id;

            //不允许添加多个webcam类型摄像头
            if ($param['type'] == CAMERA_SELF) {
                $webcamCount += 1;
            }
            $cameraParams[] = $param;
        }

        //减少流媒体, 向设备管理平台请求同步数据
        if (!empty($delStreamIds)) {
            $delData = [];
            $delData['device_code'] = $device->code;
            $delData['camera_ids'] = $delStreamIds;

            $seetaDeviceManager = new SeetaDeviceManager(SeetaDeviceManager::SYSEND_CAMERA_DELETE_URL, $delData);
            $result = $seetaDeviceManager->sendRequest("POST");
            if ($result['res'] != ERR_SUCCESS) {
                return parent::getResponse(parent::makeErrorResponse($result['res']));
            }
            if ($result['device_result'] != true) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_DEL_STREAM_WRONG));
            }

            //本地减少流
            $nowStreamIds = array_diff((array)$device->stream_ids, $delStreamIds);
            $device->stream_ids = array_values($nowStreamIds);
            try {
                $device->save();
            } catch ( \Exception $exception ) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }
        }

        $streamIds = [];
        foreach ($device->stream_ids as $stream_id) {
            $streamIds[] = new ObjectId($stream_id);
        }

        try {
            $streams = TStream::findByIds($streamIds);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        foreach ($streams as $stream) {
            if ($stream['camera_params']['type'] == CAMERA_SELF) {
                $webcamCount += 1;
            }
        }

        if ($webcamCount > 1) {
            return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_WEBCAM_OVER_MAX_NUM));
        }

        //增加流媒体, 向设备管理平台请求同步数据
        if (!empty($newStreams)) {
            $addData = [];
            $addData['device_code'] = $device->code;
            $addData['camera_params'] = $cameraParams;

            $seetaDeviceManager = new SeetaDeviceManager(SeetaDeviceManager::SYSEND_CAMERA_ADD_URL, $addData);
            $result = $seetaDeviceManager->sendRequest("POST");
            if ($result['res'] != ERR_SUCCESS) {
                return parent::getResponse(parent::makeErrorResponse($result['res']));
            }if ($result['device_result'] != true) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));
            }

            //本地增加流
            $nowStreamIds = array_unique(array_merge((array)$device->stream_ids, $newStreamIds));  //数组相加并去重
            $device->stream_ids = array_values($nowStreamIds);

            try {
                $device->save();
            } catch ( \Exception $exception ) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }

    /**
     * 更新APK
     */
    public function updateApkAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $deviceCodes = json_decode($this->request->getPost('device_codes'));

        if (empty($sessionId) || empty($deviceCodes)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        $dir = FILE_PATH_APK . "/" . date("Y-M-d_H-i-s") . "/";

        try {
            $fullPath = $this->uploadFile($dir, "apk_file", $filename);
        } catch ( \Exception $exception ) {
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_UPLOAD_WRONG));
        }

        try {
            $system = TSystem::findSystem();
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        // 检验文件是否存在
        if (!file_exists($fullPath)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_UPLOAD_WRONG));
        }

        $apkUrl = Utility::filePathToDownloadUrl($system->server_url, $fullPath);
        //生成文件md5核验值
        $etag = md5(file_get_contents($fullPath));

        //向设备管理平台请求同步数据
        $postData = [];
        $postData['device_codes'] = $deviceCodes;
        $postData['apk_url'] = Utility::filePathToDownloadUrl($system->server_url, $fullPath);
        $postData['etag'] = $etag;

        $seetaDeviceManager = new SeetaDeviceManager(SeetaDeviceManager::SYSEND_DEVICE_UPDATE_URL, $postData);
        $seetaDeviceManager->updateDevice($apkUrl, $etag, $deviceCodes);

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }

    /**
     * 设备应答
     */
    public function testAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $id = $this->request->getPost('id');

        if (empty($sessionId) || empty($id)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        try {
            $device = TDevice::findById(new ObjectId($id));
            if (empty($device)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_NOT_EXIST));
            }
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //向设备管理平台发送请求
        $postData = [];
        $postData['device_code'] = $device->code;
        $postData['types'] = [DEVICE_TEST_LIGHT, DEVICE_TEST_VOICE];

        $seetaDeviceManager = new SeetaDeviceManager(SeetaDeviceManager::SYSEND_DEVICE_TEST_URL, $postData);
        $result = $seetaDeviceManager->sendRequest("POST");
        if ($result['res'] != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result['res']));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }
}