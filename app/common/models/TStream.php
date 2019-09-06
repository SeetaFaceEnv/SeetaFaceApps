<?php

namespace SeetaAiBuildingCommunity\Models;

use MongoDB\BSON\ObjectID;
use SeetaAiBuildingCommunity\Models\Base\ModelHandler;
use SeetaAiBuildingCommunity\Models\Traits\ModelTraitBase;

class TStream extends ModelHandler
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
    public $name;

    /**
     *
     * @var string
     */
    public $time_template_id;

    /**
     *
     * @var array
     */
    public $camera_params;

    /**
     *
     * @var integer
     */
    public $status;


    /**
     * 根据时间模板id查询流媒体
     * @param string $id
     * @return TStream
     * @throws \Exception
     */
    public static function findByTemplateId($id)
    {
        $query = array(
            "time_template_id" => $id,
            "status" => STREAM_STATUS_VALID
        );

        return parent::findAllByQuery($query);
    }

    /**
     * 根据设备id查询流媒体
     * @param array $ids
     * @return TStream
     */
    public static function findByDeviceId($ids)
    {
        $query = array(
            "device_id" => ['$in' => $ids],
            "status" => STREAM_STATUS_VALID
        );

        return parent::findAllByQueryStringId($query);
    }

    /**
     * 查询多个流
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

        if (isset($data['status'])) {
            $query["status"] = $data['status'];
        }
        else{
            $query["status"] = STREAM_STATUS_VALID;
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

        if (isset($data['status'])) {
            $query["status"] = $data['status'];
        }else{
            $query["status"] = STREAM_STATUS_VALID;
        }

        return parent::countByQuery($query, $option);
    }


    /**
     * 假删除
     *
     * @param ObjectID $id
     * @return bool
     * @throws \Exception
     */
    public static function falseDeleteById($id)
    {
        $query = [
            '_id' => $id,
        ];
        $set = [
            '$set' => [
                'status' => STREAM_STATUS_DELETED,
            ]
        ];

        return parent::updateByQuery($query, $set);
    }

    /**
     * 更新多个流的time_slots参数
     *
     * @param array $ids
     * @param array $timeSlots
     * @return bool
     * @throws \Exception
     */
    public static function changeTime($ids, $timeSlots)
    {
        $query = [
            '_id' => [
                '$in' => $ids
            ],
        ];
        $set = [
            '$set' => [
                'camera_params.time_slots' => $timeSlots,
            ]
        ];

        return parent::updateByQuery($query, $set);
    }
}