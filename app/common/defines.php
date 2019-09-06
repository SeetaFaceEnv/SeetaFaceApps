<?php
// 运行模式
define('SERVER_MODE_DEBUG', "debug");
define('SERVER_MODE_RELEASE', "release");

/**
 * JSON segment
 */
define('CODE', 'result');
define('MESSAGE', 'msg');
define('ERROR', 'error');
define('ERROR_CODE', 'error_code');

// 用户类型
define("USER_TYPE_ADMIN", 1);

// 平台类型码
define("PLATFORM_TYPE_WEB", 1);
define("PLATFORM_TYPE_WECHAT", 2);
define("PLATFORM_TYPE_ANDROID", 3);

// Web Socket地址
define('CONFIG_WEBSOCKET_URL','websocket_url');
define('CONFIG_GATEWAY_REGISTER','127.0.0.1:1235');

// 文件路径
define("FILE_PATH_APK", FILE_ROOT_PATH."/apk");
define("FILE_PATH_MEMBER_IMAGE", FILE_ROOT_PATH."/member_image");
define("FILE_PATH_CAPTURE_IMAGE", FILE_ROOT_PATH."/capture_image");

// 设备类型
define("DEVICE_TYPE_1", 1);                     // 认证一体机
define("DEVICE_TYPE_2", 2);                     // 门禁机
define("DEVICE_TYPE_3", 3);                     // 视拓智能网关

// 设备状态
define("DEVICE_ALIVE", 1);                      // 设备在线
define("DEVICE_NOT_ALIVE", 2);                  // 设备离线

// 摄像头类型
define("CAMERA_SELF", "webcam");                // 本身摄像头
define("CAMERA_IPC", "ipc-h264");               // 网络摄像头
define("CAMERA_SEETA_HZ", "seeta-hz001");       // 中科视拓智能摄像头
define("CAMERA_SEETA_DH", "seeta-dh001");       // 中科视拓智能摄像头
define("CAMERA_SEETA_HI", "seeta-hi001");       // 中科视拓智能摄像头

// 设备异常类型
define("DEVICE_CAMERA_VALID", 1);               // 摄像头恢复正常
define("DEVICE_CAMERA_WRONG", 2);               // 摄像头异常
define("DEVICE_DISPLAY_VALID", 3);              // 应用显示恢复正常
define("DEVICE_DISPLAY_WRONG", 4);              // 应用显示异常
define("DEVICE_ONLINE", 5);                     // 设备恢复在线
define("DEVICE_OFFLINE", 6);                    // 设备离线

// 管理员状态
define("ADMIN_STATUS_VALID", 1);
define("ADMIN_STATUS_DELETED", 9);

// 人员状态
define("MEMBER_STATUS_VALID", 1);
define("MEMBER_STATUS_DELETED", 9);

// 人员照片状态
define("MEMBER_IMAGE_STATUS_VALID", 1);
define("MEMBER_IMAGE_STATUS_DELETED", 9);

// 人员照片最大上传数量
define("MEMBER_IMAGE_UPLOAD_MAX", 1);

// 字段状态
define("FIELD_STATUS_VALID", 1);
define("FIELD_STATUS_DELETED", 9);

// 设备状态
define("DEVICE_STATUS_VALID", 1);
define("DEVICE_STATUS_DELETED", 9);

// 设备组状态
define("GROUP_STATUS_VALID", 1);
define("GROUP_STATUS_DELETED", 9);

// 流状态
define("STREAM_STATUS_VALID", 1);
define("STREAM_STATUS_DELETED", 9);

// 设备可用时段模板状态
define("TIME_TEMPLATE_STATUS_VALID", 1);
define("TIME_TEMPLATE_STATUS_DELETED", 9);

// 文件大小限制
define('FILE_SIZE_MAX', 314572800);

// 设备默认参数
define("DEVICE_DEFAULT_VOICE_SWITCH", 1);           // 声音开关
define("DEVICE_DEFAULT_VOLUME", 60);                // 音量
define("DEVICE_DEFAULT_LOG_LEVEL", 1);              // 日志上报等级

