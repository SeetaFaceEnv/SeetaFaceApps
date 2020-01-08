package device

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
)

type CameraDelForm struct {
	DeviceCode     string `json:"device_code" binding:"required"`
	TimeTemplateId string `json:"time_template_id" binding:"omitempty"`
	CameraId       string `json:"camera_id" binding:"required"`
}

func CameraDel(ctx *gin.Context) {
	var cameraDelForm CameraDelForm

	err := ctx.ShouldBindJSON(&cameraDelForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var timeTemplate mongo.TimeTemplate

	cameraDel := seetaDeviceManager.CameraDel{
		DeviceCode: cameraDelForm.DeviceCode,
		CameraIds:  []string{cameraDelForm.CameraId},
	}

	var res seetaDeviceManager.ResWithDevice
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleCamera,
		seetaDeviceManager.ActionDelete,
		cameraDel,
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

	if cameraDelForm.TimeTemplateId != "" {
		err = timeTemplate.PullDeviceCodeCamera(
			db,
			cameraDelForm.TimeTemplateId,
			cameraDelForm.DeviceCode,
			cameraDelForm.CameraId,
		)
		if err != nil {
			controller.JsonByCode(ctx, errs.DbOperate)
			return
		}
	}

	controller.JsonByCode(ctx, errs.Success)
}
