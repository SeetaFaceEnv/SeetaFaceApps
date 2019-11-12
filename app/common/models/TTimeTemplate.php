<?php

namespace SeetaAiBuildingCommunity\Models;

use MongoDB\BSON\ObjectID;
use SeetaAiBuildingCommunity\Models\Base\ModelHandler;
use SeetaAiBuildingCommunity\Models\Traits\ModelTraitBase;

class TTimeTemplate extends ModelHandler
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
    public $description;

    /**
     *
     * @var array
     */
    public $valid_date;

    /**
     *
     * @var array
     */
    public $invalid_date;

    /**
     *
     * @var array
     */
    public $valid_time;

     /**
     *
     * @var bool
     */
    public $exclude_weekend;

    /**
     *
     * @var array
     */
    public $special_valid_date;

    /**
     *
     * @var array
     */
    public $special_invalid_date;

    /**
     *
     * @var array
     */
    public $time_slots;

    /**
     *
     * @var integer
     */
    public $status;


    /**
     * 查询多个设备可用时段模板
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
            $query["status"] = TIME_TEMPLATE_STATUS_VALID;
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
            $query["status"] = TIME_TEMPLATE_STATUS_VALID;
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
                'status' => TIME_TEMPLATE_STATUS_DELETED,
            ]
        ];

        return parent::updateByQuery($query, $set);
    }
}