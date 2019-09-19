## 一、项目简介

当前项目为视拓智慧园区社区版的后端部分

## 二、项目框架

项目使用PHP的phalcon3.4框架。


##  三、目录架构
- common 				【公共模块】
  - languages			【错误码的映射】
  - library 	                      【类库目录】
  - manager                           【包含一些redis的操作，以及一些常用各类方法的封装】
  - models                             【数据库模型目录】
  - defines.php                     【常量定义，包含数据状态定义码，设备管理平台接口地址等】
  - errorCodes.php              【错误码集合】
- config
  - config.php  	              【配置文件，包含各类文件目录，mongodb、redis连接信息等】
- modules
  - backend						 	【智慧园区后台接口】
    - controllers                                     	
      - AccountController.php		 【登录相关接口】
      - CallbackController.php                 【设备管理平台的回调接口，设备异常，照片错误，日志 】
      - ConfigController.php                    【系统配置接口】
      - DeviceController.php                    【设备相关接口】
      - ErrorLogController.php                【错误日志】
      - FieldController.php                       【人员管理的字段相关接口】
      - FileController.php                         【提供文件获取地址的接口】
      - GroupController.php                    【设备组的相关接口】
      - ImageController.php                    【提供图片获取地址的接口】
      - MemberController.php                【人员的相关接口】
      - PassRecordController.php          【通行记录相关接口】
      - StreamController.php                  【流媒体相关接口】
      - TimeTemplateController.php     【时间模板的相关接口】
  - cli
    - tasks
      - MainTask.php 				【脚本文件】



## 四、系统设置

### 1、修改配置信息

打开`项目php目录/app/config/config.php`文件

**1.1、配置文件存储路径**（存储内容包含：人员照片，通行记录照片，APK文件等）

```php
#当前默认路径为 php目录/files ，可根据需要修改配置此路径
defined('FILE_ROOT_PATH') || define('FILE_ROOT_PATH', BASE_PATH.'/files');
```



**1.2、配置日志存储路径**

```php
#当前默认路径为 php目录/files/log/php，可根据需要修改配置此路径
defined('LOG_PATH') || define('LOG_PATH', FILE_ROOT_PATH.'/log/php');

#logger_path 存储的是系统错误日志路径
#secure_logger_path 存储的是异常访问日志路径

'logger'=>[
        'name' => 'PHP',
        'logger_path' => LOG_PATH.'/php_errors.log',
        'secure_logger_path' => LOG_PATH.'/php_secure_log.log',
    ],   
```



**1.3、配置设备管理平台地址**

```php
#此为设备管理平台的地址，请根据需要改成实际的地址。
defined('SYSEND_SERVER') || define('SYSEND_SERVER',"http://192.168.0.7:7879/");
```



**1.4、配置mongodb、redis连接信息**

```php
# 将以下的mongodb的信息，改成实际的mongodb信息
'database' => [
        'adapter' => 'mongodb',
        'url' => 'mongodb://hzhs:hzhs@127.0.0.1:27017/db_seeta_ai_building',
        'dbname' => 'db_seeta_ai_building',
        'charset' => 'utf8',
    ],

#将redis信息改成实际服务器信息，若设置了redis的密码，则填写相应的redis密码
'redis' => [
        'prefix' => 'tcp://',
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',   //local server
    ],
```



### 2、创建管理员用户

`php目录/app/modules/cli/tasks/MainTask.php` 中的 `createAdminAction` 将会创建初始化的 admin账号

可直接在终端输入命令 ，执行此脚本

```bash
$ php /home/www-root/SeetaAiBuildingCommunity/php/run main createAdmin
```

创建的初始账户账户，用户名：admin，密码：123456




[phaclon3.4下载地址](https://pan.baidu.com/s/1l5hxqvTIUwDNyjllcsflvQ)