<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use SeetaAiBuildingCommunity\Common\Manager\SeetaDeviceManager;
use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\TMember;
use SeetaAiBuildingCommunity\Models\TMemberImage;
use SeetaAiBuildingCommunity\Models\TSystem;

class RegisterController extends ControllerBase
{
    /*
     * 添加
     * */
    public function addAction()
    {
        $timestamp = $this->request->getPost("timestamp");
        $secretKey = $this->request->getPost("secret_key");

        $userId = $this->request->getPost("user_id");
        $image = $this->request->getPost("image");

        if (empty($timestamp) || empty($secretKey) || empty($userId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        if (md5(SIGN.(string)$timestamp) != $secretKey) {
            return parent::getResponse(parent::makeErrorResponse(ERR_AUTH_WRONG));
        }

        if (empty($image)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_MEMBER_IMAGE_EMPTY));
        }

        try {
            $member = TMember::findByUserId($userId);
            if (!empty($member)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_MEMBER_EXIST));
            }
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        $userId = !empty($userId) ? $userId : Utility::create_uuid();

        //本地添加人员
        $member = new TMember([
            "device_ids" => [],
            "group_ids" => [],
            "attributes" => (object)[],
            "user_id" => $userId,
            "status" => MEMBER_STATUS_VALID,
        ]);

        try{
            $member->save();
            $memberId = (string)$member->_id;

            $system = TSystem::findSystem();
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }


