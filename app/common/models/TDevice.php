<?php

namespace SeetaAiBuildingCommunity\Models;

use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Regex;
use SeetaAiBuildingCommunity\Models\Base\ModelHandler;
use SeetaAiBuildingCommunity\Models\Traits\ModelTraitBase;

class TDevice extends ModelHandler
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
    public $code;

    /**
     *
     * @var integer
     */
    public $type;

    /**
     *
     * @var string
     */
    public $group_id;

    /**
     *
     * @var array
     */
    public $stream_ids;

    /**
     *
     * @var string
     */
    public $time_template_id;

    /**
     *
     * @var array
     */
    public $device_params;

    /**
     *
     * @var array
     */
    public $camera_params;

    /**
     *
     * @var string
     */
    public $report_11;

    /**
     *
     * @var string
     */
    public $report_1n;

    /**
     *
     * @var integer
     */
    public $status;


    /**
     * 查询包含某个流_id的设备
     * @param $id
     * @return array
     * @throws \Exception
     */
    public static function findByStreamId($id)
    {
        $query = array(
            "stream_ids" => $id,
            "status" => DEVICE_STATUS_VALID
        );

        return parent::findAllByQuery($query);
    }

    /**
     * 查询包含多个流_id的设备
     * @param $id
     * @return array
     * @throws \Exception
     */
    public static function findByStreamIds($id)
    {
        $query = array(
            "stream_ids" => ['$in' => $id],
            "status" => DEVICE_STATUS_VALID
        );

        return parent::findAllByQuery($query);
    }

    /**
     * 根据设备组id查询设备
     * @param $id
     * @return array
     * @throws \Exception
     */
    public static function findByGroupId($id)
    {
        $query = array(
            "group_id" => $id,
            "status" => DEVICE_STATUS_VALID
        );

        return parent::findAllByQueryStringId($query);
    }

    /**
     * 根据时间模板id查询设备
     * @param $id
     * @return array
     * @throws \Exception
     */
    public static function findByTemplateId($id)
    {
        $query = array(
            "time_template_id" => $id,
            "status" => DEVICE_STATUS_VALID
        );

        return parent::findAllByQuery($query);
    }

    /**
     * 根据设备编码查询设备
     * @param string $code
     * @return TDevice
     * @throws \Exception
     */
    public static function findByCode($code)
    {
        $query = array(
            "code" => $code,
            "status" => DEVICE_STATUS_VALID,
        );

        return parent::findByQuery($query);
    }

    /**
     * 根据设备编码查询设备
     * @param array $codes
     * @param integer $status
     * @return TDevice
     * @throws \Exception
     */
    public static function findByCodes($codes)
    {
        $query["code"]['$in'] = $codes;

        return parent::findAllByQuery($query);
    }

    /**
     * 根据设备编码查询设备
     * @param string $name
     * @return TDevice
     * @throws \Exception
     */
    public static function findByName($name)
    {
        $query = array(
            "name" => new Regex('.*' . $name . '.*', 'i'),
        );

        $option['projection'] = ["code" => 1, "id" => -1];

        return parent::findAllByQuery($query);
    }

    /**
     * 查询多个设备
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
        } else {
            $query["status"] = DEVICE_STATUS_VALID;
        }

        if (isset($data['name'])) {
            $query["name"] = new Regex('.*' . $data['name'] . '.*', 'i');
        }

        if (isset($data['code'])) {
            $query["code"] = new Regex('.*' . $data['code'] . '.*', 'i');
        }

        if (isset($data['field']) && isset($data['order'])) {
            $option['sort'] = [$data['field'] => $data['order']];
        }

        if (isset($data['projection'])) {
            $option['projection'] = $data['projection'];
        }

        if (!empty($get_count)) {
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
        } else {
            $query["status"] = DEVICE_STATUS_VALID;
        }

        if (isset($data['name'])) {
            $query["name"] = new Regex('.*' . $data['name'] . '.*', 'i');

        }

        if (isset($data['code'])) {
            $query["code"] = new Regex('.*' . $data['code'] . '.*', 'i');

        }

        if (isset($data['field']) && isset($data['order'])) {
            $option['sort'] = [$data['field'] => $data['order']];
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
                'status' => DEVICE_STATUS_DELETED,
            ]
        ];

        return parent::updateByQuery($query, $set);
    }

    /**
     * 更新多个流的time_slot参数
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

    /**
     * 添加额外信息
     * @param $devices
     * @return array|mixed
     */
    public static function addMoreInfo($devices)
    {
        $isArray = true;
        if (!is_array($devices)) {
            // 假如传入的是对象
            $devices = [$devices];
            $isArray = false;
        }

        $deviceIds = [];
        foreach ($devices as $device) {
            $deviceIds[] = (string)$device->id;
        }

        $streams = TStream::findByDeviceId($deviceIds);

        foreach ($devices as $key => $device) {
            foreach ($streams as $stream) {
                if ($device->id == $stream->device_id) {
                    $devices[$key]["streams"] = $stream;
                }
            }
        }

        if ($isArray) {
            return $devices;
        } else {
            return $devices[0];
        }

    }
}