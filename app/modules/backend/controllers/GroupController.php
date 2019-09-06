<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use MongoDB\BSON\ObjectId;
use SeetaAiBuildingCommunity\Common\Manager\SeetaDeviceManager;
use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\TDevice;
use SeetaAiBuildingCommunity\Models\TGroup;
use SeetaAiBuildingCommunity\Models\TMember;

class GroupController extends ControllerBase
{
    /*
     * 添加
     * */
    public function addAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $name = $this->request->getPost("name");

        if (empty($sessionId) || empty($name)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        //设备默认参数
        $deviceParams = [
            "voice_switch" => DEVICE_DEFAULT_VOICE_SWITCH,
            "volume" => DEVICE_DEFAULT_VOLUME,
            "log_level" => DEVICE_DEFAULT_LOG_LEVEL,
        ];

        //本地添加设备组
        $group = new TGroup([
            "name" => $name,
            "device_params" => $deviceParams,
            "status" => GROUP_STATUS_VALID,
        ]);

        try{
            $group->save();
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //请求安卓设备管理平台同步添加设备组
        $postData = [];
        $postData['group_id'] = (string)$group->_id;

        $result = SeetaDeviceManager::sendRequest(SYSEND_SYSTEM_CREATE_GROUP_URL, $postData);
        if (!is_array($result)) {
            //同步失败，删除本地数据
            try {
                //假删除
                TGroup::falseDeleteById($group->_id);
            } catch (\Exception $exception) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }

            return parent::getResponse(parent::makeErrorResponse($result));
        }

        //向设备管理平台请求同步数据
        $postData = [];
        $postData['group_ids'] = [(string)$group->_id];
        $postData['device_params'] = $deviceParams;

        $result = SeetaDeviceManager::sendRequest(SYSEND_SET_DEFAULT_GROUP_URL, $postData);
        if (!is_array($result)) {
            return parent::getResponse(parent::makeErrorResponse($result));
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
        $deviceParams = json_decode($this->request->getPost("device_params")) ?: (object)[];

        if (empty($sessionId) || empty($id)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        try {
            $group = TGroup::findById(new ObjectId($id));
            if (empty($group)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_GROUP_NOT_EXIST));
            }
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //向设备管理平台请求同步数据
        $postData = [];
        $postData['group_ids'] = [$id];
        $postData['device_params'] = $deviceParams;

        $result = SeetaDeviceManager::sendRequest(SYSEND_SET_DEFAULT_GROUP_URL, $postData);
        if (!is_array($result)) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        //保存本地数据
        try {
            $group->name = $name;
            $group->device_params = $deviceParams;
            $group->save();
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
            $group = TGroup::findById(new ObjectId($id));
            if (empty($group)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_GROUP_NOT_EXIST));
            }
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "group" => $group,
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
            //存在设备的设备组不允许删除
            $devices = TDevice::findByGroupId($id);
            if (!empty($devices)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_GROUP_DEVICE_EXIST));
            }

            $group = TGroup::findById(new ObjectId($id));
            if (empty($group)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_GROUP_NOT_EXIST));
            }
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //向设备管理平台请求同步数据
        $postData = [];
        $postData['group_id'] = $id;

        $result = SeetaDeviceManager::sendRequest(SYSEND_SYSTEM_DELETE_GROUP_URL, $postData);
        if (!is_array($result)) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        try {
            //假删除设备组
            TGroup::falseDeleteById(new ObjectId($id));

            //删除掉人员的中的此设备组
            TMember::deleteGroupId($id);
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
            $groups = TGroup::search($data, (int)$startIndex, (int)$getCount);
            $count = TGroup::searchCount($data);

            $groups = TGroup::addMoreInfo($groups);
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "groups" => $groups,
            "total" => $count,
        ));
    }
}