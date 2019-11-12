<?php

namespace SeetaAiBuildingCommunity\Common\Manager;

use SeetaAiBuildingCommunity\Models\TErrorLog;

class SeetaDeviceManager
{

    private $url;
    private $params;

    const SEETA_DEVICE_ERROR_CODE = 10000;

    // 设备
    const SYSEND_DEVICE_ADD_URL = SYSEND_PREFIX."/device/add";                    //添加设备
    const SYSEND_DEVICE_SET_URL = SYSEND_PREFIX."/device/set";                    //设置设备
    const SYSEND_DEVICE_DELETE_URL = SYSEND_PREFIX."/device/delete";              //删除设备
    const SYSEND_DEVICE_LIST_URL = SYSEND_PREFIX."/device/list";                  //设备信息
    const SYSEND_DEVICE_TEST_URL = SYSEND_PREFIX."/device/test";                  //设备应答
    const SYSEND_DEVICE_DISCOVER_URL = SYSEND_PREFIX."/device/discover";          //发现未知设备
    const SYSEND_DEVICE_UPDATE_URL = SYSEND_PREFIX."/device/update";              //APK升级

    // 设备组
    const SYSEND_SYSTEM_CREATE_GROUP_URL = SYSEND_PREFIX."/group/create";
    const SYSEND_SYSTEM_DELETE_GROUP_URL = SYSEND_PREFIX."/group/delete";
    const SYSEND_SET_DEFAULT_GROUP_URL = SYSEND_PREFIX."/group/set_default";      //设置设备组设备默认参数
    const SYSEND_GET_DEFAULT_GROUP_URL = SYSEND_PREFIX."/group/get_default";      //获取设备组设备默认参数

    // 流管理
    const SYSEND_CAMERA_ADD_URL = SYSEND_PREFIX."/camera/add";                    //流参数增加
    const SYSEND_CAMERA_EDIT_URL = SYSEND_PREFIX."/camera/edit";                  //流参数修改
    const SYSEND_CAMERA_DELETE_URL = SYSEND_PREFIX."/camera/delete";              //流参数删除

    // 人员
    const SYSEND_MEMBER_ADD_URL = SYSEND_PREFIX."/person/add";
    const SYSEND_MEMBER_EDIT_URL = SYSEND_PREFIX."/person/edit";
    const SYSEND_MEMBER_DELETE_URL = SYSEND_PREFIX."/person/delete";
    const SYSEND_MEMBER_ADD_IMAGE_URL = SYSEND_PREFIX."/person/add_image";
    const SYSEND_MEMBER_DELETE_IMAGE_URL = SYSEND_PREFIX."/person/delete_image";

    // 系统参数
    const SYSEND_SYSTEM_SET_URL = SYSEND_PREFIX."/system/set";
    const SYSEND_SYSTEM_GET_URL = SYSEND_PREFIX."/system/get";
    const SYSEND_SYSTEM_RESET_URL = SYSEND_PREFIX."/system/reset";


    const PERSON_ID = "person_id";

    const GROUP_ID = "group_id";
    const GROUP_IDS = "group_ids";

    const DEVICE_CODE = "device_code";
    const DEVICE_CODES = "device_codes";

    const DEVICE_PARAMS = "device_params";
    const CAMERA_PARAMS = "camera_params";

    const DEVICE_RESULT = "device_result";
    const DEVICE_RESULTS = "device_results";

    const TYPES = "types";

    private $deviceData = [
        self::DEVICE_CODES => [],
    ];

    private $testDeviceData = [
        self::DEVICE_CODE => [],
        self::TYPES => [
            DEVICE_TEST_LIGHT,
            DEVICE_TEST_VOICE
        ],
    ];

    private $updateDeviceData = [
        self::DEVICE_CODES => [],
        self::GROUP_IDS => [],
        "apk_url" => "",
        "etag" => "",
    ];

    private $groupData = [
        self::GROUP_ID => "",
    ];

    private $groupParamsData = [
        self::GROUP_IDS => "",
        self::DEVICE_PARAMS => [],
    ];

    private $streamData = [
        self::DEVICE_CODE => [],
        self::CAMERA_PARAMS => [],
    ];

    private $delStream = [
        self::DEVICE_CODE => [],
        "camera_ids" => [],
    ];

