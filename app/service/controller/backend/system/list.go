package system

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
	"net/http"
)

func List(ctx *gin.Context) {
	var res seetaDeviceManager.ResWithSystem
	errCode := seetaDeviceManager.Get(
		seetaDeviceManager.ModuleSystem,
		seetaDeviceManager.ActionGet,
		nil,
		&res,
	)
	if errCode != errs.Success {
		controller.JsonByCode(ctx, errCode)
		return
	}

	if res.Res != seetaDeviceManager.Success {
		controller.JsonBySeetaDeviceMsg(ctx, res.Msg)
		return
	}

	ctx.JSON(http.StatusOK, gin.H{
		controller.Res: errs.Success,
		controller.Msg: errs.GetMsgByCode(errs.Success),
		"param":        res.Param,
	})
}
