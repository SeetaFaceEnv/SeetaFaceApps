<?php

namespace SeetaAiBuildingCommunity\Models;

use MongoDB\BSON\ObjectID;
use SeetaAiBuildingCommunity\Common\Manager\Utility;
use SeetaAiBuildingCommunity\Models\Base\ModelHandler;
use SeetaAiBuildingCommunity\Models\Traits\ModelTraitBase;

class TMember extends ModelHandler
{

    use ModelTraitBase;

    /**
     *
     * @var ObjectId
     */
    public $_id;

    /**
     *
     * @var array
     */
    public $device_ids;

    /**
     *
     * @var array
     */
    public $group_ids;

    /**
     *
     * @var array
     */
    public $images;

    /**
     *
     * @var array
     */
    public $attributes;

    /**
     *
     * @var integer
     */
    public $status;


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

        if (isset($data['status'])) {
            $query["status"] = $data['status'];
        }
        else{
            $query["status"] = MEMBER_STATUS_VALID;
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
            $query["status"] = MEMBER_STATUS_VALID;
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
                'status' => MEMBER_STATUS_DELETED,
            ]
        ];

        return parent::updateByQuery($query, $set);
    }

    /**
     * 添加人员额外信息
     * @param $members
     * @return array|mixed
     */
    public static function addMoreInfo($members)
    {
        $isArray = true;
        if (!is_array($members)) {
            // 假如传入的是对象
            $members = [$members];
            $isArray = false;
        }

        $devices = TDevice::search([]);
        $deviceIdName = [];
        foreach ($devices as $device) {
            $deviceIdName[(string)$device->id] = $device->name;
        }

        $groups = TGroup::search([]);
        $groupIdName = [];
        foreach ($groups as $group) {
            $groupIdName[(string)$group->id] = $group->name;
        }

        $system = TSystem::findSystem();

        foreach ($members as $member) {
            $member['images'] = [];
            $memberImages = TMemberImage::findByMemberId((string)$member->id);
            foreach ($memberImages as $memberImage) {
                $image['id'] = $memberImage->image_id;
                $image['image_url'] = Utility::imagePathToDownloadUrl($system->server_url, $memberImage->image_path);
                $member['images'][] = $image;
            }

            $deviceNames = [];
            foreach ($member['device_ids'] as $device_id) {
                $deviceNames[] = $deviceIdName[$device_id];
            }
            $member['device_names'] = $deviceNames;

            $groupNames = [];
            foreach ($member['group_ids'] as $group_id) {
                $groupNames[] = $groupIdName[$group_id];
            }
            $member['group_names'] = $groupNames;
        }

        if ($isArray){
            return $members;
        }else{
            return $members[0];
        }

    }

    /**
     * 添加动态字段信息
     * @param $members
     * @return array|mixed
     */
    public static function addAttributes($members)
    {
        $isArray = true;
        if (!is_array($members)) {
            // 假如传入的是对象
            $members = [$members];
            $isArray = false;
        }

        $fields = TField::search([]);
        foreach ($fields as $field) {
            foreach ($members as $key => $member) {
                $members[$key]->attributes[$field->name] = $members[$key]->attributes[(string)$field->id];
                unset($members[$key]->attributes[(string)$field->id]);
            }
        }

        if ($isArray){
            return $members;
        }else{
            return $members[0];
        }


    }

    /**
     * 删除人员的某台设备
     *
     * @param string $device_id
     * @return bool
     * @throws \Exception
     */
    public static function deleteDeviceId($device_id)
    {
        $query = [
            'device_ids' => $device_id
        ];
        $set = [
            '$pull' => [
                'device_ids' => $device_id,
            ]
        ];

        return parent::updateByQuery($query, $set);
    }

    /**
     * 删除人员的某个设备组
     *
     * @param string $group_id
     * @return bool
     * @throws \Exception
     */
    public static function deleteGroupId($group_id)
    {
        $query = [
            'group_ids' => $group_id
        ];
        $set = [
            '$pull' => [
                'group_ids' => $group_id,
            ]
        ];

        return parent::updateByQuery($query, $set);
    }

    /**
     * 删除所有人的某个字段
     *
     * @param string $field_id
     * @return bool
     * @throws \Exception
     */
    public static function deleteField($field_id)
    {
        $query = [];
        $set = [
            '$unset' => [
                'attributes.'.$field_id => "",
            ]
        ];

        return parent::updateByQuery($query, $set);
    }
}