        //保存图片
        $imgPath = Utility::formatBase64ToImage($image, FILE_PATH_MEMBER_IMAGE);
        if (empty($imgPath)) {
            try{
                TMember::falseDeleteById($member->_id);
            } catch (\Exception $exception) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_WRITE_WRONG));
        }
        $imageUrl = Utility::filePathToDownloadUrl($system->server_url,$imgPath);

        //请求设备管理平台，添加人员
        $postData = [];
        $postData['person_id'] = $memberId;
        $postData['portrait_image'] = $imageUrl;

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

        //请求设备管理平台，添加人员照片
        $imageData = [];
        $imageData['person_id'] = $memberId;
        $imageData['image_url'] = $imageUrl;

        $seetaDeviceManager->setUrl(SeetaDeviceManager::SYSEND_MEMBER_ADD_IMAGE_URL);
        $seetaDeviceManager->setParams($imageData);
        $result = $seetaDeviceManager->sendRequest("POST");
        if ($result['res'] != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result['res']));
        }

        $imageId = $result['image_id'];

        //本地保存人员照片
        $image = new TMemberImage([
            "member_id" => $memberId,
            "image_id" => $imageId,
            "image_path" => $imgPath,
            "status" => MEMBER_IMAGE_STATUS_VALID,
        ]);
        try {
            $image->save();
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            'user_id' => $userId,
            'image_id' => $imageId,
        ));
    }

    /*
     * 编辑
     * */
    public function editAction()
    {
        $timestamp = $this->request->getPost("timestamp");
        $secretKey = $this->request->getPost("secret_key");

        $userId = $this->request->getPost("user_id");
        $image = $this->request->getPost("image");
        $delImageId = $this->request->getPost("del_image_id");

        if (md5(SIGN.(string)$timestamp) != $secretKey) {
            return parent::getResponse(parent::makeErrorResponse(ERR_AUTH_WRONG));
        }

        if (empty($userId) || empty($timestamp) || empty($secretKey)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        if (!empty($delImageId) && empty($image)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_MEMBER_IMAGE_EMPTY));
        }

        //新增人员照片
        try{
            $system = TSystem::findSystem();
            $member = TMember::findByUserId($userId);
            if (empty($member)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_MEMBER_NOT_EXIST));
            }
            $memberId = (string)$member->_id;

            $memberImages = TMemberImage::findByMemberId($memberId);
            if (!empty($memberImages)) {
                if (empty($delImageId) && !empty($image)) {
                    return parent::getResponse(parent::makeErrorResponse(ERR_MEMBER_IMAGE_OVER_MAX_NUM));
                }
            }

        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }
        $seetaDeviceManager = new SeetaDeviceManager();

        $postData = [];
        $postData['person_id'] = $memberId;

        //保存图片
        $imageId = "";
        if (!empty($image)) {
            $imgPath = Utility::formatBase64ToImage($image, FILE_PATH_MEMBER_IMAGE);
            if (empty($imgPath)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_FILE_WRITE_WRONG));
            }
            $imageUrl = Utility::filePathToDownloadUrl($system->server_url, $imgPath);

            //请求设备管理平台，添加人员照片
            $imageData = [];
            $imageData['person_id'] = $memberId;
            $imageData['image_url'] = $imageUrl;

            $seetaDeviceManager->setUrl(SeetaDeviceManager::SYSEND_MEMBER_ADD_IMAGE_URL);
            $seetaDeviceManager->setParams($imageData);
            $result = $seetaDeviceManager->sendRequest("POST");
            if ($result['res'] != ERR_SUCCESS) {
                return parent::getResponse(parent::makeErrorResponse($result['res']));
            }
            $imageId = $result['image_id'];

            $image = new TMemberImage([
                "member_id" => $memberId,
                "image_id" => $imageId,
                "image_path" => $imgPath,
                "status" => MEMBER_IMAGE_STATUS_VALID,
            ]);
            try{
                $image->save();
            } catch (\Exception $exception) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }

            $postData['portrait_image'] = $imageUrl;
        }

        if (!empty($delImageId)) {
            //请求设备管理平台，删除人员照片
            $delImageData = [];
            $delImageData['person_id'] = $memberId;
            $delImageData['image_id'] = $delImageId;

            $seetaDeviceManager->setUrl(SeetaDeviceManager::SYSEND_MEMBER_DELETE_IMAGE_URL);
            $seetaDeviceManager->setParams($delImageData);
            $result = $seetaDeviceManager->sendRequest("POST");
            if ($result['res'] != ERR_SUCCESS) {
                return parent::getResponse(parent::makeErrorResponse($result['res']));
            }

            try{
                //假删除本地数据
                TMemberImage::falseDeleteByImageId($delImageId);
            } catch (\Exception $exception) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }
        }

        //请求设备管理平台，编辑人员
        $seetaDeviceManager->setUrl(SeetaDeviceManager::SYSEND_MEMBER_EDIT_URL);
        $seetaDeviceManager->setParams($postData);
        $result = $seetaDeviceManager->sendRequest("POST");
        if ($result['res'] != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result['res']));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            'image_id' => $imageId,
        ));
    }

    /*
     * 获取
     * */
    public function getAction()
    {
        $timestamp = $this->request->getPost("timestamp");
        $secretKey = $this->request->getPost("secret_key");

        $userId = $this->request->getPost("user_id");

        if (empty($timestamp) || empty($secretKey)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        if (md5(SIGN.(string)$timestamp) != $secretKey) {
            return parent::getResponse(parent::makeErrorResponse(ERR_AUTH_WRONG));
        }

        if (empty($userId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        try {
            $system = TSystem::findSystem();
            $member = TMember::findByUserId($userId);
            if (empty($member)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_MEMBER_NOT_EXIST));
            }

            $images = [];
            $memberImages = TMemberImage::findByMemberId((string)$member->_id);
            foreach ($memberImages as $memberImage) {
                $image['id'] = $memberImage->image_id;
                $image['image_url'] = Utility::filePathToDownloadUrl($system->server_url, $memberImage->image_path);
                $images[] = $image;
            }
            $member->images = $image;
        } catch (\Exception $exception) {
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
        $timestamp = $this->request->getPost("timestamp");
        $secretKey = $this->request->getPost("secret_key");

        $userId = $this->request->getPost("user_id");

        if (empty($timestamp) || empty($secretKey)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        if (md5(SIGN.(string)$timestamp) != $secretKey) {
            return parent::getResponse(parent::makeErrorResponse(ERR_AUTH_WRONG));
        }

        if (empty($userId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        try {
            $member = TMember::findByUserId($userId);
            if (empty($member)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_MEMBER_NOT_EXIST));
            }
        } catch (\Exception $exception) {
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
            TMember::falseDeleteById($member->_id);
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