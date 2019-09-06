<?php
/*
 * Modified: prepend directory path of current file, because of this file own different ENV under between Apache and command line.
 * NOTE: please remove this comment.
 */
defined('BASE_PATH') || define('BASE_PATH', getenv('BASE_PATH') ?: realpath(dirname(__FILE__) . '/../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');
defined('FILE_ROOT_PATH') || define('FILE_ROOT_PATH', BASE_PATH.'/files');
defined('LOG_PATH') || define('LOG_PATH', FILE_ROOT_PATH.'/log/php');
defined('IMAGE_DOWNLOAD_URL') || define('IMAGE_DOWNLOAD_URL', '/backend/image/get?image_key=');
defined('File_DOWNLOAD_URL') || define('File_DOWNLOAD_URL', '/backend/file/get?file_key=');
defined('WEBSOCKET_URL') || define('WEBSOCKET_URL', 'ws://192.168.0.8:8181/websocket');

//设备管理平台IP
defined('SYSEND_SERVER') || define('SYSEND_SERVER',"http://192.168.0.7:7879/");

return new \Phalcon\Config([
    'version' => '1.0',

    /*
     * 运行模式，可选值有
     * debug,release
     * 在debug模式下，忽视签名验证
     * */
    'mode' => "release",

    'database' => [
        'adapter' => 'mongodb',
        'url' => 'mongodb://hzhs:hzhs@127.0.0.1:27017/db_seeta_ai_building',
        'dbname' => 'db_seeta_ai_building',
        'charset' => 'utf8',
    ],

    'redis' => [
        'prefix' => 'tcp://',
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',   //local server
    ],

    'logger'=>[
        'name' => 'PHP',
        'logger_path' => LOG_PATH.'/php_errors.log',
        'secure_logger_path' => LOG_PATH.'/php_secure_log.log',
    ],

    'application' => [
        'appDir'         => APP_PATH . '/',
        'modelsDir'      => APP_PATH . '/common/models/',
        'migrationsDir'  => APP_PATH . '/migrations/',
        'cacheDir'       => BASE_PATH . '/cache/',

        // This allows the baseUri to be understand project paths that are not in the root directory
        // of the webpspace.  This will break if the public/index.php entry point is moved or
        // possibly if the web server rewrite rules are changed. This can also be set to a static path.
        'baseUri'        => preg_replace('/public([\/\\\\])index.php$/', '', $_SERVER["PHP_SELF"]),
    ],

    /**
     * if true, then we print a new line at the end of each CLI execution
     *
     * If we dont print a new line,
     * then the next command prompt will be placed directly on the left of the output
     * and it is less readable.
     *
     * You can disable this behaviour if the output of your application needs to don't have a new line at end
     */
    'printNewLine' => true
]);