    public function __construct($url = [], $params = [])
    {
        $this->url = $url;
        $this->params = $params;
    }

    /**
     * 设置请求地址
     * @param $url
     */
    public function setUrl($url) {
        $this->url = $url;
    }

    /**
     * 获取请求地址
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * 设置请求参数
     * @param $params
     */
    public function setParams($params) {
        $this->params = $params;
    }

    /**
     * 获取请求参数
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * 添加设备
     * @param array $device_codes
     * @param string $group_id
     * @return array
     */
    public function addDevice($device_codes, $group_id)
    {
        $url = self::SYSEND_DEVICE_ADD_URL;
        $this->deviceData[self::DEVICE_CODES] = $device_codes;
        $this->deviceData[self::GROUP_IDS] = $group_id;

        $result = $this->sendSeetaDevice($url, $this->deviceData, "POST");

        //检查设备执行结果
        foreach ($result["device_results"] as $item) {
            if ($item['result'] != true) {
                Utility::log('logger', json_encode($result), __METHOD__, __LINE__);
                Utility::responseEarly(self::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));die;
            }
        }

        return $result;
    }

    /**
     * 设置设备
     * @param array $device_codes
     * @param string $group_id
     * @param array $device_params
     * @return array
     */
    public function setDevice($device_codes, $group_id = "", $device_params = [])
    {
        $url = self::SYSEND_DEVICE_SET_URL;
        $this->deviceData[self::DEVICE_CODES] = $device_codes;

        if (!empty($group_id)) {
            $this->deviceData[self::GROUP_IDS] = $group_id;
        }

        if (!empty($device_params)) {
            $this->deviceData[self::DEVICE_PARAMS] = $device_params;
        }

        $result = $this->sendSeetaDevice($url, $this->deviceData, "POST");
        return $result;
    }

    /**
     * 删除设备
     * @param array $device_codes
     * @return array
     */
    public function delDevice($device_codes)
    {
        $url = self::SYSEND_DEVICE_DELETE_URL;
        $this->deviceData[self::DEVICE_CODES] = $device_codes;

        $result = $this->sendSeetaDevice($url, $this->deviceData, "POST");

        //检查设备执行结果
        foreach ($result["device_results"] as $item) {
            if ($item['result'] != true) {
                Utility::log('logger', json_encode($result), __METHOD__, __LINE__);
                Utility::responseEarly(self::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));die;
            }
        }

        return $result;
    }

    /**
     * 获取设备信息
     * @param array $device_codes
     * @return array
     */
    public function listDevice($device_codes)
    {
        $url = self::SYSEND_DEVICE_LIST_URL;
        $this->deviceData[self::DEVICE_CODES] = $device_codes;

        $result = $this->sendSeetaDevice($url, $this->deviceData, "POST", true);
        return $result;
    }

    /**
     * 发现未知设备
     * @return array
     */
    public function discoverDevice()
    {
        $url = self::SYSEND_DEVICE_DISCOVER_URL;

        $result = $this->sendSeetaDevice($url);
        return $result;
    }

    /**
     * 设备APk升级
     * @param array $device_codes
     * @param array $group_ids
     * @param string $apk_url
     * @param string $etag
     * @return array
     */
    public function updateDevice($apk_url, $etag, $device_codes = [], $group_ids = [])
    {
        $url = self::SYSEND_DEVICE_UPDATE_URL;

        $this->updateDeviceData[self::DEVICE_CODES] = $device_codes;
        $this->updateDeviceData[self::GROUP_IDS] = $group_ids;
        $this->updateDeviceData["apk_url"] = $apk_url;
        $this->updateDeviceData["etag"] = $etag;

        $result = $this->sendSeetaDevice($url, $this->updateDeviceData, "POST");
        return $result;
    }

    /**
     * 设备应答
     * @param string $device_code
     * @return array
     */
    public function testDevice($device_code)
    {
        $url = self::SYSEND_DEVICE_TEST_URL;
        $this->testDeviceData[self::DEVICE_CODE] = $device_code;

        $result = $this->sendSeetaDevice($url, $this->testDeviceData, "POST");
        return $result;
    }


    /**
     * 创建设备组
     * @param string $group_id
     * @return array
     */
    public function createGroup($group_id)
    {
        $url = self::SYSEND_SYSTEM_CREATE_GROUP_URL;
        $this->groupData[self::GROUP_ID] = $group_id;

        $result = $this->sendSeetaDevice($url, $this->groupData, "POST", true);
        return $result;
    }

    /**
     * 删除设备组
     * @param string $group_id
     * @return array
     */
    public function deleteGroup($group_id)
    {
        $url = self::SYSEND_SYSTEM_DELETE_GROUP_URL;
        $this->groupData[self::GROUP_ID] = $group_id;

        $result = $this->sendSeetaDevice($url, $this->groupData, "POST");
        return $result;
    }

    /**
     * 设置设备组参数
     * @param array $group_ids
     * @param array|object $device_params
     * @return array
     */
    public function setGroupParams($group_ids, $device_params)
    {
        $url = self::SYSEND_SET_DEFAULT_GROUP_URL;
        $this->groupParamsData[self::GROUP_IDS] = $group_ids;
        $this->groupParamsData[self::DEVICE_PARAMS] = (object)$device_params;

        $result = $this->sendSeetaDevice($url, $this->groupParamsData, "POST");
        return $result;
    }

    /**
     * 获取设备组参数
     * @param array $group_ids
     * @param array $device_params
     * @return array
     */
    public function getGroupParams($group_ids, $device_params)
    {
        $url = self::SYSEND_GET_DEFAULT_GROUP_URL;
        $this->groupParamsData[self::GROUP_IDS] = $group_ids;
        $this->groupParamsData[self::DEVICE_PARAMS] = $device_params;

        $result = $this->sendSeetaDevice($url, $this->groupParamsData, "POST");
        return $result;
    }

    /**
     * 添加流媒体
     * @param string $device_code
     * @param array $camera_params
     * @return array
     */
    public function addStream($device_code, $camera_params)
    {
        $url = self::SYSEND_CAMERA_ADD_URL;
        $this->streamData[self::DEVICE_CODE] = $device_code;
        $this->streamData[self::CAMERA_PARAMS] = $camera_params;

        $result = $this->sendSeetaDevice($url, $this->streamData, "POST");
        if ($result["device_result"] != true) {
            Utility::responseEarly(self::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));die;
        }

        return $result;
    }

    /**
     * 编辑流媒体
     * @param string $device_code
     * @param array $camera_params
     * @return array
     */
    public function editStream($device_code, $camera_params)
    {
        $url = self::SYSEND_CAMERA_EDIT_URL;
        $this->streamData[self::DEVICE_CODE] = $device_code;
        $this->streamData[self::CAMERA_PARAMS] = $camera_params;

        $result = $this->sendSeetaDevice($url, $this->streamData, "POST");

        if ($result["device_result"] != true) {


            $log = new TErrorLog([
                "device_code" => $device_code,
                "level" => 1,
                "time" => time() * 1000,
                "content" => "流参数编辑失败",
            ]);

            $log->save();
        }


        return $result;
    }

    /**
     * 删除流媒体
     * @param string $device_code
     * @param array $camera_ids
     * @return array
     */
    public function delStream($device_code, $camera_ids)
    {
        $url = self::SYSEND_CAMERA_DELETE_URL;
        $this->delStream[self::DEVICE_CODE] = $device_code;
        $this->delStream["camera_ids"] = $camera_ids;

        $result = $this->sendSeetaDevice($url, $this->delStream, "POST");
        if ($result["device_result"] != true) {
            Utility::responseEarly(self::makeErrorResponse(ERR_DEVICE_OPERATION_WRONG));die;
        }

        return $result;
    }

    /**
     * 添加人员
     * @param array $post_data
     * @return array
     */
    public function addMember($post_data)
    {
        $url = self::SYSEND_MEMBER_ADD_URL;

        $result = $this->sendSeetaDevice($url, $post_data, "POST", true);
        return $result;
    }

    /**
     * 编辑人员
     * @param array $post_data
     * @return array
     */
    public function editMember($post_data)
    {
        $url = self::SYSEND_MEMBER_EDIT_URL;

        $result = $this->sendSeetaDevice($url, $post_data, "POST");
        return $result;
    }

    /**
     * 删除人员
     * @param string $person_id
     * @return array
     */
    public function delMember($person_id)
    {
        $url = self::SYSEND_MEMBER_DELETE_URL;
        $member_data = [self::PERSON_ID => $person_id];

        $result = $this->sendSeetaDevice($url, $member_data, "POST");
        return $result;
    }

    /**
     * 添加人员照片
     * @param string $person_id
     * @param string $image_url
     * @return array
     */
    public function addMemberImage($person_id, $image_url)
    {
        $url = self::SYSEND_MEMBER_ADD_IMAGE_URL;
        $member_data = [
            self::PERSON_ID => $person_id,
            "image_url" => $image_url,
        ];

        $result = $this->sendSeetaDevice($url, $member_data, "POST");
        return $result;
    }

    /**
     * 删除人员照片
     * @param string $person_id
     * @param string $image_id
     * @return array
     */
    public function delMemberImage($person_id, $image_id)
    {
        $url = self::SYSEND_MEMBER_DELETE_IMAGE_URL;
        $member_data = [
            self::PERSON_ID => $person_id,
            "image_id" => $image_id,
        ];

        $result = $this->sendSeetaDevice($url, $member_data, "POST");
        return $result;
    }

    /**
     * 设置系统参数
     * @param string $seetacloud_url
     * @param string $device_status_callback
     * @param string $register_image_callback
     * @param string $log_callback
     * @return array
     */
    public function setSystem($seetacloud_url, $device_status_callback, $register_image_callback, $log_callback)
    {
        $url = self::SYSEND_SYSTEM_SET_URL;

        $system_data = [
            "seetacloud_url" => $seetacloud_url,
            "device_status_callback" => $device_status_callback,
            "register_image_callback" => $register_image_callback,
            "log_callback" => ["url" => $log_callback, "level" => 1],
        ];

        $result = $this->sendSeetaDevice($url, $system_data, "POST");
        return $result;
    }

    /**
     * 获取系统参数
     * @return array
     */
    public function getSystem()
    {
        $url = self::SYSEND_SYSTEM_GET_URL;

        $result = $this->sendSeetaDevice($url);
        return $result;
    }

    /**
     * 重置系统
     * @param array $reset_data
     * @return array
     */
    public function resetSystem($reset_data)
    {
        $url = self::SYSEND_SYSTEM_RESET_URL;

        $result = $this->sendSeetaDevice($url, $reset_data, "POST");
        return $result;
    }

    /**
     * 向设备管理平台发送请求
     * @param string $url
     * @param array $data
     * @param string $method
     * @return array
     */
    public function sendRequest($method = "GET")
    {
        //向设备管理平台发送请求
        if ($method == "POST"){
            $result = Utility::sendUrlPost($this->url, $this->params);
        } else {
            $result = Utility::sendUrlGet($this->url);
        }

        $result = json_decode($result, true);
        if (!isset($result['res'])) {
            $result =  ['res' => ERR_SYNCHRO_POST_WRONG];
        }

        if ($result['res'] != ERR_SUCCESS){
            $result['res'] += self::SEETA_DEVICE_ERROR_CODE;
        }

        return $result;
    }

    /**
     * 处理结果
     * @param string $url
     * @param array $data
     * @param string $method
     * @param bool $is_continue
     * @return array
     */
    public function sendSeetaDevice($url, $data = [], $method = "GET", $is_continue = false)
    {
        //向设备管理平台发送请求
        if ($method == "POST"){
            $result = Utility::sendUrlPost($url, $data);
        } else {
            $result = Utility::sendUrlGet($url);
        }

        $result = json_decode($result, true);
        if (!isset($result['res'])) {
            if ($is_continue) {
                return ['res' => ERR_SYNCHRO_POST_WRONG];
            } else {
                Utility::responseEarly(self::makeErrorResponse(ERR_SYNCHRO_POST_WRONG));die;
            }
        }

        if ($result['res'] != ERR_SUCCESS){
            if ($is_continue) {
                return $result;
            } else {
                $result['res'] += self::SEETA_DEVICE_ERROR_CODE;
                Utility::responseEarly(self::makeErrorResponse($result['res']));die;
            }
        }

        return $result;
    }

    /**
     * 以错误码，返回错误信息
     *
     * @param integer $code
     * @return array
     */
    public function makeErrorResponse($code)
    {
        return array(
            MESSAGE => ERROR_MSGS[$code],
            CODE => $code
        );
    }
}