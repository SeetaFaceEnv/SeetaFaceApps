package device

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
)

type CloseForm struct {
	DeviceCode string `json:"device_code" binding:"required"`
	CameraId   string `json:"camera_id" binding:"required"`
}

func Close(ctx *gin.Context) {
	var closeForm CloseForm

	err := ctx.ShouldBindJSON(&closeForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	deviceClose := seetaDeviceManager.DeviceRelayClose{
		DeviceCode: closeForm.DeviceCode,
		CameraId:   closeForm.CameraId,
	}

	var res seetaDeviceManager.ResWithDevice
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleDevice,
		seetaDeviceManager.ActionRelayClose,
		deviceClose,
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

	if !res.DeviceResult {
		controller.JsonByDeviceMsg(ctx, errs.DeviceFailed)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
