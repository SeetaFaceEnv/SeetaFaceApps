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
define('ERR_AUTH_WRONG', 1019);                                 //  鉴权失败
define('ERR_API_FILE_WRITE_WRONG', 1021);                       //  文件读写失败
define('ERR_FAILED', 1999);                                     //  错误


// 管理员
define('ERR_ADMIN_NOT_EXIST',1031);                             //  该管理员不存在
define('ERR_PASSWORD_NOT_MATCHED',1032);                        //  用户名或者密码错误
define('ERR_ORIGINAL_PASSWORD_WRONG',1033);                     //  原密码输入错误
define('ERR_PASSWORD_REPEAT_FAILED',1034);                      //  两次输入密码不一致

// 人员
define('ERR_MEMBER_NOT_EXIST',1041);                            //  人员不存在
define('ERR_MEMBER_IMAGE_OVER_MAX_NUM',1042);                   //  人员照片超出最大上传限制
define('ERR_MEMBER_INFO_NOT_EXIST',1043);                       //  人员信息不能为空
define('ERR_MEMBER_FIELD_AND_IMAGE_NOT_EXIST',1044);            //  人员字段信息和照片信息不能皆空
define('ERR_MEMBER_IMAGE_NOT_EXIST',1045);                      //  人员照片不存在
define('ERR_MEMBER_IMAGE_EMPTY',1046);                          //  人员照片不能为空
define('ERR_MEMBER_EXIST',1047);                                //  人员已存在

// 字段
define('ERR_FIELD_NOT_EXIST',1051);                             //  字段不存在
define('ERR_FIELD_NAME_EXIST',1052);                            //  字段名字已存在
define('ERR_DENIED_DEL_FIELD',1053);                            //  禁止删除字段
define('ERR_SYSTEM_NOT_CARD_FIELD',1054);                       //  系统未设置证件字段

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
define('ERR_TIME_TEMPLATE_STREAM_EXIST',1092);                  //  正在被流媒体使用，不可删除
define('ERR_TIME_TEMPLATE_DEVICE_EXIST',1093);                  //  正在被设备使用，不可删除

// Redis错误
define('ERR_REDIS_CREATE_FAILED',2001);                         //  Redis创建记录失败

// 设备管理平台错误码
define('ERR_SEETA_DEVICE_1002',11002);
define('ERR_SEETA_DEVICE_1003',11003);
define('ERR_SEETA_DEVICE_1006',11006);
define('ERR_SEETA_DEVICE_1007',11007);
define('ERR_SEETA_DEVICE_1009',11009);
define('ERR_SEETA_DEVICE_1010',11010);
define('ERR_SEETA_DEVICE_1011',11011);
define('ERR_SEETA_DEVICE_1012',11012);
define('ERR_SEETA_DEVICE_1013',11013);
define('ERR_SEETA_DEVICE_1014',11014);
define('ERR_SEETA_DEVICE_1015',11015);
define('ERR_SEETA_DEVICE_2201',12201);
define('ERR_SEETA_DEVICE_2202',12202);
define('ERR_SEETA_DEVICE_2203',12203);
define('ERR_SEETA_DEVICE_2204',12204);
define('ERR_SEETA_DEVICE_2206',12206);
define('ERR_SEETA_DEVICE_2207',12207);
define('ERR_SEETA_DEVICE_2208',12208);
define('ERR_SEETA_DEVICE_2209',12209);
define('ERR_SEETA_DEVICE_2210',12210);
define('ERR_SEETA_DEVICE_2211',12211);
define('ERR_SEETA_DEVICE_2212',12212);
define('ERR_SEETA_DEVICE_2213',12213);
define('ERR_SEETA_DEVICE_2214',12214);
define('ERR_SEETA_DEVICE_2215',12215);
define('ERR_SEETA_DEVICE_2216',12216);
define('ERR_SEETA_DEVICE_2301',12301);
define('ERR_SEETA_DEVICE_2302',12302);
define('ERR_SEETA_DEVICE_2303',12303);
define('ERR_SEETA_DEVICE_2304',12304);
define('ERR_SEETA_DEVICE_2305',12305);
define('ERR_SEETA_DEVICE_2306',12306);
define('ERR_SEETA_DEVICE_2401',12401);
define('ERR_SEETA_DEVICE_2402',12402);
define('ERR_SEETA_DEVICE_2403',12403);
define('ERR_SEETA_DEVICE_2404',12404);
define('ERR_SEETA_DEVICE_2405',12405);
define('ERR_SEETA_DEVICE_2406',12406);
define('ERR_SEETA_DEVICE_2901',12901);
define('ERR_SEETA_DEVICE_2902',12902);
define('ERR_SEETA_DEVICE_2903',12903);
define('ERR_SEETA_DEVICE_2904',12904);
define('ERR_SEETA_DEVICE_3001',13001);
define('ERR_SEETA_DEVICE_3002',13002);
define('ERR_SEETA_DEVICE_3003',13003);
define('ERR_SEETA_DEVICE_3004',13004);
define('ERR_SEETA_DEVICE_3005',13005);
define('ERR_SEETA_DEVICE_3006',13006);
define('ERR_SEETA_DEVICE_3007',13007);
define('ERR_SEETA_DEVICE_3008',13008);
define('ERR_SEETA_DEVICE_3009',13009);
define('ERR_SEETA_DEVICE_3010',13010);
define('ERR_SEETA_DEVICE_3011',13011);
define('ERR_SEETA_DEVICE_3012',13012);

