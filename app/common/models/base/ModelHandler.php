<?php

namespace SeetaAiBuildingCommunity\Models\Base;

use JsonSerializable;

abstract class ModelHandler extends MongoDbHandler implements JsonSerializable
{
    /**
     * 解析类名，生成表名
     *
     * @return string
     *
     * */
    private static function classNameToCollectionName(){
        $res = preg_split('/\\\/',static::class);
        $className = $res[count($res) - 1];
        preg_match_all('/[A-Z]{1}[a-z]*/', $className, $matchs);

        $collection = '';
        foreach ($matchs[0] as $unit){
            if(empty($collection)){
                $collection = $collection . strtolower($unit);
            }
            else{
                $collection = $collection . '_' . strtolower($unit);
            }

        }

        return $collection;
    }

    /**
     * 储存当前对象，id存在则update，不存在则insert
     *
     * @return string $id
     * @throws \Exception
     */
    protected function save()
    {
        $array = get_object_vars($this);    //Object to array
        $collection = self::classNameToCollectionName();

        unset($array['collection']);             //移除集合名
        if($this->_id != null){
            return parent::update($array, $collection);
        }
        else{
            return parent::insert($array, $collection);
        }
    }

    /**
     * 以Query查询, if exit, return Object, if not, return null
     * @param array $query
     * @param array $option
     * @return mixed
     * @throws \Exception
     */
    protected static function findByQuery($query, $option = [])
    {
        $collection = self::classNameToCollectionName();
        $result = parent::findOne($query, $collection, $option);
        if($result == null){
            return $result;
        }
        else{
            $object = call_user_func(array(static::class, 'create'), $result);     //实例化自身
            return $object;
        }
    }

    /**
     * 以Query查询
     * @param array $query
     * @param array $option
     * @return array
     * @throws \Exception
     */
    protected static function findAllByQuery($query, $option = [])
    {
        $collection = self::classNameToCollectionName();
        $results = parent::findMany($query, $collection, $option);
        return $results;
    }

    /**
     * 以Query查询
     * @param array $query
     * @param array $option
     * @return array
     * @throws \Exception
     */
    protected static function findAllByQueryStringId($query, $option = [])
    {
        $collection = self::classNameToCollectionName();
        $results = parent::findMany($query, $collection, $option);
        foreach ($results as $key => $result){
            $results[$key]->id = (string) $result->_id;
            unset($result->_id);
        }
        return $results;
    }

    /**
     * Update Many
     * @param array $query
     * @param array $set
     * @param array $option
     * @return integer
     * @throws \Exception
     */
    protected static function updateManyByQuery($query, $set, $option = [])
    {
        $collection = self::classNameToCollectionName();
        return parent::updateMany($query, $set, $collection, $option);
    }

    /**
     * 聚合查询
     * @param array $pipeline
     * @return array
     * @throws \Exception
     */
    protected static function findAndAggregate($pipeline)
    {
        $collection = self::classNameToCollectionName();
        return parent::aggregate($pipeline, $collection);
    }

    /**
     * 更新
     * @param array $query
     * @param array $option
     * @return boolean
     * @throws \Exception
     */
    protected static function updateByQuery($query, $update, $option = [])
    {
        $collection = self::classNameToCollectionName();
        return parent::updateMany($query, $update, $collection, $option);
    }

    /**
     * 删除
     * @param array $query
     * @param array $option
     * @return boolean
     * @throws \Exception
     */
    protected static function deleteByQuery($query, $option = [])
    {
        $collection = self::classNameToCollectionName();
        return parent::delete($query, $collection, $option);
    }

    /**
     * 以Query查询, 返回记录数
     * @param array $query
     * @param array $option
     * @return integer
     * @throws \Exception
     */
    protected static function countByQuery($query, $option = [])
    {
        $collection = self::classNameToCollectionName();
        return parent::count($query, $collection, $option);
    }

    /**
     * 查询字段最大值
     *
     * @var string $field
     * @return integer
     * @throws \Exception
     */
    protected static function getMax($field){
        $collection = self::classNameToCollectionName();
        return parent::max($field, $collection);
    }
}
