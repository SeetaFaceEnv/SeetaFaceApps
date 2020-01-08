package device

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
)

type OpenForm struct {
	DeviceCode string `json:"device_code" binding:"required"`
	PersonId   string `json:"person_id" binding:"omitempty"`
	Lasting    int64  `json:"lasting" binding:"omitempty,min=1,max=2"`
	CameraId   string `json:"camera_id" binding:"required"`
}

func Open(ctx *gin.Context) {
	var openForm OpenForm

	err := ctx.ShouldBindJSON(&openForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	var (
		res     seetaDeviceManager.ResWithDevice
		reqData interface{}
		action  string
	)
	switch {
	case openForm.Lasting == constants.SwitchOpen:
		reqData = seetaDeviceManager.DeviceRelayOpen{
			DeviceCode: openForm.DeviceCode,
			CameraId:   openForm.CameraId,
		}
		action = seetaDeviceManager.ActionRelayOpen
	case openForm.PersonId != "":
		reqData = seetaDeviceManager.DevicePass{
			DeviceCode: openForm.DeviceCode,
			CameraId:   openForm.CameraId,
			PersonId:   openForm.PersonId,
		}
		action = seetaDeviceManager.ActionPass
	default:
		reqData = seetaDeviceManager.DeviceOpen{
			DeviceCode: openForm.DeviceCode,
			CameraId:   openForm.CameraId,
		}
		action = seetaDeviceManager.ActionOpen
	}

	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleDevice,
		action,
		reqData,
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