// 流媒体默认参数
define("STREAM_DEFAULT_THRESHOLD_11", 0.7);         // 1:1验证阈值
define("STREAM_DEFAULT_CONFIDENCE", 0.7);           // 1:n验证阈值
define("STREAM_DEFAULT_UNSURE", 0.6);               // 1:n不可信阈值
define("STREAM_DEFAULT_MIN_CLARITY", 0.3);          // 最小清晰度
define("STREAM_DEFAULT_RECOGNITION_MODE", 1);       // 识别模式
define("STREAM_DEFAULT_MIN_FACE", 70);              // 最小人脸宽度
define("STREAM_DEFAULT_MAX_ANGLE", 20);             // 最大人脸角度
define("STREAM_DEFAULT_CROP_RATIO", 1.2);           // 截取比例
define("STREAM_DEFAULT_DETECT_BOX", 2 );             // 人脸检测框
define("STREAM_DEFAULT_RECOGNIZE_TYPE", 1);         // 识别类型
define("STREAM_DEFAULT_CAPTURE_MAX_INTERVAL", 10);  // 最大尝试人脸抓拍时长(ms)
define("STREAM_DEFAULT_IS_LIGHT", 1);               // 是否开启闪光灯
define("STREAM_DEFAULT_IS_LIVING_DETECT", 1);       // 是否开启活体检测
define("STREAM_DEFAULT_CONTROL_SIGNAL_OUT", 1);     // 控制信号输出
define("STREAM_DEFAULT_TOP_N", 1);                  // 返回的照片数目
define("STREAM_DEFAULT_NOT_PASS_REPORT", 2);        // 是否上报未识别人员
define("STREAM_DEFAULT_TOP_IS_WORKING", 1);         // 是否启用(默认为启用)

// 设备管理平台接口地址
define("SYSEND_PREFIX", SYSEND_SERVER. "seetadevice/v1/platform");

// 设备
define("SYSEND_DEVICE_ADD_URL", SYSEND_PREFIX."/device/add");                    //添加设备
define("SYSEND_DEVICE_SET_URL", SYSEND_PREFIX."/device/set");                    //设置设备
define("SYSEND_DEVICE_DELETE_URL", SYSEND_PREFIX."/device/delete");              //删除设备
define("SYSEND_DEVICE_LIST_URL", SYSEND_PREFIX."/device/list");                  //设备信息
define("SYSEND_DEVICE_TEST_URL", SYSEND_PREFIX."/device/test");                  //设备应答
define("SYSEND_DEVICE_DISCOVER_URL", SYSEND_PREFIX."/device/discover");          //发现未知设备
define("SYSEND_DEVICE_UPDATE_URL", SYSEND_PREFIX."/device/update");              //APK升级

// 设备组
define("SYSEND_SYSTEM_CREATE_GROUP_URL", SYSEND_PREFIX."/group/create");
define("SYSEND_SYSTEM_DELETE_GROUP_URL", SYSEND_PREFIX."/group/delete");
define("SYSEND_SET_DEFAULT_GROUP_URL", SYSEND_PREFIX."/group/set_default");      //设置设备组设备默认参数
define("SYSEND_GET_DEFAULT_GROUP_URL", SYSEND_PREFIX."/group/get_default");      //获取设备组设备默认参数

// 流管理
define("SYSEND_CAMERA_ADD_URL", SYSEND_PREFIX."/camera/add");                    //流参数增加
define("SYSEND_CAMERA_EDIT_URL", SYSEND_PREFIX."/camera/edit");                  //流参数修改
define("SYSEND_CAMERA_DELETE_URL", SYSEND_PREFIX."/camera/delete");              //流参数删除

// 人员
define("SYSEND_MEMBER_ADD_URL", SYSEND_PREFIX."/person/add");
define("SYSEND_MEMBER_EDIT_URL", SYSEND_PREFIX."/person/edit");
define("SYSEND_MEMBER_DELETE_URL", SYSEND_PREFIX."/person/delete");
define("SYSEND_MEMBER_ADD_IMAGE_URL", SYSEND_PREFIX."/person/add_image");
define("SYSEND_MEMBER_DELETE_IMAGE_URL", SYSEND_PREFIX."/person/delete_image");

// 系统参数
define("SYSEND_SYSTEM_SET_URL", SYSEND_PREFIX."/system/set");
define("SYSEND_SYSTEM_GET_URL", SYSEND_PREFIX."/system/get");
define("SYSEND_SYSTEM_RESET_URL", SYSEND_PREFIX."/system/reset");

// 设备管理平台回调地址
define('REPORT_11_URL', '/backend/pass_record/report11');
define('REPORT_1N_URL', '/backend/pass_record/report1n');
define('LOG_CALLBACK_URL', '/backend/callback/log');
define('DEVICE_STATUS_CALLBACK_URL', '/backend/callback/deviceStatus');
define('REGISTER_IMAGE_CALLBACK_URL', '/backend/callback/registerImage');