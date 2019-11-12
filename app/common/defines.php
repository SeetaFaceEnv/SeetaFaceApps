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

//资源文件路径
define("RESOURCE_PATH_VERIFY_CODE", APP_PATH.'/common/library/Cabin-Bold.ttf');     //图片验证码资源文件

// 文件路径
define("FILE_PATH_APK", FILE_ROOT_PATH."/apk");
define("FILE_PATH_MEMBER_IMAGE", FILE_ROOT_PATH."/member_image");
define("FILE_PATH_CARD_IMAGE", FILE_ROOT_PATH."/card_image");
define("FILE_PATH_CAPTURE_IMAGE", FILE_ROOT_PATH."/capture_image");

//文件下载路径
define('IMAGE_DOWNLOAD_URL', '/backend/image/get?image_key=');
define('File_DOWNLOAD_URL', '/backend/file/get?file_key=');

// 设备类型
define("DEVICE_TYPE_FACE_AND_CARD_MACHINE", 1);     // 人证一体机
define("DEVICE_TYPE_ACCESS_CONTROL_MACHINE", 2);    // 门禁机
define("DEVICE_TYPE_SEETA_INTELLIGENT_GATEWAY", 3); // 视拓智能网关
define("DEVICE_TYPE_PC_INTELLIGENT_GATEWAY", 4);    // PC智能网关

//默认流名称
define("STREAM_FACE_AND_CARD_MACHINE", "人证一体机默认流");    // 人证一体机默认流
define("STREAM_TYPE_ACCESS_CONTROL_MACHINE", "门禁机默认流");    // 门禁机默认流

// 设备状态
define("DEVICE_ALIVE", 1);                      // 设备在线
define("DEVICE_NOT_ALIVE", 2);                  // 设备离线

//设备应答类型
define("DEVICE_TEST_LIGHT", 1);                 // 闪光灯
define("DEVICE_TEST_VOICE", 2);                 // 声音
define("DEVICE_TEST_CAMERA", 3);                // 摄像头
define("DEVICE_TEST_SHOW", 4);                  // 显示

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

// 通行结果
define("VERIFICATION_IS_PASS", 1);              // 认证通过
define("VERIFICATION_NOT_PASS", 2);             // 认证不通过

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
define("MEMBER_IMAGE_UPLOAD_MAX", 3);

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
define("STREAM_DEFAULT_DETECT_BOX", 2 );            // 人脸检测框
define("STREAM_DEFAULT_RECOGNIZE_TYPE", 1);         // 识别类型
define("STREAM_DEFAULT_CAPTURE_MAX_INTERVAL", 10);  // 最大尝试人脸抓拍时长(ms)
define("STREAM_DEFAULT_IS_LIGHT", 1);               // 是否开启闪光灯
define("STREAM_DEFAULT_IS_LIVING_DETECT", 1);       // 是否开启活体检测
define("STREAM_DEFAULT_CONTROL_SIGNAL_OUT", 1);     // 控制信号输出
define("STREAM_DEFAULT_TOP_N", 1);                  // 返回的照片数目
define("STREAM_DEFAULT_NOT_PASS_REPORT", 2);        // 是否上报未识别人员
define("STREAM_DEFAULT_TOP_IS_WORKING", 1);         // 是否启用(默认为启用)

// 设备管理平台回调地址
define('REPORT_11_URL', '/backend/pass_record/report11');
define('REPORT_1N_URL', '/backend/pass_record/report1n');
define('LOG_CALLBACK_URL', '/backend/callback/log');
define('DEVICE_STATUS_CALLBACK_URL', '/backend/callback/deviceStatus');
define('REGISTER_IMAGE_CALLBACK_URL', '/backend/callback/registerImage');

define('REPORT_URL', 'http://10.136.1.136');
define('SIGN', '4cc89aa9b9684076804b7974cc16caf1');