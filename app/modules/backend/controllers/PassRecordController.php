<?php

namespace SeetaAiBuildingCommunity\Modules\Backend\Controllers;

use MongoDB\BSON\ObjectId;
use SeetaAiBuildingCommunity\Common\Manager\ExportManager;
use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\TDevice;
use SeetaAiBuildingCommunity\Models\TField;
use SeetaAiBuildingCommunity\Models\TMember;
use SeetaAiBuildingCommunity\Models\TMemberImage;
use SeetaAiBuildingCommunity\Models\TPassRecord;
use SeetaAiBuildingCommunity\Models\TStream;
use SeetaAiBuildingCommunity\Models\TSystem;

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
        $deviceName = $this->request->getPost("device_name");
        $field = $this->request->getPost("field");
        $timeBegin = $this->request->getPost("time_begin");
        $timeEnd = $this->request->getPost("time_end");

        if (empty($sessionId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }
        $field = json_decode($field, true);
        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        $data = [];

        //模糊搜索设备名称
        if (!empty($deviceName)) {
            try {
                $devices = TDevice::findByName($deviceName);
            } catch (\Exception $exception) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }

            if (empty($devices)) {
                return parent::getResponse(array(
                    CODE => ERR_SUCCESS,
                    "pass_records" => [],
                    "total" => 0,
                ));
            }

            $deviceCodes = [];
            foreach ($devices as $device) {
                $deviceCodes[] = $device['code'];
            }
            $data['device_code']['$in'] = $deviceCodes;
        }

        // 模糊字段搜索
        if (!empty($field) && is_array($field)) {
            $memberData = [];
            $memberData["attributes"] = $field;
            $memberData['projection'] = ["id" => 1];

            try {
                $members = TMember::search($memberData);
            } catch (\Exception $exception) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }

            if (empty($members)) {
                return parent::getResponse(array(
                    CODE => ERR_SUCCESS,
                    "pass_records" => [],
                    "total" => 0,
                ));
            }

            $memberIds = [];
            foreach ($members as $key => $val) {
                $memberIds[] = $val['id'];
            }
            $data['person_id']['$in'] = $memberIds;
        }

        if (!empty($timeBegin)) {
            $data['time_begin'] = (int)$timeBegin;
        }

        if (!empty($timeEnd)) {
            $data['time_end'] = (int)$timeEnd + 86400000;
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
     * 导出人员通行记录
     * */
    public function exportRecordAction()
    {
        $sessionId = $this->request->getPost("session_id");
        $fieldIds = $this->request->getPost("field_ids") ?: [];
        $deviceCodes = json_decode($this->request->getPost("device_codes")) ?: [];
        $timeBegin = (int)$this->request->getPost("time_begin");
        $timeEnd = (int)$this->request->getPost("time_end");

        if (empty($sessionId)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //安全性检验
        $result = parent::actionChecker($sessionId, $userType, $privateKey);
        if ($result != ERR_SUCCESS) {
            return parent::getResponse(parent::makeErrorResponse($result));
        }

        $data = [];
        if (!empty($deviceCodes)) {
            $data['device_code']['$in'] = $deviceCodes;
        }

        if (!empty($timeBegin)) {
            $data['time_begin'] = (int)$timeBegin;
        }

        if (!empty($timeEnd)) {
            $data['time_end'] = (int)$timeEnd + 86400000;
        }

        $data['field'] = "time";
        $data['order'] = -1;
        $fieldList = [];

        try {
            if (!empty($fieldIds)) {
                $fieldIds = json_decode($fieldIds, true);

                if (count($fieldIds) >= 1) {
                    foreach ($fieldIds as $fieldId) {
                        $ids[] = new ObjectId($fieldId);
                    }
                    $fields = TField::findByIds($ids);
                    foreach ($fields as $field) {
                        $fieldList[$field['name']] = "";
                    }
                }
            }

            $passRecords = TPassRecord::search($data);
            $count = TPassRecord::searchCount($data);

        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }


        $excelName = "pass_record";
        $export = new ExportManager($excelName);
        if ($count < 1) {
            $exportData = [];
        } else {
            $passRecords = TPassRecord::addMoreInfo($passRecords);

            $export->seTitle([]);
            $exportData = [];
            foreach ($passRecords as $key => $passRecord) {
                $attributes = $fieldList;

                $exportData[$key]['A'] = $passRecord['device_name'];
                $exportData[$key]['B'] = $passRecord['device_code'];
                $memberAttribute = "";
                if (isset($passRecord['attributes']) && count($passRecord['attributes']) >= 1) {
                    foreach ($passRecord['attributes'] as $attributeKey => $val) {
                        if (isset($attributes[$attributeKey])) {
                            $memberAttribute .= $attributeKey . ":" . $val . "   \n" ;
                        }
                    }
                }
                $exportData[$key]['C'] = substr($memberAttribute,0,-2);
                $exportData[$key]['D'] = $passRecord['stream_id'];
                $exportData[$key]['E'] = $passRecord['score'];
                $exportData[$key]['F'] = $passRecord['is_pass'] == VERIFICATION_IS_PASS ? "通过" : "不通过";
                $exportData[$key]['G'] = date("Y-m-d   H:i:s", $passRecord['time'] / 1000);
            }
        }

        $export->data = $exportData;
        $export->exportMemberRecordExcel();
    }

    /*
     * 统计人员每日早晚通行时间
     * */
    public function statisticsRecordAction()
    {
        $sessionId = $this->request->getPost("session_id");
        $fieldIds = $this->request->getPost("field_ids");
        $deviceCodes = json_decode($this->request->getPost("device_codes")) ?: [];
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

        if (!empty($fieldIds)) {
            $fieldIds = json_decode($fieldIds, true);
            if (count($fieldIds) < 1) {
                $fieldIds = [];
            }
        } else {
            $fieldIds = [];
        }

        try {
            $personResult = TPassRecord::countCommuterTime($deviceCodes, $timeBegin, $timeEnd);
            $personIds = [];
            foreach ($personResult as $item) {
                if (strlen($item['_id']) == 24) {
                    $personIds[] = new ObjectId($item['_id']);
                }
            }

            $members = TMember::findByIds($personIds);
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }


        $members = TMember::addAttributes($fieldIds, $members);
        $attributes = array_keys((array)$members[0]->attributes);

        foreach ($members as $key => $member) {
            foreach ($personResult as $value) {
                if ($value['_id'] == (string)$member->id) {
                    $members[$key]['time'] = $value['time'];
                }
            }
            unset($members[$key]['id']);
        }

        $countResult = $this->count($members, $timeBegin, $timeEnd);
        $excelName = "count_record";
        $export = new ExportManager($excelName);
        $title = array_merge($attributes, $countResult['date']);
        $export->seTitle($title);
        $export->data = $countResult['result'];
        $export->statisticsRecordRecordExcel();

    }

    /*
     * 接收1:1通行记录
     * */
    public function report11Action()
    {
        $passInfo = json_decode($this->request->getRawBody(), true);
        Utility::log('logger', "通行1：1", __METHOD__, __LINE__);

        $deviceCode = $passInfo["device_code"];
        $captureImage = $passInfo["capture_image"];
        $score = $passInfo["score"];
        $cardId = $passInfo["card_id"];
        $cardName = $passInfo["card_name"];
        $cardImage = $passInfo["card_image"];
        $cameraId = $passInfo["camera_id"];
        $isPass = $passInfo["is_pass"];
        $timestamp = $passInfo["timestamp"];

        if (empty($deviceCode) || empty($captureImage) || !isset($score) || empty($cardImage) || empty($cardId) || empty($isPass) || empty($cameraId) || empty($timestamp)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //存储现场抓拍图片
        $cardImgPath = Utility::base64ToImage($cardImage, FILE_PATH_CARD_IMAGE);
        $captureImgPath = Utility::base64ToImage($captureImage, FILE_PATH_CAPTURE_IMAGE);

        if (empty($cardImgPath) || empty($captureImgPath)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_WRITE_WRONG));
        }

        try {
            //查找系统中的证件字段
            $system = TSystem::findSystem();
            $cardFieldId = $system->card_field;

            $device = TDevice::findByCode($deviceCode);
            $member = TMember::findByCardId($cardFieldId,$deviceCode);

            $personId = !empty($member) ? (string)$member->_id : "";
            if (!empty($personId)) {
                $memberImages = TMemberImage::findByMemberId($personId);
            }
            $personImageId = !empty($memberImages) ? (string)$memberImages[0]->_id : "";
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //若未传递是否通过字段，则对比流参数的可信阈值，进行判断
        if (empty($isPass)) {
            try {
                $stream = TStream::findById(new ObjectId($cameraId));
                if (empty($stream)) {
                    return parent::getResponse(parent::makeErrorResponse(ERR_STREAM_NOT_EXIST));
                }
            } catch (\Exception $exception) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }
            $confidence = $stream->camera_params['confidence'];

            if ($score >= $confidence) {
                $isPass = VERIFICATION_IS_PASS;
            } else {
                $isPass = VERIFICATION_NOT_PASS;
            }
        }

        $matches = [
            "card_id" => $cardId,
            "card_name" => $cardName,
            "card_Image_path" => $cardImgPath,
        ];

        $passRecord = new TPassRecord([
            "device_code" => $deviceCode,
            "device_name" => $device->name,
            "stream_id" => $cameraId,
            "person_id" => $personId,
            "person_image_id" => $personImageId,
            "capture_image_path" => $captureImgPath,
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

        Utility::responseEarly(array(
            CODE => ERR_SUCCESS,
        ));


        //检测第三方上报接口是否存在
        $report_url = $device->report_11;
        if (!empty($report_url)) {
            $result = Utility::sendUrlPost($report_url, $passInfo);
            $result = json_decode($result);
            if (empty($result)) {
                Utility::log('logger', "上报通行记录请求失败", __METHOD__, __LINE__);
            }
        }

        return parent::getResponse(array(
            CODE => ERR_SUCCESS,
        ));
    }

    /*
     * 接收1:n通行记录
     * */
    public function report1nAction()
    {
        $passInfo = json_decode($this->request->getRawBody(), true);
        Utility::log('logger', "通行1：n", __METHOD__, __LINE__);
        $deviceCode = $passInfo["device_code"];
        $captureImage = $passInfo["capture_image"];
        $matches = $passInfo["matches"];
        $cameraId = $passInfo["camera_id"];
        $isPass = $passInfo["is_pass"];
        $timestamp = $passInfo["timestamp"];

        if (empty($deviceCode) || empty($captureImage) || empty($matches) || empty($cameraId) || empty($timestamp)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_PARAM_WRONG));
        }

        //存储现场抓拍图片
        $imgPath = Utility::base64ToImage($captureImage, FILE_PATH_CAPTURE_IMAGE);
        if (empty($imgPath)) {
            return parent::getResponse(parent::makeErrorResponse(ERR_FILE_WRITE_WRONG));
        }

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
        foreach ($matches as $match) {
            if ($match['score'] > $score) {
                $person_id = $match['person_id'];
                $image_id = $match['image_id'];
                $score = $match['score'];
            }
        }

        //若未传递是否通过字段，则对比流参数的可信阈值，进行判断
        if (empty($isPass)) {
            try {
                $stream = TStream::findById(new ObjectId($cameraId));
                if (empty($stream)) {
                    return parent::getResponse(parent::makeErrorResponse(ERR_STREAM_NOT_EXIST));
                }
            } catch (\Exception $exception) {
                //数据库出错
                Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
                return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
            }
            $confidence = $stream->camera_params['confidence'];

            if ($score >= $confidence) {
                $isPass = VERIFICATION_IS_PASS;
            } else {
                $isPass = VERIFICATION_NOT_PASS;
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

        Utility::responseEarly(array(
            CODE => ERR_SUCCESS,
        ));

        try {
            $person = TMember::findById(new ObjectId($person_id));
            if (empty($person)) {
                return parent::getResponse(parent::makeErrorResponse(ERR_MEMBER_NOT_EXIST));
            }
        } catch (\Exception $exception) {
            //数据库出错
            Utility::log('logger', $exception->getMessage(), __METHOD__, __LINE__);
            return parent::getResponse(parent::makeErrorResponse(ERR_DB_WRONG));
        }

        //检测第三方上报接口是否存在
        $report_url = $device->report_1n;
        if (!empty($report_url)) {
            $result = Utility::sendUrlPost($report_url, $passInfo);
            $result = json_decode($result);
            if (empty($result)) {
                Utility::log('logger', "上报通行记录请求失败", __METHOD__, __LINE__);
            }
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
        $day = (($timeEnd - $timeBegin) / 86400000);
        $allDate = [];
        foreach ($result as $key => $person) {

            if (count($person['attributes']) >= 1) {
                foreach ($person['attributes'] as $filed => $val) {
                    $result[$key][$filed] = $val;
                }
            }


            $startTime = $timeBegin;
            for ($i = 1; $i <= $day; $i++) {
                $date = date('Y-m-d', (int)($startTime / 1000));
                if ($key == 0) {
                    $allDate[] = $date;
                }
                $result[$key][$date] = "";
                $person['time'] = (array)$person['time'];
                asort($person['time']);
                if (count($person['time']) < 1) {
                    $startTime += 86400000;
                    continue;
                }

                $data = [];
                $tamp = array_filter($person['time'], function ($element) use ($startTime) {
                    return ($element >= $startTime) && ($element <= $startTime + 86400000);
                });
                if (count($tamp) >= 2) {
                    $data[$key][$date][0] = reset($tamp);
                    $data[$key][$date][1] = end($tamp);
                } elseif (1 <= count($tamp) && count($tamp) < 2) {
                    $data[$key][$date][0] = reset($tamp);
                }

                $dateRes = "";
                if (!empty($data[$key][$date][0])) {
                    $dateRes = $dateRes . date('H:i', $data[$key][$date][0] / 1000);
                }
                if (!empty($data[$key][$date][1])) {
                    $dateRes = $dateRes .  " \n"  . date('H:i', $data[$key][$date][1] / 1000);
                }
                $result[$key][$date] = $dateRes;
                $startTime += 86400000;
            }
            unset($result[$key]['attributes']);
            unset($result[$key]['time']);
        }

        return ["result" => $result, "date" => $allDate];
    }
}