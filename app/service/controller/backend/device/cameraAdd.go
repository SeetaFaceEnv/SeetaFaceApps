package device

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo/bson"
)

type CameraAddForm struct {
	DeviceCode  string                         `json:"device_code" binding:"required"`
	CameraParam seetaDeviceManager.CameraParam `json:"camera_param" binding:"required"`
}

func CameraAdd(ctx *gin.Context) {
	var cameraAddForm CameraAddForm

	err := ctx.ShouldBindJSON(&cameraAddForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	if cameraAddForm.CameraParam.Id == "" {
		cameraAddForm.CameraParam.Id = bson.NewObjectId().Hex()
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var timeTemplate mongo.TimeTemplate

	if cameraAddForm.CameraParam.TimeTemplateId != "" {
		err = timeTemplate.GetById(db, cameraAddForm.CameraParam.TimeTemplateId)
		if err != nil {
			controller.JsonByCode(ctx, errs.DbQuery)
			return
		}

		cameraAddForm.CameraParam.TimeSlots = timeTemplate.TimeSlots
	}

	if len(cameraAddForm.CameraParam.FilterTypeWeb) > 0 {
		cameraAddForm.CameraParam.FilterType = 0
		for _, num := range cameraAddForm.CameraParam.FilterTypeWeb {
			cameraAddForm.CameraParam.FilterType += num
		}
	}
	cameraAddForm.CameraParam.GatherSwitch = constants.GatherMap[configManager.Conf.Server.GatherSwitch]
	cameraAdd := seetaDeviceManager.CameraAdd{
		DeviceCode:   cameraAddForm.DeviceCode,
		CameraParams: []seetaDeviceManager.CameraParam{cameraAddForm.CameraParam},
	}

	var res seetaDeviceManager.ResWithDevice
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleCamera,
		seetaDeviceManager.ActionAdd,
		cameraAdd,
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

	if cameraAddForm.CameraParam.TimeTemplateId != "" {
		err = timeTemplate.PushDeviceCodeCamera(db, cameraAddForm.DeviceCode, cameraAddForm.CameraParam.Id)
		if err != nil {
			controller.JsonByCode(ctx, errs.DbOperate)
			return
		}
	}

	controller.JsonByCode(ctx, errs.Success)
}
