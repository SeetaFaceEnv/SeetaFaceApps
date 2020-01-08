package main

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/middleware"
	"SeetaDeviceCommunity/service/router"
	"SeetaDeviceCommunity/utils"
	"fmt"
	"github.com/gin-gonic/gin"
	"mime"

	_ "SeetaDeviceCommunity/service/manager/cronManager"
)

var engine *gin.Engine

func main() {
	//print version
	utils.PrintLine("VERSION")
	fmt.Println(constants.Version)

	//server run
	utils.PrintLine("SERVER RUN")
	fmt.Println("port: ", configManager.Conf.Server.Port)
	err := engine.Run(":" + configManager.Conf.Server.Port)
	if err != nil {
		panic(err)
	}
}

func init() {
	initMime()

	utils.PrintLine("INIT SERVER")
	fmt.Println("server mode: ", configManager.Conf.Server.Mode)
	gin.SetMode(configManager.Conf.Server.Mode)

	engine = gin.Default()

	//init middleware
	middleware.Init(engine)

	//init router
	router.Init(engine)
}

func initMime() {
	utils.PrintLine("LOAD MIME")
	for contentType, extension := range constants.MimeMap {
		fmt.Println(contentType, " ---> ", extension)
		err := mime.AddExtensionType(extension, contentType)
		if err != nil {
			panic(err)
		}
	}
}
