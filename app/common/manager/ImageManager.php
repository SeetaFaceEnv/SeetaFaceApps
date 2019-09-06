<?php

namespace SeetaAiBuildingCommunity\Common\Manager;

use Phalcon\Di;

class ImageManager extends RedisManager
{
    const DEF_INFO = [
        self::DEF_INFO_PREFIX => 'image_key:',
        self::DEF_INFO_LIFETIME => 1200,
        self::DEF_INFO_IS_HASH => false,
    ];

    public function __construct()
    {
        parent::__construct(Di::getDefault()->get('redis'));
    }

    /**
     * 添加image key
     * @param string $imageKey
     * @param string $imagePath
     * @return boolean
     * @throws /Exception
     * */
    public function setImageKey($imageKey, $imagePath){
        return $this->setCache(self::DEF_INFO, $imageKey, $imagePath);
    }

    /**
     * 获取image key
     * @param string $imageKey
     * @return string | boolean
     * @throws /Exception
     * */
    public function getImageKey($imageKey){
        return $this->getCache(self::DEF_INFO, $imageKey);
    }
}
