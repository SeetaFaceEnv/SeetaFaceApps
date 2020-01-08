package middleware

import (
	"SeetaDeviceCommunity/utils"
	"github.com/gin-gonic/gin"
)

func Init(engine *gin.Engine) {
	utils.PrintLine("LOAD MIDDLEWARE")
	engine.Use(
		cors(),
		optionsHeader(),
		access(),
		auth(),
	)
}
