<?php
namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use MongoDB\BSON\ObjectId;
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
        $findResult = SeetaDeviceManager::sendRequest(SYSEND_DEVICE_DISCOVER_URL);
        if (!is_array($findResult)) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }
        $discoverDevices = $findResult['devices'];
        if (empty($discoverDevices)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_NOT_EXIST));
        }
        //根据设备编码，查找设备的类型
        foreach ($discoverDevices as $discoverDevice){
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

        $result = SeetaDeviceManager::sendRequest(SYSEND_DEVICE_ADD_URL, $postData);
        if (!is_array($result)) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }
        if ($result["device_results"][0]['result'] != true) {
            Utility::log('logger', json_encode($result), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));
        }

        $deviceParams = empty($result["device_results"][0]['device_params']) ? (object)[] : (object)$result["device_results"][0]['device_params'];
        $cameraParams = empty($result["device_results"][0]['camera_params'][0]) ? (object)[] : (object)$result["device_results"][0]['camera_params'][0];

        //本地添加设备
        $device = new TDevice([
            "name" => $name,
            "code" => $code,
            "type" => $deviceType,
            "group_id" => $groupId,
            "stream_ids" => [],
            "device_params" => $deviceParams,
            "camera_params" => $cameraParams,
            "status" => DEVICE_STATUS_VALID,
        ]);


        try{
            //如果选择了设备组，则修改设备的默认参数
            if (!empty($groupId)) {
                $group = TGroup::findById(new ObjectId($groupId));
                $device->device_params = $group->device_params;
            }
            $device->save();
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        if ($deviceType == DEVICE_TYPE_1 || $deviceType == DEVICE_TYPE_2) {
            //给设备添加1:1,1:n地址
            $system = TSystem::findSystem();
            $cameraParams->report_11_url = $system->server_url.REPORT_11_URL;
            $cameraParams->report_1n_url = $system->server_url.REPORT_1N_URL;

            //请求设备管理平台，更改对应设备的流参数
            $postData = [];
            $postData['device_code'] = $code;
            $postData['camera_params'] = [$cameraParams];

            $result = SeetaDeviceManager::sendRequest(SYSEND_CAMERA_EDIT_URL, $postData);
            if (!is_array($result)) {
                return parent::getResponse(parent::makeErrorResponse($result));
            }
            if ($result['device_result'] != true) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));
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
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        try{
            //如果选择了设备组，则修改设备的默认参数
            if (!empty($groupId) && $groupId != $device->group_id) {
                $group = TGroup::findById(new ObjectId($groupId));
                $deviceParams = $group->device_params;
            }
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        if ((array)$deviceParams != (array)$device->device_params) {
            //请求设备管理平台，编辑设备
            $deviceData = [];
            $deviceData['device_codes'] = [$device->code];
            $deviceData['device_params'] = $deviceParams;
            //将设备从设备组内移除
            if (isset($groupId)) {
                $deviceData['group_id'] = $groupId;
            } else {
                $deviceData['group_id'] = "default";
            }

            $result = SeetaDeviceManager::sendRequest(SYSEND_DEVICE_SET_URL, $deviceData);
            if (!is_array($result)) {
                return parent::getResponse(parent::makeErrorResponse($result));
            }
        }

        //保存本地数据
        try {
            $device->name = $name;
            $device->group_id = $groupId;
            $device->device_params = $deviceParams;
            $device->save();
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //编辑流参数
        if ($timeTemplateId !=  $device->time_template_id || json_encode($cameraParams) != json_encode($device->camera_params))
        {
            if (!empty($timeTemplateId)) {
                $timeTemplate = TTimeTemplate::findById(new ObjectId($timeTemplateId));
                $cameraParams->time_slots = $timeTemplate->time_slots;
            } else {
                $cameraParams->time_slots = [];
            }

            $system = TSystem::findSystem();
            $cameraParams->report_11_url = $system->server_url.REPORT_11_URL;
            $cameraParams->report_1n_url = $system->server_url.REPORT_1N_URL;

            $cameraData = [];
            $cameraData['device_code'] = $device->code;
            $cameraData['camera_params'] = [$cameraParams];

            $result = SeetaDeviceManager::sendRequest(SYSEND_CAMERA_EDIT_URL, $cameraData);
            if (!is_array($result)) {
                return parent::getResponse(parent::makeErrorResponse($result));
            }
            if ($result["device_result"] != true) {
                Utility::log('logger', json_encode($result), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));
            }

            try {
                $device->time_template_id = $timeTemplateId;
                $device->camera_params = $cameraParams;
                $device->save();
            } catch (\Exception $exception) {
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
        } catch (\Exception $exception) {
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
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //向设备管理平台请求同步数据
        $postData = [];
        $postData['device_codes'] = [$device->code];

        $result = SeetaDeviceManager::sendRequest(SYSEND_DEVICE_DELETE_URL, $postData);
        if (!is_array($result)) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }
        if ($result["device_results"][0]['result'] != true) {
            return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));
        }

        try {
            //假删除设备
            TDevice::falseDeleteById(new ObjectId($id));

            //删除掉人员的中的此设备
            TMember::deleteDeviceId($id);
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
            $data['code'] = $code;
        }
        if (!empty($name)) {
            $data['name'] = $name;
        }

        $data['field'] = "_id";
        $data['order'] = -1;

        try {
            $devices = TDevice::search($data, (int)$startIndex, (int)$getCount);
            $count = TDevice::searchCount($data);
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        $deviceCodes = [];
        foreach ($devices as $device) {
            $deviceCodes[] = $device->code;
        }

        $result = SeetaDeviceManager::sendRequest(SYSEND_DEVICE_LIST_URL, ["device_codes" => $deviceCodes]);
        if (!is_array($result)) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        foreach ($devices as $key => $device1) {
            $devices[$key]['alive'] = DEVICE_NOT_ALIVE;
            foreach ($result['devices'] as $device2) {
                if ($device1['code'] == $device2['device_code']) {
                    $devices[$key]['alive'] = $device2['alive'];
                }
            }
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
        $result = SeetaDeviceManager::sendRequest(SYSEND_DEVICE_DISCOVER_URL);
        if (!is_array($result)) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

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
        $addStreamIds = json_decode($this->request->getPost("add_stream_ids"));
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
            if ($device->type != DEVICE_TYPE_3) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_CANT_ADD_STREAM));
            }

            //新添加的流
            $addStreamObIds = [];
            foreach ($addStreamIds as $addStreamId) {
                $addStreamObIds[] = new ObjectId($addStreamId);
            }
            $addStreams = TStream::findByIds($addStreamObIds);
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        $webcamCount = 0;
        $cameraParams = [];
        foreach ($addStreams as $stream) {
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

            $result = SeetaDeviceManager::sendRequest(SYSEND_CAMERA_DELETE_URL, $delData);
            if (!is_array($result)) {
                return parent::getResponse(parent::makeErrorResponse($result));
            }
            if ($result['device_result'] != true) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_DEL_STREAM_WRONG));
            }

            //本地减少流
            $newStreamIds = array_diff((array)$device->stream_ids, $delStreamIds);
            $device->stream_ids = (array)$newStreamIds;
            try {
                $device->save();
            } catch (\Exception $exception) {
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
        } catch (\Exception $exception) {
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
        if (!empty($addStreams)) {
            $addData = [];
            $addData['device_code'] = $device->code;
            $addData['camera_params'] = $cameraParams;

            $result = SeetaDeviceManager::sendRequest(SYSEND_CAMERA_ADD_URL, $addData);
            if (!is_array($result)) {
                return parent::getResponse(parent::makeErrorResponse($result));
            }
            if ($result['device_result'] != true) {
                return parent::getResponse(parent::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));
            }

            //本地增加流
            $newStreamIds = array_unique(array_merge((array)$device->stream_ids, $addStreamIds));  //数组相加并去重
            $device->stream_ids = (array)$newStreamIds;

            try {
                $device->save();
            } catch (\Exception $exception) {
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
        } catch (\Exception $exception) {
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_UPLOAD_WRONG));
        }

        try {
            $system = TSystem::findSystem();
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        // 检验文件是否存在
        if (!file_exists($fullPath)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_UPLOAD_WRONG));
        }

        //对文件内容进行md5加密
        $etag = md5(file_get_contents($fullPath));

        //向设备管理平台请求同步数据
        $postData = [];
        $postData['device_codes'] = $deviceCodes;
        $postData['apk_url'] = Utility::filePathToDownloadUrl($system->server_url, $fullPath);
        $postData['etag'] = $etag;

        $result = SeetaDeviceManager::sendRequest(SYSEND_DEVICE_UPDATE_URL, $postData);
        Utility::log('logger',"apk".json_encode($result), __METHOD__, __LINE__);
        if (!is_array($result)) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }

    /**
     * 设备应答
     */
    public function responseAction()
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
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //向设备管理平台发送请求
        $postData = [];
        $postData['device_code'] = $device->code;
        $postData['types'] = [1,2];

        $result = SeetaDeviceManager::sendRequest(SYSEND_DEVICE_TEST_URL, $postData);

        if (!is_array($result)) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }
}