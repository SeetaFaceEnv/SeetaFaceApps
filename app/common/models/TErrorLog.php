<?php

namespace SeetaAiBuildingCommunity\Models;

use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Regex;
use SeetaAiBuildingCommunity\Models\Base\ModelHandler;
use SeetaAiBuildingCommunity\Models\Traits\ModelTraitBase;

class TErrorLog extends ModelHandler
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
     * @var integer
     */
    public $level;

    /**
     *
     * @var integer
     */
    public $time;

    /**
     *
     * @var string
     */
    public $content;

    /**
     * 查询多条异常历史
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

        if (isset($data['time_begin'])) {
            $query["time"]['$gte' ] = $data['time_begin'];
        }

        if (isset($data['time_end'])) {
            $query["time"]['$lte'] = $data['time_end'];
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

        if (isset($data['time_begin'])) {
            $query["time"]['$gte' ] = $data['time_begin'];
        }

        if (isset($data['time_end'])) {
            $query["time"]['$lte'] = $data['time_end'];
        }

        if (isset($data['device_code'])) {
            $query["device_code"] = new Regex('.*'.$data['device_code'].'.*', 'i');
        }

        return parent::countByQuery($query, $option);
    }

}