package group

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
)

type SetForm struct {
	GroupId      string      `json:"group_id" binding:"required"`
	DeviceParams interface{} `json:"device_params" binding:"required"`
}

func Set(ctx *gin.Context) {
	var setForm SetForm

	err := ctx.ShouldBindJSON(&setForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	groupSetDefault := seetaDeviceManager.GroupSetDefault{
		GroupIds:     []string{setForm.GroupId},
		DeviceParams: setForm.DeviceParams,
	}
	var res seetaDeviceManager.OnlyRes

	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleGroup,
		seetaDeviceManager.ActionSetDefault,
		groupSetDefault,
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

	controller.JsonByCode(ctx, errs.Success)
}
