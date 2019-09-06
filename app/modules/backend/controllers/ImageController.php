<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use SeetaAiBuildingCommunity\Common\Manager\ImageManager;
use SeetaAiBuildingCommunity\Common\Manager\Utility;

class ImageController extends ControllerBase
{
    public function getAction()
    {
        $imageKey = $this->request->get("image_key");

        //验证必要参数是否为空
        if (empty($imageKey)) {
            return $this->return404();
        }

        $imagePathManager = new ImageManager();
        //验证image key
        if(empty($path = $imagePathManager->getImageKey($imageKey))){
            return $this->return404();
        }

        $res = preg_split('/\./', $path);
        $extension = $res[count($res) - 1];
        if ($extension == 'jpg') {
            $extension = 'jpeg';
        }

        $fp = fopen($path, "r");
        if (!$fp) {
            return $this->return404();
        }
        $file_size = filesize($path);

        Header("Content-type: image/" . $extension);
        Header("Accept-Ranges: bytes");
        Header("Accept-Length:" . $file_size);
        Header("Content-Disposition: attachment; filename=" . Utility::generateRandomCode(7).".".$extension);

        Header("Access-Control-Allow-Origin:*");
        Header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, OPTIONS');
        Header("Access-Control-Allow-Headers:x-requested-with,content-type");
        $buffer = 1024;
        $file_count = 0;

        while (!feof($fp) && $file_count < $file_size) {
            $file_con = fread($fp, $buffer);
            $file_count += $buffer;
            echo $file_con;
        }
        fclose($fp);

        exit;
    }

    public function return404()
    {
        $msg = Utility::makeText(LOG_IMAGE_READ_NOT_NORMAL, [$this->request->getHeader("X-real-ip")]);
        Utility::log('secure_logger', $msg, __METHOD__, __LINE__);
        $response = $this->response;
        $response->setStatusCode(404, "NOT FOUND");
        return $response;
    }
}

