package device

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
	"net/http"
)

func Discover(ctx *gin.Context) {
	var res seetaDeviceManager.ResWithDevices
	errCode := seetaDeviceManager.Get(
		seetaDeviceManager.ModuleDevice,
		seetaDeviceManager.ActionDiscover,
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
		"devices":      res.Devices,
	})
}
