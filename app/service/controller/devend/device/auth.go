package device

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"github.com/gin-gonic/gin"
)

func Auth(ctx *gin.Context) {
	controller.JsonByCode(ctx, errs.Success)
}
