<?php

namespace SeetaAiBuildingCommunity\Models;

use MongoDB\BSON\ObjectID;
use SeetaAiBuildingCommunity\Models\Base\ModelHandler;
use SeetaAiBuildingCommunity\Models\Traits\ModelTraitBase;

class TSystem extends ModelHandler
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
    public $server_url;

    /**
     *
     * @var string
     */
    public $seetacloud_url;

    /**
     * 查询系统配置信息
     *
     * @param string
     * @return TSystem
     */
    public static function findSystem()
    {
        $query = array();

        return parent::findByQuery($query);
    }


}