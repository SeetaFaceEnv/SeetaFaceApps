<?php

define('ERR_SUCCESS', 0);
define('ERR_PARAM_WRONG', 1001);

define('ERR_DB_WRONG', 1002);                                   //  数据库错误
define('ERR_CACHE_WRONG', 1003);                                //  缓存错误
define('ERR_SIGNATURE_WRONG', 1004);                            //  数字签名错误
define('ERR_VERIFY_WRONG', 1005);                               //  验证码错误
define('ERR_VERIFY_CODE_EXPIRE', 1006);                         //  验证码过期
define('ERR_FILE_MISSED', 1007);                                //  文件缺失
define('ERR_FILE_SIZE_TOO_LARGE', 1008);                        //  文件过大
define('ERR_FILE_NOT_EXIST', 1009);                             //  文件不存在
define('ERR_FILE_TYPE_NOT_SUPPORTED', 1010);                    //  文件格式错误
define('ERR_USER_SESSION_EXPIRED', 1111);                       //  sessionId过期
define('ERR_RABBIT_MQ_WRONG', 1012);                            //  rabbitMQ错误
define('ERR_FILE_UPLOAD_WRONG', 1013);                          //  文件上传错误
define('ERR_REQUEST_TIMESTAMP_WRONG', 1014);                    //  时间戳错误
define('ERR_FILE_WRITE_WRONG', 1015);                           //  文件写入错误
define('ERR_CODE_UNKNOW', 1016);                                //  code不存在
define('ERR_SYNCHRO_POST_WRONG', 1017);                         //  同步接口请求失败
define('ERR_SYNCHRO_DATA_WRONG', 1018);                         //  同步数据失败
define('ERR_FAILED', 1999);                                     //  错误


// 管理员
define('ERR_ADMIN_NOT_EXIST',1031);                             //  该管理员不存在
define('ERR_PASSWORD_NOT_MATCHED',1032);                        //  用户名或者密码错误
define('ERR_ORIGINAL_PASSWORD_WRONG',1033);                     //  原密码输入错误
define('ERR_PASSWORD_REPEAT_FAILED',1034);                      //  两次输入密码不一致

// 人员
define('ERR_MEMBER_NOT_EXIST',1041);                            //  员工不存在
define('ERR_MEMBER_IMAGE_OVER_MAX_NUM',1042);                   //  照片超出最大上传限制
define('ERR_MEMBER_INFO_NOT_EXIST',1043);                       //  人员信息不能为空
define('ERR_MEMBER_FIELD_AND_IMAGE_NOT_EXIST',1044);            //  人员字段信息和照片信息不能皆空
define('ERR_MEMBER_IMAGE_NOT_EXIST',1045);                      //  人员字段信息和照片信息不能皆空

// 字段
define('ERR_FIELD_NOT_EXIST',1051);                             //  字段不存在
define('ERR_FIELD_NAME_EXIST',1052);                            //  字段名字已存在

// 设备
define('ERR_DEVICE_NOT_EXIST',1061);                            //  设备不存在
define('ERR_DEVICE_CODE_ALREADY_EXIST',1062);                   //  设备编号重复
define('ERR_DEVICE_NOT_USEFUL',1063);                           //  设备不可用
define('ERR_DEVICE_SIGNATURE_WRONG', 1064);                     //  设备签名错误
define('ERR_DEVICE_OPERATION_WRONG', 1065);                     //  设备运行任务失败
define('ERR_DEVICE_ADD_STREAM_WRONG', 1066);                    //  增加流参数操作失败
define('ERR_DEVICE_DEL_STREAM_WRONG', 1067);                    //  删除流参数操作失败
define('ERR_DEVICE_WEBCAM_OVER_MAX_NUM', 1068);                 //  添加的WEBCAM流超过数量
define('ERR_DEVICE_CANT_ADD_STREAM', 1069);                     //  非智能网关的设备，不允许添加流

//设备组
define('ERR_GROUP_NOT_EXIST',1071);                             //  设备组不存在
define('ERR_GROUP_DEVICE_EXIST',1072);                          //  设备组内存在设备，不可删除

//流
define('ERR_STREAM_NOT_EXIST',1081);                            //  流数据不存在
define('ERR_STREAM_DEVICE_EXIST',1082);                         //  正在被设备使用，不可删除

//通行时间模板
define('ERR_TIME_TEMPLATE_NOT_EXIST',1091);                     //  通行时间模板不存在
define('ERR_TIME_TEMPLATE_STREAM_EXIST',1092);                  //  正在被设备使用，不可删除

// Redis错误
define('ERR_REDIS_CREATE_FAILED',2001);                         //  Redis创建记录失败

