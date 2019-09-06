<?php

use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use SeetaAiBuildingCommunity\Common\Library\SeetaRedis;


/**
 * Shared configuration service
 */
$di->setShared('config', function () {
    return include APP_PATH . "/config/config.php";
});

/**
 * Database connection is created based in the parameters defined in the configuration file
 */
/**
 * Database connection is created based in the parameters defined in the configuration file
 */
$di->setShared('db', function () {
    $config = $this->getConfig();

    $connection = new MongoDB\Client($config->database->url);
    $datebase = $connection->__get($config->database->dbname);

    return $datebase;
});

/**
 * Redis connector
 */
$di->setShared('redis', function () {
    $config = $this->getConfig();
    $redis = new SeetaRedis($config->redis->prefix.$config->redis->host.':'.$config->redis->port,$config->redis->password);
    return $redis;
});

/**
 * 系统错误Logger
 */
$di->setShared('logger', function () {
    $config = $this->getConfig();
    $logger = new \Monolog\Logger($config->logger->name);
    $logger->pushHandler(new \Monolog\Handler\StreamHandler($config->logger->logger_path, \Monolog\Logger::WARNING));

//    $connection = new MongoDB\Client($config->database->url);
//
//    $mongoHandler = new \Monolog\Handler\MongoDBHandler($connection, $config->database->dbname, $config->database->system_log_collection);
//    $logger->pushHandler($mongoHandler);
    return $logger;
});

/**
 * 访问异常Logger
 */
$di->setShared('secure_logger', function () {
    $config = $this->getConfig();
    $logger = new \Monolog\Logger($config->logger->name);

    $logger->pushHandler(new \Monolog\Handler\StreamHandler($config->logger->secure_logger_path, \Monolog\Logger::INFO));

//    $connection = new MongoDB\Client($config->database->url);
//
//    $mongoHandler = new \Monolog\Handler\MongoDBHandler($connection, $config->database->dbname, $config->database->secure_log_collection);
//    $logger->pushHandler($mongoHandler);
    return $logger;
});

/*
 * Email Configure
 * */
$di->setShared('mailer', function (){
    $config = $this->getConfig();
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->SMTPDebug = 0;   //是否debug调试
    $mail->isSMTP();
    $mail->SMTPAuth=true;
    $mail->Host = $config->mail->host;
    $mail->Port = $config->mail->port;
    $mail->Username = $config->mail->username;
    $mail->Password = $config->mail->password;
    $mail->SMTPSecure = 'ssl';
    $mail->setFrom($config->mail->username, '智慧园区');
    $mail->CharSet = "UTF-8";

    return $mail;
});

/**
 * If the configuration specify the use of metadata adapter use it or use memory otherwise
 */
$di->setShared('modelsMetadata', function () {
    return new MetaDataAdapter();
});

/**
 * Configure the Volt service for rendering .volt templates
 */
$di->setShared('voltShared', function ($view) {
    $config = $this->getConfig();

    $volt = new VoltEngine($view, $this);
    $volt->setOptions([
        'compiledPath' => function($templatePath) use ($config) {
            $basePath = $config->application->appDir;
            if ($basePath && substr($basePath, 0, 2) == '..') {
                $basePath = dirname(__DIR__);
            }

            $basePath = realpath($basePath);
            $templatePath = trim(substr($templatePath, strlen($basePath)), '\\/');

            $filename = basename(str_replace(['\\', '/'], '_', $templatePath), '.volt') . '.php';

            $cacheDir = $config->application->cacheDir;
            if ($cacheDir && substr($cacheDir, 0, 2) == '..') {
                $cacheDir = __DIR__ . DIRECTORY_SEPARATOR . $cacheDir;
            }

            $cacheDir = realpath($cacheDir);

            if (!$cacheDir) {
                $cacheDir = sys_get_temp_dir();
            }

            if (!is_dir($cacheDir . DIRECTORY_SEPARATOR . 'volt' )) {
                @mkdir($cacheDir . DIRECTORY_SEPARATOR . 'volt' , 0755, true);
            }

            return $cacheDir . DIRECTORY_SEPARATOR . 'volt' . DIRECTORY_SEPARATOR . $filename;
        }
    ]);

    return $volt;
});
