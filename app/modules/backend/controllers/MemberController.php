<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use MongoDB\BSON\ObjectId;
use SeetaAiBuildingCommunity\Common\Manager\SeetaDeviceManager;
use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\TDevice;
use SeetaAiBuildingCommunity\Models\TField;
use SeetaAiBuildingCommunity\Models\TMember;
use SeetaAiBuildingCommunity\Models\TMemberImage;
use SeetaAiBuildingCommunity\Models\TSystem;

class MemberController extends ControllerBase
{
    /*
     * 添加
     * */
    public function addAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $deviceIds = json_decode($this->request->getPost("device_ids")) ?: [];
        $groupIds = json_decode($this->request->getPost("group_ids")) ?: [];
        $attributes = json_decode($this->request->getPost("attributes")) ?: [];

        if (empty($sessionId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        $imageNum = count($this->request->getUploadedFiles());
        if ($imageNum > MEMBER_IMAGE_UPLOAD_MAX) {
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_UPLOAD_WRONG));
        }

        if (empty($deviceIds) && empty($groupIds) && empty($attributes) && $imageNum < 1) {
            return parent::getResponse(parent::makeErrorResponse(ERR_MEMBER_INFO_NOT_EXIST));
        }

        //字段信息，和照片信息不可以都为空
        try {
            $fields = TField::search([]);
            $system = TSystem::findSystem();
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        if (empty($fields) && $imageNum < 1) {
            return parent::getResponse(parent::makeErrorResponse(ERR_MEMBER_FIELD_AND_IMAGE_NOT_EXIST));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        //添加人员照片
        $imageInfo = [];
        $dir = FILE_PATH_MEMBER_IMAGE . "/" . date("Y-M-d_H-i-s") . "/";
        for ($i = 1; $i <= $imageNum; $i++) {
            $uploadFileName = "image" . $i;
            $filename = "";
            try {
                $fullPath = $this->uploadFile($dir, $uploadFileName, $filename);
                $image_url = Utility::filePathToDownloadUrl($system->server_url, $fullPath);
            } catch ( \Exception $exception ) {
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_FILE_UPLOAD_WRONG));
            }

            $image = [];
            $image['image_path'] = $fullPath;
            $image['image_url'] = $image_url;
            $imageInfo[] = $image;
        }

        //本地添加人员
        $member = new TMember([
            "device_ids" => $deviceIds,
            "group_ids" => $groupIds,
            "attributes" => $attributes,
            "status" => MEMBER_STATUS_VALID,
        ]);

        try {
            $member->save();
            foreach ($deviceIds as $key => $deviceId) {
                $deviceIds[$key] = new ObjectId($deviceId);
            }
            $devices = TDevice::findByIds($deviceIds);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }
        $memberId = (string)$member->_id;
        $deviceCodes = [];
        foreach ($devices as $device) {
            $deviceCodes[] = $device->code;
        }

        //人员动态字段
        $fieldIds = [];
        foreach ($attributes as $fieldId => $fieldValue) {
            $fieldIds[] = new ObjectId($fieldId);
        }
        try {
            $fields = TField::findByIds($fieldIds);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        $postAttributes = [];
        foreach ($attributes as $fieldId => $fieldValue) {
            foreach ($fields as $key => $field) {
                if ($fieldId == (string)$field->id) {
                    $postAttributes[$field->name] = $fieldValue;
                }
                //设置硬件显示字段
                if (!empty($system->show_field)) {
                    foreach ($system->show_field as $value) {
                        if ($value == (string)$field->id) {
                            $subtitle_pattern = "<" . $field->name . ">";
                        }
                    }
                }
            }
        }

        //请求设备管理平台，添加人员
        $postData = [];
        $postData['person_id'] = $memberId;
        $postData['group_ids'] = $groupIds;
        $postData['device_codes'] = $deviceCodes;
        $postData['attributes'] = (object)$postAttributes;
        if (!empty($subtitle_pattern)) {
            $postData['subtitle_pattern'] = [$subtitle_pattern];
        }
        if (!empty($imageInfo)) {
            $postData['portrait_image'] = $imageInfo[0]['image_url'];
        }

        $seetaDeviceManager = new SeetaDeviceManager(SeetaDeviceManager::SYSEND_MEMBER_ADD_URL, $postData);
        $result = $seetaDeviceManager->sendRequest("POST");
        if ($result["res"] != ERR_SUCCESS) {
            //同步失败，删除本地数据
            try {
                TMember::falseDeleteById($member->_id);
            } catch ( \Exception $exception ) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }
            return parent::getResponse(parent::makeErrorResponse($result["res"]));
        }

