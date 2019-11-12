<?php

namespace SeetaAiBuildingCommunity\Models;

use MongoDB\BSON\ObjectID;
use MongoDB\BSON\Regex;
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
    public $attributes;

    /**
     *
     * @var integer
     */
    public $status;

    /**
     * 根据user_id 查询人员
     * @param string $user_id
     * @return TMember
     * @throws \Exception
     */
    public static function findByUserId($user_id)
    {
        $query = array(
            "user_id" => $user_id,
            "status" => DEVICE_STATUS_VALID,
        );

        return parent::findByQuery($query);
    }

    /**
     * 根据证件id 查询人员
     * @param string $card_field_id
     * @param string $value
     * @return TMember
     * @throws \Exception
     */
    public static function findByCardId($card_field_id, $value)
    {
        $query = array(
            "attributes.$card_field_id" => $value,
            "status" => DEVICE_STATUS_VALID,
        );

        return parent::findByQuery($query);
    }

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
        } else {
            $query["status"] = MEMBER_STATUS_VALID;
        }

        if (isset($data['attributes'])) {
            $key = array_keys($data['attributes'])[0];
            $attributes = "attributes." . $key;
            $query[$attributes] = new Regex('.*' . $data['attributes'][$key] . '.*', 'i');
        }

        if (isset($data['projection'])) {
            $option['projection'] = $data['projection'];
        }

        if (isset($data['field']) && isset($data['order'])) {
            $option['sort'] = [$data['field'] => $data['order']];
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
            $query["status"] = MEMBER_STATUS_VALID;
        }

        if (isset($data['attributes'])) {
            $key = array_keys($data['attributes'])[0];
            $attributes = "attributes." . $key;
            $query[$attributes] = new Regex('.*' . $data['attributes'][$key] . '.*', 'i');
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
     * @throws \Exception
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
                $image['image_url'] = Utility::filePathToDownloadUrl($system->server_url, $memberImage->image_path);
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
            $member['attributes'] = TMember::repairAttributes((array)$member['attributes']);
        }

        if ($isArray) {
            return $members;
        } else {
            return $members[0];
        }

    }

    /**
     * 添加动态字段信息
     * @param $fieldIds
     * @param $members
     * @return array|mixed
     * @throws \Exception
     */
    public static function addAttributes($fieldIds, $members)
    {
        $isArray = true;
        if (!is_array($members)) {
            // 假如传入的是对象
            $members = [$members];
            $isArray = false;
        }
        $fields = TField::search([]);

        $fieldMap = [];
        foreach ($fields as $field) {
            $fieldMap[(string)$field->id] = $field->name;
        }

        foreach ($members as $key => $member) {
            $attributes = $members[$key]->attributes;
            unset($members[$key]->attributes);
            foreach ($fieldIds as $fieldId) {
                $fieldName = $fieldMap[$fieldId];
                if (isset($attributes[$fieldId])) {
                    $members[$key]->attributes[$fieldName] = $attributes[$fieldId];
                } else {
                    $members[$key]->attributes[$fieldName] = "";
                }
            }
            unset($members[$key]['device_ids']);
            unset($members[$key]['group_ids']);
            unset($members[$key]['status']);
        }


        if ($isArray) {
            return $members;
        } else {
            return $members[0];
        }
    }


    /**
     * 补全人员动态字段信息
     * @param array $attributes
     * @return array|mixed
     * @throws \Exception
     */
    public static function repairAttributes(array $attributes)
    {
        $fields = TField::search([]);

        $fieldMap = [];
        foreach ($fields as $field) {
            $fieldMap[(string)$field->id] = "";
            if (isset($attributes[(string)$field->id])) {
                $fieldMap[(string)$field->id] = $attributes[(string)$field->id];
            }
        }

        return $fieldMap;
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
                'attributes.' . $field_id => "",
            ]
        ];

        return parent::updateByQuery($query, $set);
    }
}