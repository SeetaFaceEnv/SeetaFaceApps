package router

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/service/controller/devend/device"
	"SeetaDeviceCommunity/service/controller/devend/status"
	"github.com/gin-gonic/gin"
)

func DevDeviceRouter(engine *gin.Engine) {
	r := engine.Group(constants.DevDevice)
	{
		r.POST(constants.Auth, device.Auth)
		r.POST(constants.Gather, device.Gather)
		r.POST(constants.Report, device.Report)
	}
}

func DevStatusRouter(engine *gin.Engine) {
	r := engine.Group(constants.DevStatus)
	{
		r.POST(constants.Callback, status.Callback)
	}
}