// 设备管理平台错误码
define('ERR_SEETA_DEVICE_1002',3001);
define('ERR_SEETA_DEVICE_1003',3002);
define('ERR_SEETA_DEVICE_1006',3003);
define('ERR_SEETA_DEVICE_1007',3004);
define('ERR_SEETA_DEVICE_1009',3005);
define('ERR_SEETA_DEVICE_1010',3006);
define('ERR_SEETA_DEVICE_1011',3007);
define('ERR_SEETA_DEVICE_1012',3008);
define('ERR_SEETA_DEVICE_1013',3009);
define('ERR_SEETA_DEVICE_1014',3010);
define('ERR_SEETA_DEVICE_1015',3011);
define('ERR_SEETA_DEVICE_2201',3012);
define('ERR_SEETA_DEVICE_2202',3013);
define('ERR_SEETA_DEVICE_2203',3014);
define('ERR_SEETA_DEVICE_2204',3015);
define('ERR_SEETA_DEVICE_2206',3016);
define('ERR_SEETA_DEVICE_2207',3017);
define('ERR_SEETA_DEVICE_2208',3018);
define('ERR_SEETA_DEVICE_2209',3019);
define('ERR_SEETA_DEVICE_2210',3020);
define('ERR_SEETA_DEVICE_2211',3021);
define('ERR_SEETA_DEVICE_2212',3022);
define('ERR_SEETA_DEVICE_2213',3023);
define('ERR_SEETA_DEVICE_2214',3024);
define('ERR_SEETA_DEVICE_2215',3025);
define('ERR_SEETA_DEVICE_2216',3026);
define('ERR_SEETA_DEVICE_2301',3027);
define('ERR_SEETA_DEVICE_2302',3028);
define('ERR_SEETA_DEVICE_2303',3029);
define('ERR_SEETA_DEVICE_2304',3030);
define('ERR_SEETA_DEVICE_2305',3031);
define('ERR_SEETA_DEVICE_2306',3032);
define('ERR_SEETA_DEVICE_2401',3033);
define('ERR_SEETA_DEVICE_2402',3034);
define('ERR_SEETA_DEVICE_2403',3035);
define('ERR_SEETA_DEVICE_2404',3036);
define('ERR_SEETA_DEVICE_2405',3037);
define('ERR_SEETA_DEVICE_2406',3038);
define('ERR_SEETA_DEVICE_2901',3039);
define('ERR_SEETA_DEVICE_2902',3040);
define('ERR_SEETA_DEVICE_2903',3041);
define('ERR_SEETA_DEVICE_2904',3042);
define('ERR_SEETA_DEVICE_3001',3043);
define('ERR_SEETA_DEVICE_3002',3044);
define('ERR_SEETA_DEVICE_3003',3045);
define('ERR_SEETA_DEVICE_3004',3046);
define('ERR_SEETA_DEVICE_3005',3047);
define('ERR_SEETA_DEVICE_3006',3048);
define('ERR_SEETA_DEVICE_3007',3049);
define('ERR_SEETA_DEVICE_3008',3050);
define('ERR_SEETA_DEVICE_3009',3051);
define('ERR_SEETA_DEVICE_3010',3052);
define('ERR_SEETA_DEVICE_3011',3053);
define('ERR_SEETA_DEVICE_3012',3054);


define('ERR_SEETA_DEVICE_CODES',[
    1002 => ERR_SEETA_DEVICE_1002,
    1003 => ERR_SEETA_DEVICE_1003,
    1006 => ERR_SEETA_DEVICE_1006,
    1007 => ERR_SEETA_DEVICE_1007,
    1009 => ERR_SEETA_DEVICE_1009,
    1010 => ERR_SEETA_DEVICE_1010,
    1011 => ERR_SEETA_DEVICE_1011,
    1012 => ERR_SEETA_DEVICE_1012,
    1013 => ERR_SEETA_DEVICE_1013,
    1014 => ERR_SEETA_DEVICE_1014,
    1015 => ERR_SEETA_DEVICE_1015,
    2201 => ERR_SEETA_DEVICE_2201,
    2202 => ERR_SEETA_DEVICE_2202,
    2203 => ERR_SEETA_DEVICE_2203,
    2204 => ERR_SEETA_DEVICE_2204,
    2206 => ERR_SEETA_DEVICE_2206,
    2207 => ERR_SEETA_DEVICE_2207,
    2208 => ERR_SEETA_DEVICE_2208,
    2209 => ERR_SEETA_DEVICE_2209,
    2210 => ERR_SEETA_DEVICE_2210,
    2211 => ERR_SEETA_DEVICE_2211,
    2212 => ERR_SEETA_DEVICE_2212,
    2213 => ERR_SEETA_DEVICE_2213,
    2214 => ERR_SEETA_DEVICE_2214,
    2215 => ERR_SEETA_DEVICE_2215,
    2216 => ERR_SEETA_DEVICE_2216,
    2301 => ERR_SEETA_DEVICE_2301,
    2302 => ERR_SEETA_DEVICE_2302,
    2303 => ERR_SEETA_DEVICE_2303,
    2304 => ERR_SEETA_DEVICE_2304,
    2305 => ERR_SEETA_DEVICE_2305,
    2306 => ERR_SEETA_DEVICE_2306,
    2401 => ERR_SEETA_DEVICE_2401,
    2402 => ERR_SEETA_DEVICE_2402,
    2403 => ERR_SEETA_DEVICE_2403,
    2404 => ERR_SEETA_DEVICE_2404,
    2405 => ERR_SEETA_DEVICE_2405,
    2406 => ERR_SEETA_DEVICE_2406,
    2901 => ERR_SEETA_DEVICE_2901,
    2902 => ERR_SEETA_DEVICE_2902,
    2903 => ERR_SEETA_DEVICE_2903,
    2904 => ERR_SEETA_DEVICE_2904,
    3001 => ERR_SEETA_DEVICE_3001,
    3002 => ERR_SEETA_DEVICE_3002,
    3003 => ERR_SEETA_DEVICE_3003,
    3004 => ERR_SEETA_DEVICE_3004,
    3005 => ERR_SEETA_DEVICE_3005,
    3006 => ERR_SEETA_DEVICE_3006,
    3007 => ERR_SEETA_DEVICE_3007,
    3008 => ERR_SEETA_DEVICE_3008,
    3009 => ERR_SEETA_DEVICE_3009,
    3010 => ERR_SEETA_DEVICE_3010,
    3011 => ERR_SEETA_DEVICE_3011,
    3012 => ERR_SEETA_DEVICE_3012,
]);

// gateway服务器发送用户失败
define('ERR_SEND_WRONG',4001);                                  //  消息发送客户端失败



