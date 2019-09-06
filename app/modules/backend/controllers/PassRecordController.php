<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use MongoDB\BSON\ObjectId;
use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\TDevice;
use SeetaAiBuildingCommunity\Models\TMember;
use SeetaAiBuildingCommunity\Models\TPassRecord;

class PassRecordController extends ControllerBase
{
    /*
     * 检索
     * */
    public function listAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $startIndex = $this->request->getPost("start_index");
        $getCount = $this->request->getPost("get_count");
        $deviceCode = $this->request->getPost("device_code");

        if (empty($sessionId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        $data = [];
        if (!empty($deviceCode)) {
            $data['device_code'] = $deviceCode;
        }

        $data['field'] = "time";
        $data['order'] = -1;

        try {
            $passRecords = TPassRecord::search($data, (int)$startIndex, (int)$getCount);
            $count = TPassRecord::searchCount($data);

            $passRecords = TPassRecord::addMoreInfo($passRecords);
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "pass_records" => $passRecords,
            "total" => $count,
        ));
    }

    /*
     * 统计人员每日早晚通行时间
     * */
    public function countRecordAction()
    {
        $sessionId = $this->request->getPost("session_id");

        $timeBegin = (int)$this->request->getPost("time_begin");
        $timeEnd = (int)$this->request->getPost("time_end") + 86400000;

        if (empty($sessionId) || empty($timeBegin) || empty($timeEnd)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        try {
            $personResult = TPassRecord::countCommuterTime($timeBegin, $timeEnd);

            $personIds = [];
            foreach ($personResult as $item) {
                if (strlen($item['_id']) == 24) {
                    $personIds[] = new ObjectId($item['_id']);
                }
            }

            $members = TMember::findByIds($personIds);
            $members = TMember::addAttributes($members);
            $attributes = array_keys((array)$members[0]->attributes);

            foreach ($personResult as $key => $value){
                foreach ($members as $member) {
                    if ($value['_id'] == (string)$member->id) {
                        $personResult[$key] = array_merge((array)$personResult[$key], (array)$member->attributes);
                    }
                }
            }

            $countResult = $this->count($personResult, $timeBegin, $timeEnd);
            $date = array_merge($attributes, $countResult['date']);
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
            "count_result" => $countResult['result'],
            "date" => $date,
        ));
    }

    /*
     * 接收1:1通行记录
     * */
    public function report11Action() {

    }

    /*
     * 接收1:n通行记录
     * */
    public function report1nAction()
    {
        $passInfo = json_decode($this->request->getRawBody(),true);

        $deviceCode = $passInfo["device_code"];
        $captureImage = $passInfo["capture_image"];
        $matches = $passInfo["matches"];
        $cameraId = $passInfo["camera_id"];
        $isPass = $passInfo["is_pass"];
        $timestamp = $passInfo["timestamp"];

        if (empty($deviceCode) || empty($captureImage) || empty($matches) || empty($cameraId) || empty($timestamp)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        $captureImage = str_replace('data:image/jpeg;base64,', '', $captureImage);
        $captureImage = str_replace(' ', '+', $captureImage);
        $data = base64_decode($captureImage);

        //现场抓拍图片存放目录
        $dir = FILE_PATH_CAPTURE_IMAGE . "/" . date("Y-M-d_H-i-s") . "/";
        if (!file_exists($dir)) {
            $res = mkdir($dir, 0755, true);
            if ($res == false){
                return parent::getResponse(parent::makeErrorResponse(ERR_FILE_WRITE_WRONG));
            }
        }

        //写入图片信息
        $imgPath = $dir.$matches[0]['person_id'].".jpg";
        if(@file_exists($imgPath)){
            @unlink($imgPath);
        }@clearstatcache();
        $fp=fopen($imgPath,'w');
        fwrite($fp,$data);
        fclose($fp);

        try {
            $device = TDevice::findByCode($deviceCode);
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //只取score得分最高的数据
        $person_id = "";
        $image_id = "";
        $score = 0;
        foreach ($matches as $match){
            if ($match['score'] > $score) {
                $person_id = $match['person_id'];
                $image_id = $match['image_id'];
                $score = $match['score'];
            }
        }

        $passRecord = new TPassRecord([
            "device_code" => $deviceCode,
            "device_name" => $device->name,
            "stream_id" => $cameraId,
            "person_id" => $person_id,
            "person_image_id" => $image_id,
            "capture_image_path" => $imgPath,
            "score" => round($score, 2),
            "matches" => $matches,
            "is_pass" => $isPass,
            "time" => $timestamp,
        ]);

        try {
            $passRecord->save();
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }

    /**
     * 统计考勤
     * @param array $result
     * @param integer $timeBegin
     * @param integer $timeEnd
     * @return array
     */
    public function count($result, $timeBegin, $timeEnd)
    {
        $day = (($timeEnd - $timeBegin) / 86400000 );

        $allDate = [];
        foreach ($result as $key => $person) {
            $startTime = $timeBegin;
            for ($i=1; $i <= $day; $i++) {
                $date = date('Y/m/d', (int)($startTime/1000));
                if ($key == 0) {
                    $allDate[] = $date;
                }
                $result[$key][$date] = ["", ""];
                $person['time'] = (array)$person['time'];
                asort($person['time']);
                foreach ($person['time'] as $item) {

                    if ($item >= $startTime && $item <= $startTime + 86400000) {
                        if (empty($result[$key][$date][0])) {
                            $result[$key][$date][0] = $item;
                        } else {
                            $result[$key][$date][1] = $item;
                        }
                    }
                }

                $dateRes = "";
                if (!empty($result[$key][$date][0])) {
                    $dateRes = $dateRes . date('H:i', $result[$key][$date][0]/1000);
                }
                if (!empty($result[$key][$date][1])) {
                    $dateRes = $dateRes . "\n" . date('H:i', $result[$key][$date][1]/1000);
                }
//                if (empty($dateRes)) {
//                    $dateRes = "--:--";
//                }
                $result[$key][$date] = $dateRes;

                $startTime += 86400000;
            }
            unset($result[$key]['time']);
        }

        return ["result" => $result, "date" => $allDate];
    }
}