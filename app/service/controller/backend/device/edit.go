package device

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
)

type EditForm struct {
	DeviceCode   string                         `json:"device_code" binding:"required"`
	GroupId      string                         `json:"group_id" binding:"omitempty"`
	DeviceParams seetaDeviceManager.DeviceParam `json:"device_params" binding:"omitempty"`
}

func Edit(ctx *gin.Context) {
	var editForm EditForm

	err := ctx.ShouldBindJSON(&editForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	deviceSet := seetaDeviceManager.DeviceSet{
		DeviceCodes:  []string{editForm.DeviceCode},
		GroupId:      &editForm.GroupId,
		DeviceParams: editForm.DeviceParams,
	}
	var res seetaDeviceManager.OnlyRes
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleDevice,
		seetaDeviceManager.ActionSet,
		deviceSet,
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