        foreach ($imageInfo as $image) {
            //请求设备管理平台，添加人员照片
            $imageData = [];
            $imageData['person_id'] = $memberId;
            $imageData['image_url'] = $image['image_url'];

            $seetaDeviceManager->setUrl(SeetaDeviceManager::SYSEND_MEMBER_ADD_IMAGE_URL);
            $seetaDeviceManager->setParams($imageData);
            $result = $seetaDeviceManager->sendRequest("POST");
            if ($result['res'] != ERR_SUCCESS) {
                return parent::getResponse(parent::makeErrorResponse($result['res']));
            }

            //本地保存人员照片
            $image = new TMemberImage([
                "member_id" => $memberId,
                "image_id" => $result['image_id'],
                "image_path" => $image['image_path'],
                "status" => MEMBER_IMAGE_STATUS_VALID,
            ]);
            try {
                $image->save();
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
        $deviceIds = json_decode($this->request->getPost("device_ids")) ?: [];
        $groupIds = json_decode($this->request->getPost("group_ids")) ?: [];
        $delImageIds = json_decode($this->request->getPost("del_image_ids"));
        $attributes = json_decode($this->request->getPost("attributes")) ?: [];
        $imageNum = count($this->request->getUploadedFiles());
        if (empty($sessionId) || empty($id)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        //新增人员照片
        try {
            $system = TSystem::findSystem();
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        $dir = FILE_PATH_MEMBER_IMAGE . "/" . date("Y-M-d_H-i-s") . "/";

        $seetaDeviceManager = new SeetaDeviceManager();

        if ($imageNum >= 1) {
            for ($i = 1; $i <= $imageNum; $i++) {
                $uploadFileName = "image" . $i;
                $filename = "";
                try {
                    $fullPath = $this->uploadFile($dir, $uploadFileName, $filename);
                    $imageUrl = Utility::filePathToDownloadUrl($system->server_url, $fullPath);
                } catch ( \Exception $exception ) {
                    Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                    return parent::getResponse(parent::makeErrorResponse(ERR_FILE_UPLOAD_WRONG));
                }

                //请求设备管理平台，添加人员照片
                $imageData = [];
                $imageData['person_id'] = $id;
                $imageData['image_url'] = $imageUrl;

                $seetaDeviceManager->setUrl(SeetaDeviceManager::SYSEND_MEMBER_ADD_IMAGE_URL);
                $seetaDeviceManager->setParams($imageData);
                $result = $seetaDeviceManager->sendRequest("POST");
                if ($result['res'] != ERR_SUCCESS) {
                    return parent::getResponse(parent::makeErrorResponse($result['res']));
                }

                $image = new TMemberImage([
                    "member_id" => $id,
                    "image_id" => $result['image_id'],
                    "image_path" => $fullPath,
                    "status" => MEMBER_IMAGE_STATUS_VALID,
                ]);
                try {
                    $image->save();
                } catch ( \Exception $exception ) {
                    //数据库出错
                    Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                    return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
                }
            }
        }

        foreach ($delImageIds as $imageId) {
            //请求设备管理平台，删除人员照片
            $delImageData = [];
            $delImageData['person_id'] = $id;
            $delImageData['image_id'] = $imageId;

            $seetaDeviceManager->setUrl(SeetaDeviceManager::SYSEND_MEMBER_DELETE_IMAGE_URL);
            $seetaDeviceManager->setParams($delImageData);
            $result = $seetaDeviceManager->sendRequest("POST");
            if ($result['res'] != ERR_SUCCESS) {
                return parent::getResponse(parent::makeErrorResponse($result['res']));
            }

            try {
                //假删除本地数据
                TMemberImage::falseDeleteByImageId($imageId);
            } catch ( \Exception $exception ) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }
        }

        try {
            $member = TMember::findById(new ObjectId($id));
            if (empty($member)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_MEMBER_NOT_EXIST));
            }
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //设备变动
        $postData = [];
        if ((array)$member->device_ids != (array)$deviceIds) {
            $deviceObjectIds = [];
            foreach ($deviceIds as $deviceId) {
                $deviceObjectIds[] = new ObjectId($deviceId);
            }

            try {
                $devices = TDevice::findByIds($deviceObjectIds);
            } catch ( \Exception $exception ) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }

            $deviceCodes = [];
            foreach ($devices as $device) {
                $deviceCodes[] = $device->code;
            }
            $postData['device_codes'] = $deviceCodes;
        }

        //设备组变动
        if ((array)$member->group_ids != (array)$groupIds) {
            $postData['group_ids'] = $groupIds;
        }

        //人员动态字段变动
        if ((array)$member->attributes != (array)$attributes) {
            $fieldIds = [];
            foreach ($attributes as $fieldId => $fieldValue) {
                $fieldIds[] = new ObjectId($fieldId);
            }
            try {
                $fields = TField::findByIds($fieldIds);
            } catch ( \Exception $exception ) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }

            $postAttributes = [];
            foreach ($attributes as $fieldId => $fieldValue) {
                foreach ($fields as $key => $field) {
                    if ($fieldId == (string)$field->id) {
                        $postAttributes[$field->name] = $fieldValue;
                    }
                }
                $postData['attributes'] = (object)$postAttributes;
            }
        }

