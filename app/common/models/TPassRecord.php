<?php

namespace SeetaAiBuildingCommunity\Models;

use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Regex;
use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\Base\ModelHandler;
use SeetaAiBuildingCommunity\Models\Traits\ModelTraitBase;

class TPassRecord extends ModelHandler
{

    use ModelTraitBase;

    /**
     *
     * @var ObjectId
     */
    public $_id;

    /**
     *
     * @var string
     */
    public $device_code;

    /**
     *
     * @var string
     */
    public $device_name;

    /**
     *
     * @var string
     */
    public $stream_id;

    /**
     *
     * @var string
     */
    public $person_id;

    /**
     *
     * @var string
     */
    public $person_image_id;

    /**
     *
     * @var string
     */
    public $capture_image_path;

    /**
     *
     * @var array
     */
    public $matches;

    /**
     *
     * @var float
     */
    public $score;

    /**
     *
     * @var integer
     */
    public $is_pass;

    /**
     *
     * @var integer
     */
    public $time;


    /**
     * 查询多个人员
     *
     * @param array $data
     * @param integer $start_index
     * @param integer $get_count
     * @return array
     * @throws \Exception
     */
    public static function search($data, $start_index = null, $get_count = null)
    {
        $query = array();
        $option = array();

        if (isset($data['person_id'])) {
            $query["person_id"] = $data['person_id'];
        }

        if (isset($data['device_code'])) {
            $query["device_code"] = new Regex('.*'.$data['device_code'].'.*', 'i');

        }

        if(isset($data['field']) && isset($data['order'])){
            $option['sort'] = [$data['field'] => $data['order']];
        }

        if(!empty($get_count)){
            $option['limit'] = $get_count;
            $option['skip'] = $start_index;
        }

        return parent::findAllByQueryStringId($query, $option);
    }

    /**
     * 返回数量
     *
     * @param array $data
     * @return integer
     * @throws \Exception
     */
    public static function searchCount($data)
    {
        $query = array();
        $option = array();

        if (isset($data['person_id'])) {
            $query["person_id"] = $data['person_id'];
        }

        if (isset($data['device_code'])) {
            $query["device_code"] = new Regex('.*'.$data['device_code'].'.*', 'i');

        }

        return parent::countByQuery($query, $option);
    }

    /**
     * 添加人员额外信息
     * @param $passRecords
     * @return array|mixed
     */
    public static function addMoreInfo($passRecords)
    {
        $isArray = true;
        if (!is_array($passRecords)) {
            // 假如传入的是对象
            $passRecords = [$passRecords];
            $isArray = false;
        }

        $deviceCodes = [];
        $memberImageIds = [];
        foreach ($passRecords as $passRecord) {
            $deviceCodes[] = $passRecord['device_code'];
            $memberImageIds[] = $passRecord['person_image_id'];
        }

        $system = TSystem::findSystem();
        $memberImages = TMemberImage::findByImageIds($memberImageIds);

        foreach ($passRecords as $key => $passRecord) {
            //添加人员照片信息
            foreach ($memberImages as $memberImage) {
                if ($passRecord['person_image_id'] == $memberImage->image_id) {
                    $passRecord['person_image_path'] = Utility::imagePathToDownloadUrl($system->server_url, $memberImage->image_path);
                }
            }

            $passRecord['capture_image_path'] = Utility::imagePathToDownloadUrl($system->server_url,$passRecord['capture_image_path']);
        }

        if ($isArray){
            return $passRecords;
        }else{
            return $passRecords[0];
        }

    }

    /**
     * 统计人员每日早晚通行记录
     * @param integer $timeBegin
     * @param integer $timeEnd
     * @return array|mixed
     * @throws \Exception
     */
    public static function countCommuterTime($timeBegin, $timeEnd)
    {
        $match = [];
        $match['time']['$gte'] = $timeBegin;
        $match['time']['$lte'] = $timeEnd;
        $match['is_pass'] = 1;

        $group = [];
        $group["_id"] = '$person_id';
        $group["time"]['$push'] = '$time';

        $pipeline = [
            [
                '$match' => $match
            ],
            [
                '$project' => [
                    "person_id" => 1,
                    "time" => 1
                ]
            ],
            [
                '$group' => [
                    "_id" => '$person_id',
                    "time" => ['$push' => '$time'],
                ]
            ],
        ];

        return parent::findAndAggregate($pipeline);
    }
}