<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use SeetaAiBuildingCommunity\Common\Manager\SeetaDeviceManager;
use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\TSystem;

class ConfigController extends ControllerBase
{
    /**
     * 获取配置
     */
    public function getAction()
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

        try {
            $system = TSystem::findSystem();
        } catch ( \Exception $exception ) {
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_UPLOAD_WRONG));
        }

        $id = (string)$system->_id ?: "";
        $serverUrl = $system->server_url ?: "";
        $seetacloudUrl = $system->seetacloud_url ?:"";
        $personId = $system->person_id ?:"";
        $showField = $system->show_field ?:"";

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "id" => $id,
            "server_url" => $serverUrl,
            "seetacloud_url" => $seetacloudUrl,
            "person_id" => $personId,
            "show_field" => $showField,
        ));
    }

    /**
     * 修改配置
     */
    public function setAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $serverUrl = $this->request->getPost("server_url");
        $seetaCloudUrl = $this->request->getPost("seetacloud_url") ?: "";
        $personId = $this->request->getPost("person_id") ?: "";
        $showField = json_decode($this->request->getPost("show_field")) ?: [];
        $cardField = $this->request->getPost("card_field") ?: "";

        if (empty($sessionId) || empty($serverUrl)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        //请求设备管理平台编辑系统配置
        $postData = [];
        $postData["seetacloud_url"] = $seetaCloudUrl;
        $postData["device_status_callback"] = $serverUrl.DEVICE_STATUS_CALLBACK_URL;
        $postData["register_image_callback"] = $serverUrl.REGISTER_IMAGE_CALLBACK_URL;
        $postData["log_callback"] = ["url" => $serverUrl.LOG_CALLBACK_URL, "level" => 1];

        $seetaDeviceManager = new SeetaDeviceManager(SeetaDeviceManager::SYSEND_SYSTEM_SET_URL, $postData);
        $seetaDeviceManager->sendRequest("POST");

        try {
            $system = TSystem::findSystem();
        } catch ( \Exception $exception ) {
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_UPLOAD_WRONG));
        }

        if (!empty($system)) {
            $system->server_url = trim($serverUrl);
            $system->seetacloud_url = $seetaCloudUrl;
            $system->person_id = $personId;
            $system->show_field = $showField;
            $system->card_field = $cardField;
        } else {
            $system = new TSystem(array(
                "server_url" => $serverUrl,
                "seetacloud_url" => $seetaCloudUrl,
                "person_id" => $personId,
                "show_field" => $showField,
                "card_field" => $cardField,
            ));
        }

        try {
            $system->save();
        } catch ( \Exception $exception ) {
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_UPLOAD_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }
}