<?php

namespace SeetaAiBuildingCommunity\Common\Manager;

use Phalcon\Di;

class FileManager extends RedisManager
{
    const DEF_INFO = [
        self::DEF_INFO_PREFIX => 'file_key:',
        self::DEF_INFO_LIFETIME => 7200,
        self::DEF_INFO_IS_HASH => false,
    ];

    public function __construct()
    {
        $config = Di::getDefault()->get('config')->redis;
        parent::__construct($config->prefix . $config->host . ':' . $config->port, $config->password);
    }

    /**
     *存储文件到本地，并重命名
     *
     * @param string $dir 路径名
     * @param \Phalcon\Http\Request\File $file
     * @param string $file_name
     * @return string $file_path
     * @throws $exception
     */
    public static function storeFile($dir, $file, $file_name)
    {
        try {
            $file_path = null;
            if (!file_exists($dir)) {
                $res = mkdir($dir, 0755, true);
                if ($res == false) {
                    throw new \Exception("文件读写错误", ERR_FILE_WRITE_WRONG);
                }
            }
            $file_path = $dir . $file_name;
            $file->moveTo($file_path);
        } catch ( \Exception $exception ) {
            /*need log*/
            throw new \Exception($exception->getMessage(), ERR_FILE_WRITE_WRONG);
        }
        return $file_path;
    }

    /**
     * 获取指定目录下所有文件名
     *
     * @param string $dir 路径名
     * @return array
     * @throws $exception
     */
    public static function listFile($dir)
    {
        try {
            if (!file_exists($dir)) {
                $res = mkdir($dir, 0755, true);
                if ($res == false) {
                    throw new \Exception("文件读写错误", ERR_API_FILE_WRITE_WRONG);
                }
            }

            $filePaths = scandir($dir);
            $result = [];
            foreach ($filePaths as $key => $filePath) {
                if ($filePath != "." && $filePath != "..") {
                    $result[] = $filePath;
                }
            }

            return $result;
        } catch ( \Exception $exception ) {
            throw new \Exception("文件读写错误", ERR_API_FILE_WRITE_WRONG);
        }
    }

    /**
     *  添加file key
     * @param $fileKey
     * @param $filePath
     * @return bool
     * @throws \Exception
     */
    public function setFileKey($fileKey, $filePath)
    {
        return parent::setCache(self::DEF_INFO, $fileKey, $filePath);
    }

    /**
     * 获取file key
     * @param $fileKey
     * @return bool|mixed
     * @throws \Exception
     */
    public function getFileKey($fileKey)
    {
        return parent::getCache(self::DEF_INFO, $fileKey);
    }
}