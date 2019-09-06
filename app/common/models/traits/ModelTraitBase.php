<?php
/**
 *
 * 定义数据层可重复利用的公共方法
 *
 */

namespace SeetaAiBuildingCommunity\Models\Traits;

use MongoDB\BSON\ObjectID;

trait ModelTraitBase
{
    public function __construct($array)
    {
        foreach ($array as $key => $val){
            $this->{$key} = $val;
        }
    }

    /**
     * ID查询
     *
     * @param ObjectID $id
     * @return $this
     */
    public static function findById($id)
    {
        $query = array("_id" => $id);
        return parent::findByQuery($query);
    }

    /**
     * ID数组查询
     *
     * @param array $ids
     * @return $this
     */
    public static function findByIds($ids)
    {
        $query = [
            "_id" => [
                '$in' => $ids
            ]
        ];
        return parent::findAllByQueryStringId($query);
    }

    /**
     * 储存
     *
     * @return string $id
     */
    public function save()
    {
        $result = parent::save($this);
        if(!empty($result)){
            $this->_id = $result;
            return $this->_id;
        }
        else{
            return null;
        }
    }

    /**
     * 以ID删除文档
     * @param ObjectID $id
     * @return boolean
     */
    public static function deleteById($id){
        $query = ["_id" => $id];
        return parent::deleteByQuery($query);
    }

    /*
     * 实例化自身
     *
     * @return Object
     * */
    public static function create($array){
        return new self($array);
    }

    /*
     * Json转译函数，用于转换ObjectId
     * */
    function JsonSerialize() {
        $arr = [];
        $vars = (array)$this;

        foreach ($vars as $varName => $varValue) {
            if($varName == "_id"){
                $arr[$varName] = (string)$varValue;
            }else{
                $arr[$varName] = $varValue;
            }
        }

        return $arr;
    }

}