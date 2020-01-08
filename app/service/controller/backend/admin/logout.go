package admin

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/tokenManager"
	"github.com/gin-gonic/gin"
)

func Logout(ctx *gin.Context) {
	err := tokenManager.DeleteInfo(ctx.GetHeader(tokenManager.TokenName))
	if err != nil {
		controller.JsonByCode(ctx, errs.TokenDelete)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
