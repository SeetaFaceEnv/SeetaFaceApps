<?php

namespace SeetaAiBuildingCommunity\Models\Base;

use Phalcon\Di;

abstract class MongoDbHandler
{
    /**
     * 查询文档
     *
     * @param array $query
     * @param string $collectionName
     * @param array $option
     * @return array
     * @throws \Exception
     */
    protected static function findOne($query = array(), $collectionName, $option = [])
    {
        try{
            $collection = Di::getDefault()->get('db')->__get($collectionName);
            $res = $collection->findOne($query, $option);
            if(!empty($res)){
                return iterator_to_array($res);
            }
            else{
                return null;
            }
        }catch (\Exception $exception){
            /*need log*/
            throw $exception;
        }

    }

    /**
     * 查询多文档
     *
     * @param array $query
     * @param string $collectionName
     * @param array $option
     * @return array
     * @throws \Exception
     */
    protected static function findMany($query = array(), $collectionName, $option = [])
    {
        try{
            $collection = Di::getDefault()->get('db')->__get($collectionName);
            $res = $collection->find($query, $option);
            if(!empty($res)){
                return $res->toArray();
            }
            else{
                return null;
            }
        }catch (\Exception $exception){
            /*need log*/
            throw $exception;
        }

    }

    /**
     * 统计文档数
     *
     * @param Array $query
     * @param string $collectionName
     * @return integer
     * @throws \Exception
     */
    protected static function count($query = array(), $collectionName)
    {
        try{
            $collection = Di::getDefault()->get('db')->__get($collectionName);
            return $collection->count($query);
        }catch (\Exception $exception){
            /*need log*/
            throw $exception;
        }

    }

    /**
     * 删除单个文档
     *
     * @param array $query
     * @param string $collectionName
     * @param array $option
     * @return boolean
     * @throws \Exception
     */
    protected static function delete($query = array(), $collectionName, $option = [])
    {
        try{
            $collection = Di::getDefault()->get('db')->__get($collectionName);
            $res = $collection->deleteMany($query, $option);
            return $res->getDeletedCount();
        }catch (\Exception $exception){
            /*need log*/
            throw $exception;
        }

    }

    /**
     * 以query更新多个文档
     *
     * @param array $query
     * @param string $collectionName
     * @param array $option
     * @return integer
     * @throws \Exception
     */
    protected static function updateMany($query, $set, $collectionName, $option = [])
    {
        try{
            $collection = Di::getDefault()->get('db')->__get($collectionName);
            $result = $collection->updateMany(
                $query,
                $set,
                $option
            );
            return $result->getModifiedCount();
        } catch (\Exception $exception){
            /*need log*/
            throw $exception;
        }

    }

    /**
     * 插入一个新的文档
     *
     * @param array $doc,
     * @param string $collectionName
     * @param array $option
     * @return string $id
     * @throws \Exception
     */
    protected static function insert($doc, $collectionName, $option = [])
    {
        unset($doc['_id']);     //移除id，使得mongodb给予新Id
        try{
            $collection = Di::getDefault()->get('db')->__get($collectionName);
            $result = $collection->insertOne($doc, $option);
            return $result->getInsertedId();
        } catch (\Exception $exception){
            /*need log*/
            throw $exception;
        }
    }

    /**
     * 更新带Id的文档
     *
     * @param array $doc
     * @param string $collectionName
     * @param array $option
     * @return string $id
     * @throws \Exception
     */
    protected static function update($doc, $collectionName, $option = [])
    {
        try{
            $id = $doc["_id"];
            unset($doc['_id']);     //移除id
            $collection = Di::getDefault()->get('db')->__get($collectionName);
            $result = $collection->updateOne(["_id" => $id], ['$set' => $doc], $option);
            return $result->getUpsertedId();
        } catch (\Exception $exception){
            /*need log*/
            throw $exception;
        }

    }

    /**
     * 返回字段最大值
     *
     * @param string $field
     * @param string $collectionName
     * @return integer
     * @throws \Exception
     */
    protected static function max($field, $collectionName)
    {
        try{
            $collection = Di::getDefault()->get('db')->__get($collectionName);
            $res = $collection->aggregate(
                [
                    ['$group' => ['_id' => 'max', 'max_value' => ['$max' => '$'.$field]]]
                ]
            )->toArray();

            if(!empty($res)){
                return $res[0]['max_value'];
            }
            else{
                return 0;
            }
        }catch (\Exception $exception){
            /*need log*/
            throw $exception;
        }

    }

    /**
     * 聚合查询
     *
     * @param array $pipeline
     * @param string $collectionName
     * @return array
     * @throws \Exception
     */
    protected static function aggregate($pipeline, $collectionName)
    {
        try{
            $collection = Di::getDefault()->get('db')->__get($collectionName);
            $res = $collection->aggregate($pipeline);
            if(!empty($res)){
                return $res->toArray();
            }
            else{
                return null;
            }
        }catch (\Exception $exception){
            /*need log*/
            throw $exception;
        }

    }
}
