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
    public $person_id;

    /**
     *
     * @var array
     */
    public $show_field;

    /**
     *
     * @var string
     */
    public $card_field;

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
     * @throws \Exception
     */
    public static function findSystem()
    {
        $query = [];

        return parent::findByQuery($query);
    }


}