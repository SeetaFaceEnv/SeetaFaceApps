<?php

namespace SeetaAiBuildingCommunity\Models;

use MongoDB\BSON\ObjectID;
use SeetaAiBuildingCommunity\Models\Base\ModelHandler;
use SeetaAiBuildingCommunity\Models\Traits\ModelTraitBase;

class TGroup extends ModelHandler
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
     * @var array
     */
    public $device_params;

    /**
     *
     * @var integer
     */
    public $status;


    /**
     * 查询多个设备组
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
            $query["status"] = GROUP_STATUS_VALID;
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
            $query["status"] = GROUP_STATUS_VALID;
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
                'status' => GROUP_STATUS_DELETED,
            ]
        ];

        return parent::updateByQuery($query, $set);
    }


    /**
     * 添加额外信息
     * @param $groups
     * @return array|mixed
     */
    public static function addMoreInfo($groups)
    {
        $isArray = true;
        if (!is_array($groups)) {
            // 假如传入的是对象
            $groups = [$groups];
            $isArray = false;
        }

        foreach ($groups as $group) {
            $devices = TDevice::findByGroupId((string)$group->id);
            
            $groupDevices = [];
            foreach ($devices as $device) {
                $groupDevices[] = [
                    "device_id" => (string)$device->id,
                    "device_code" => $device->code,
                    "device_name" => $device->name,
                ];
            }

            $group['devices'] = $groupDevices;
        }

        if ($isArray){
            return $groups;
        }else{
            return $groups[0];
        }

    }
}