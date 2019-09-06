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
        } catch (\Exception $exception) {
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_UPLOAD_WRONG));
        }

        if (empty($system)) {
            $system = [
                "server_url" => "",
                "seetacloud_url" => "",
            ];
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            'api' => $system,
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

        if (empty($sessionId) || empty($serverUrl)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        $postData = [];
        $postData["device_status_callback"] = $serverUrl.DEVICE_STATUS_CALLBACK_URL;
        $postData["register_image_callback"] = $serverUrl.REGISTER_IMAGE_CALLBACK_URL;
        $postData["log_callback"] = ["url" => $serverUrl.LOG_CALLBACK_URL, "level" => 1];
        $postData["seetacloud_url"] = $seetaCloudUrl;

        Utility::log('logger', json_encode($postData), __METHOD__, __LINE__);

        $result = SeetaDeviceManager::sendRequest(SYSEND_SYSTEM_SET_URL, $postData);
        if (!is_array($result)) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        try {
            $system = TSystem::findSystem();
        } catch (\Exception $exception) {
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_UPLOAD_WRONG));
        }

        if (!empty($system)) {
            $system->server_url = $serverUrl;
            $system->seetacloud_url = $seetaCloudUrl;
        } else {
            $system = new TSystem([
                "server_url" => $serverUrl,
                "seetacloud_url" => $seetaCloudUrl,
            ]);
        }

        try {
            $system->save();
        } catch (\Exception $exception) {
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_UPLOAD_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }
}