package router

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/service/controller/backend/admin"
	"SeetaDeviceCommunity/service/controller/backend/device"
	"SeetaDeviceCommunity/service/controller/backend/deviceLog"
	"SeetaDeviceCommunity/service/controller/backend/file"
	"SeetaDeviceCommunity/service/controller/backend/group"
	"SeetaDeviceCommunity/service/controller/backend/imageLog"
	"SeetaDeviceCommunity/service/controller/backend/passRecord"
	"SeetaDeviceCommunity/service/controller/backend/person"
	"SeetaDeviceCommunity/service/controller/backend/register"
	"SeetaDeviceCommunity/service/controller/backend/requestLog"
	"SeetaDeviceCommunity/service/controller/backend/style"
	"SeetaDeviceCommunity/service/controller/backend/system"
	"SeetaDeviceCommunity/service/controller/backend/timeTemplate"
	"github.com/gin-gonic/gin"
)

func BackAdminRouter(engine *gin.Engine) {
	r := engine.Group(constants.BackAdmin)
	{
		r.POST(constants.Add, admin.Add)
		r.GET(constants.Captcha, admin.Captcha)
		r.POST(constants.Del, admin.Del)
		r.POST(constants.Edit, admin.Edit)
		r.POST(constants.List, admin.List)
		r.POST(constants.Login, admin.Login)
		r.POST(constants.Logout, admin.Logout)
	}
}

func BackDeviceRouter(engine *gin.Engine) {
	r := engine.Group(constants.BackDevice)
	{
		r.POST(constants.Add, device.Add)
		r.POST(constants.Bind, device.Bind)
		r.POST(constants.CameraAdd, device.CameraAdd)
		r.POST(constants.CameraEdit, device.CameraEdit)
		r.POST(constants.CameraDel, device.CameraDel)
		r.POST(constants.Close, device.Close)
		r.POST(constants.Del, device.Del)
		r.GET(constants.Discover, device.Discover)
		r.POST(constants.Edit, device.Edit)
		r.POST(constants.List, device.List)
		r.POST(constants.Open, device.Open)
		r.POST(constants.Reload, device.Reload)
		r.POST(constants.Test, device.Test)
		r.POST(constants.Unbind, device.Unbind)
		r.POST(constants.Update, device.Update)
	}
}

func BackDeviceLogRouter(engine *gin.Engine) {
	r := engine.Group(constants.BackDeviceLog)
	{
		r.POST(constants.List, deviceLog.List)
	}
}

func BackFileRouter(engine *gin.Engine) {
	r := engine.Group(constants.BackFile)
	{
		r.GET(constants.Get, file.Get)
	}
}

func BackGroupRouter(engine *gin.Engine) {
	r := engine.Group(constants.BackGroup)
	{
		r.POST(constants.Add, group.Add)
		r.POST(constants.Del, group.Del)
		r.POST(constants.List, group.List)
		r.POST(constants.Set, group.Set)
	}
}

func BackPassRecordRouter(engine *gin.Engine) {
	r := engine.Group(constants.BackPassRecord)
	{
		r.POST(constants.List, passRecord.List)
	}
}

func BackPersonRouter(engine *gin.Engine) {
	r := engine.Group(constants.BackPerson)
	{
		r.POST(constants.Add, person.Add)
		r.POST(constants.Del, person.Del)
		r.POST(constants.Edit, person.Edit)
		r.POST(constants.ImageAdd, person.ImageAdd)
		r.POST(constants.ImageDel, person.ImageDel)
		r.POST(constants.List, person.List)
		r.POST(constants.QrCode, person.QrCode)
		r.POST(constants.AvatarUpdate, person.AvatarUpdate)
	}
}

func BackRequestLogRouter(engine *gin.Engine) {
	r := engine.Group(constants.BackRequestLog)
	{
		r.POST(constants.List, requestLog.List)
	}
}

func BackStyleRouter(engine *gin.Engine) {
	r := engine.Group(constants.BackStyle)
	{
		r.POST(constants.Add, style.Add)
		r.POST(constants.Del, style.Del)
		r.POST(constants.Edit, style.Edit)
		r.POST(constants.List, style.List)
	}
}

func BackSystemRouter(engine *gin.Engine) {
	r := engine.Group(constants.BackSystem)
	{
		r.GET(constants.List, system.List)
		r.POST(constants.Reset, system.Reset)
		r.POST(constants.Set, system.Set)
	}
}

func BackTimeTemplateRouter(engine *gin.Engine) {
	r := engine.Group(constants.BackTimeTemplate)
	{
		r.POST(constants.Add, timeTemplate.Add)
		r.POST(constants.Del, timeTemplate.Del)
		r.POST(constants.Edit, timeTemplate.Edit)
		r.POST(constants.List, timeTemplate.List)
	}
}

func BackImageLogRouter(engine *gin.Engine) {
	r := engine.Group(constants.BackImageLog)
	{
		r.POST(constants.List, imageLog.List)
	}
}

func BackRegisterRouter(engine *gin.Engine) {
	r := engine.Group(constants.BackRegister)
	{
		r.POST(constants.Add, register.Add)
		r.POST(constants.Edit, register.Edit)
		r.POST(constants.Delete, register.Delete)
		r.POST(constants.Get, register.Get)
	}
}
