package errs

import (
	"errors"
	"fmt"
)

const DeviceFailed = "执行失败"

const (
	token = "token"

	Success        = 0
	SeetaDeviceRes = 1

	Param          = 1001
	HttpParse      = 1002
	DbOperate      = 1003
	DbQuery        = 1004
	ImageNotUpload = 1005
	ImageSave      = 1006
	Read           = 1007
	JsonParse      = 1008
	FileParse      = 1009

	CodeExist    = 1011
	CodeGenerate = 1012
	CodeVerify   = 1013
	CodeSend     = 1014
	CodeNotExist = 1015

	AdminNotExist = 1021
	AdminExist    = 1022
	AdminPassword = 1023
	AdminAdd      = 1024
	AdminUpdate   = 1025
	AdminSelfDel  = 1026

	TokenGenerate = 1031
	TokenDelete   = 1032
	TokenFind     = 1033

	TimeTemplateExist     = 1041
	TimeTemplateNotExist  = 1042
	TimeTemplateAdd       = 1043
	TimeTemplateUpdate    = 1044
	TimeTemplateBind      = 1045
	TimeTemplateNameExist = 1046

	StyleExist     = 1051
	StyleNotExist  = 1052
	StyleNameExist = 1053
	StyleAdd       = 1054
	StyleUpdate    = 1055
	StyleBind      = 1056

	HttpNewRequest = 1062
	HttpRequest    = 1063
	HttpResponse   = 1064

	FileSize       = 1071
	QrCodeGenerate = 1072
	QrCodeSend     = 1073

	FileSave = 1081

	MqttDisconnected = 1091
	MqttSend         = 1092

	SecretKeyVerify = 1101

	ThirdPersonImageNum = 1111

	DeviceNotExist = 1121
)

var (
	NotExist      = errors.New("not exist")
	TokenNotExist = wrapErr(token, NotExist)

	Format   = errors.New("format")
	IdFormat = wrapErr("id", Format)

	Parse = errors.New("parse")
)

var (
	errMap = map[int]string{
		Success:        "成功",
		Param:          "参数错误",
		HttpParse:      "请求解析错误",
		DbOperate:      "数据库操作错误",
		DbQuery:        "数据库查询错误",
		ImageNotUpload: "没有图片上传",
		ImageSave:      "图片保存失败",
		Read:           "读取参数错误",
		JsonParse:      "json解析错误",
		FileParse:      "文件解析错误",

		CodeExist:    "验证码已存在",
		CodeGenerate: "验证码生成错误",
		CodeVerify:   "验证码错误",
		CodeSend:     "验证码发送错误",
		CodeNotExist: "请输入验证码",

		AdminExist:    "管理员已存在",
		AdminNotExist: "管理员不存在",
		AdminPassword: "管理员密码错误",
		AdminAdd:      "管理员添加错误",
		AdminUpdate:   "管理员更新错误",
		AdminSelfDel:  "管理员无法删除自己",

		TokenGenerate: "token生成错误",
		TokenDelete:   "token删除错误",
		TokenFind:     "token查询错误",

		TimeTemplateExist:     "时间模板已存在",
		TimeTemplateNotExist:  "时间模板不存在",
		TimeTemplateAdd:       "时间模板添加错误",
		TimeTemplateUpdate:    "时间模板更新错误",
		TimeTemplateBind:      "时间模板被绑定",
		TimeTemplateNameExist: "时间模板名称已存在",

		StyleExist:     "样式已存在",
		StyleNotExist:  "样式不存在",
		StyleAdd:       "样式添加错误",
		StyleUpdate:    "样式更新错误",
		StyleBind:      "样式被绑定",
		StyleNameExist: "样式名称已存在",

		HttpNewRequest: "建立请求错误",
		HttpRequest:    "发送请求错误",
		HttpResponse:   "设备管理平台响应错误",
		SeetaDeviceRes: "设备管理平台返回错误",

		FileSize: "文件过大",

		QrCodeGenerate: "生成二维码错误",
		QrCodeSend:     "二维码发送错误",

		FileSave: "文件保存错误",

		MqttDisconnected: "mqtt连接错误",
		MqttSend:         "mqtt消息发送失败",

		SecretKeyVerify: "secret_key校验错误",

		ThirdPersonImageNum: "人员必须要有一张照片",

		DeviceNotExist: "设备不存在",
	}
)

func wrapErr(prefix string, err error) error {
	return fmt.Errorf("%q:%w", prefix, err)
}

func GetMsgByCode(errCode int) string {
	msg, ok := errMap[errCode]
	if ok {
		return msg
	}

	return "unknown error"
}
