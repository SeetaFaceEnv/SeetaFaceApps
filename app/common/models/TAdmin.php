<?php

namespace SeetaAiBuildingCommunity\Models;

use MongoDB\BSON\ObjectID;
use SeetaAiBuildingCommunity\Models\Base\ModelHandler;
use SeetaAiBuildingCommunity\Models\Traits\ModelTraitBase;

class TAdmin extends ModelHandler
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
    public $username;

    /**
     *
     * @var string
     */
    public $password;

    /**
     *
     * @var integer
     */
    public $status;


    /**
     * 以用戶名查询，返回有效用户全信息
     *
     * @param string $username
     * @return TAdmin
     */
    public static function findByUsername($username)
    {
        $query = array("username" => $username, "status" => ADMIN_STATUS_VALID);

        return parent::findByQuery($query);
    }

    /**
     * 查询多个用戶，不返回私密信息
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
            $query["status"] = ADMIN_STATUS_VALID;
        }

        if(isset($data['field']) && isset($data['order'])){
            $option['sort'] = [$data['field'] => $data['order']];
        }

        if(!empty($get_count)){
            $option['limit'] = $get_count;
            $option['skip'] = $start_index;
        }

        $option['projection'] = [
            'password' => 0,
        ];

        return parent::findAllByQueryStringId($query, $option);
    }

    /**
     * 返回用户数
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
            $query["status"] = ADMIN_STATUS_VALID;
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
                'status' => ADMIN_STATUS_DELETED,
            ]
        ];

        return parent::updateByQuery($query, $set);
    }
}