        // 人员照片变动,更改人员头像
        if (!empty($imageNum) || $delImageIds) {
            try {
                $memberImages = TMemberImage::findByMemberId($id);
            } catch ( \Exception $exception ) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }
            $portrait_image = "";
            if (!empty($memberImages)) {
                $portrait_image = Utility::filePathToDownloadUrl($system->server_url, $memberImages[0]->image_path);
            }
            $postData['portrait_image'] = $portrait_image;
        }


        //请求设备管理平台，编辑人员
        if (!empty($postData)) {
            $postData['person_id'] = $id;

            $seetaDeviceManager->setUrl(SeetaDeviceManager::SYSEND_MEMBER_EDIT_URL);
            $seetaDeviceManager->setParams($postData);
            $result = $seetaDeviceManager->sendRequest("POST");
            if ($result['res'] != ERR_SUCCESS) {
                return parent::getResponse(parent::makeErrorResponse($result['res']));
            }

            //本地保存数据
            try {
                $member->group_ids = $groupIds;
                $member->device_ids = $deviceIds;
                $member->attributes = $attributes;
                $member->save();
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
            $member = TMember::findById(new ObjectId($id));
            if (empty($member)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_MEMBER_NOT_EXIST));
            }
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "member" => $member,
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
            $member = TMember::findById(new ObjectId($id));
            if (empty($member)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_MEMBER_NOT_EXIST));
            }
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //请求设备管理平台，删除人员
        $postData = [];
        $postData['person_id'] = (string)$member->_id;

        $seetaDeviceManager = new SeetaDeviceManager(SeetaDeviceManager::SYSEND_MEMBER_DELETE_URL,$postData);
        $result = $seetaDeviceManager->sendRequest("POST");
        if ($result['res'] != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result['res']));
        }

        try {
            //假删除
            TMember::falseDeleteById(new ObjectId($id));
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
        $field = $this->request->getPost("field");

        if (empty($sessionId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }
        $data = [];
        $field = json_decode($field, true);
        if (!empty($field)) {
            if (is_array($field)) {
                $data["attributes"] = $field;
            }
        }
        $data['field'] = "_id";
        $data['order'] = -1;
        try {
            $members = TMember::search($data, (int)$startIndex, (int)$getCount);
            $count = TMember::searchCount($data);

            //添加人员更多信息
            $members = TMember::addMoreInfo($members);
        } catch ( \Exception $exception ) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "members" => $members,
            "total" => $count
        ));
    }
}