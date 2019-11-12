<?php

namespace SeetaAiBuildingCommunity\Models;

use MongoDB\BSON\ObjectID;
use SeetaAiBuildingCommunity\Models\Base\ModelHandler;
use SeetaAiBuildingCommunity\Models\Traits\ModelTraitBase;

class TMemberImage extends ModelHandler
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
    public $member_id;

    /**
     *
     * @var string
     */
    public $image_id;

    /**
     *
     * @var string
     */
    public $image_path;

    /**
     *
     * @var integer
     */
    public $status;


    /**
     * 根据人员id，查询人员照片
     * @param string $id
     * @return array
     * @throws \Exception
     */
    public static function findByMemberId($id)
    {
        $query = array(
            "member_id" => $id,
            "status" => MEMBER_IMAGE_STATUS_VALID
        );

        return parent::findAllByQuery($query);
    }

    /**
     * 根据image_id，查询人员照片
     * @param string $id
     * @return TMemberImage
     * @throws \Exception
     */
    public static function findByImageId($id)
    {
        $query = array(
            "image_id" => $id,
        );

        return parent::findByQuery($query);
    }

    /**
     * 根据image_id，查询人员照片
     * @param array $ids
     * @return array
     * @throws \Exception
     */
    public static function findByImageIds($ids)
    {
        $query = array(
            "image_id" => ['$in' => $ids],
        );

        return parent::findAllByQueryStringId($query);
    }

    /**
     * 通过image_id假删除
     *
     * @param $image_id
     * @return bool
     * @throws \Exception
     */
    public static function falseDeleteByImageId($image_id)
    {
        $query = [
            'image_id' => $image_id,
        ];
        $set = [
            '$set' => [
                'status' => MEMBER_IMAGE_STATUS_DELETED,
            ]
        ];

        return parent::updateByQuery($query, $set);
    }
}