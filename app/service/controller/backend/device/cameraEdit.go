package device

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"errors"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo"
)

type CameraEditForm struct {
	DeviceCode        string                         `json:"device_code" binding:"required"`
	DelTimeTemplateId string                         `json:"del_time_template_id" binding:"omitempty"`
	CameraParam       seetaDeviceManager.CameraParam `json:"camera_param" binding:"required"`
}

func CameraEdit(ctx *gin.Context) {
	var cameraEditForm CameraEditForm

	err := ctx.ShouldBindJSON(&cameraEditForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	db := mongoManager.GetDB()
	var timeTemplate mongo.TimeTemplate

	if cameraEditForm.CameraParam.TimeTemplateId != "" {
		err = timeTemplate.GetById(db, cameraEditForm.CameraParam.TimeTemplateId)
		if err != nil {
			controller.JsonByCode(ctx, errs.DbQuery)
			return
		}

		cameraEditForm.CameraParam.TimeSlots = timeTemplate.TimeSlots
	} else {
		cameraEditForm.CameraParam.TimeSlots = make([]mongo.TimeSlot, 0)
	}

	if len(cameraEditForm.CameraParam.FilterTypeWeb) > 0 {
		cameraEditForm.CameraParam.FilterType = 0
		for _, num := range cameraEditForm.CameraParam.FilterTypeWeb {
			cameraEditForm.CameraParam.FilterType += num
		}
	}

	cameraEditForm.CameraParam.GatherSwitch = constants.GatherMap[configManager.Conf.Server.GatherSwitch]
	cameraEdit := seetaDeviceManager.CameraEdit{
		DeviceCode:   cameraEditForm.DeviceCode,
		CameraParams: []seetaDeviceManager.CameraParam{cameraEditForm.CameraParam},
	}

	var res seetaDeviceManager.ResWithDevice
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleCamera,
		seetaDeviceManager.ActionEdit,
		cameraEdit,
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

	if cameraEditForm.DelTimeTemplateId != "" {
		err = timeTemplate.PullDeviceCodeCamera(
			db,
			cameraEditForm.DelTimeTemplateId,
			cameraEditForm.DeviceCode,
			cameraEditForm.CameraParam.Id,
		)
		if err != nil {
			controller.JsonByCode(ctx, errs.DbOperate)
			return
		}
	}

	if cameraEditForm.CameraParam.TimeTemplateId != "" {
		err = timeTemplate.PushDeviceCodeCamera(db, cameraEditForm.DeviceCode, cameraEditForm.CameraParam.Id)
		if err != nil && !errors.Is(err, mgo.ErrNotFound) {
			controller.JsonByCode(ctx, errs.DbOperate)
			return
		}
	}

	controller.JsonByCode(ctx, errs.Success)
}
