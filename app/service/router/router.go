package router

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/utils"
	"github.com/gin-gonic/gin"
	"net/http"
)

func Init(engine *gin.Engine) {
	utils.PrintLine("LOAD ROUTER")
	//backend
	BackAdminRouter(engine)
	BackDeviceRouter(engine)
	BackDeviceLogRouter(engine)
	BackFileRouter(engine)
	BackGroupRouter(engine)
	BackPassRecordRouter(engine)
	BackPersonRouter(engine)
	BackRequestLogRouter(engine)
	BackStyleRouter(engine)
	BackSystemRouter(engine)
	BackTimeTemplateRouter(engine)
	BackImageLogRouter(engine)
	BackRegisterRouter(engine)

	//devend
	DevDeviceRouter(engine)
	DevStatusRouter(engine)

	//frontend
	engine.Static(constants.Frontend, "resource/templates")

	//redirect / => /frontend
	engine.GET("/", func(context *gin.Context) {
		context.Redirect(http.StatusMovedPermanently, constants.Frontend)
	})
}
