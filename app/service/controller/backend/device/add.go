package device

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/logManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
	"strconv"
)

type AddForm struct {
	DeviceCode string `json:"device_code" binding:"required"`
	GroupId    string `json:"group_id" binding:"omitempty"`
	Type       int64  `json:"type" binding:"required,min=1,max=4"`
}

func Add(ctx *gin.Context) {
	var addForm AddForm

	err := ctx.ShouldBindJSON(&addForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	deviceAdd := seetaDeviceManager.DeviceAdd{
		DeviceCodes: []string{addForm.DeviceCode},
		GroupId:     addForm.GroupId,
	}
	var res seetaDeviceManager.ResWithDeviceAllParam
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleDevice,
		seetaDeviceManager.ActionAdd,
		deviceAdd,
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

	if !res.DeviceResults[0].Result {
		controller.JsonByDeviceMsg(ctx, errs.DeviceFailed)
		return
	}

	seetaDeviceRes := res.DeviceResults[0]
	callbackAddr := configManager.Conf.SeetaDevice.CallbackAddr

	switch addForm.Type {
	case mongo.DevicePcGateway:
		seetaDeviceRes.DeviceParams.ReportUrl = callbackAddr + controller.ReportRouter
		//seetaDeviceRes.DeviceParams.AuthUrl = callbackAddr + controller.AuthRouter

		var tmpRes seetaDeviceManager.OnlyRes
		errCode = seetaDeviceManager.Post(
			seetaDeviceManager.ModuleDevice,
			seetaDeviceManager.ActionSet,
			seetaDeviceManager.DeviceSet{
				DeviceCodes:  []string{addForm.DeviceCode},
				GroupId:      nil,
				DeviceParams: seetaDeviceRes.DeviceParams,
			},
			&tmpRes,
		)
		if errCode != errs.Success || tmpRes.Res != seetaDeviceManager.Success {
			logManager.Warn("device: add: SeetaDevice set device<", addForm.DeviceCode, "> deviceParam error,errCode: ", strconv.Itoa(errCode))
		}
	case mongo.DeviceCard, mongo.DeviceAccess, mongo.DeviceGateway:
		for i := range seetaDeviceRes.CameraParams {
			seetaDeviceRes.CameraParams[i].ReportUrl = callbackAddr + controller.ReportRouter
			//seetaDeviceRes.CameraParams[i].AuthUrl = callbackAddr + controller.AuthRouter
			seetaDeviceRes.CameraParams[i].Name = "默认流" + strconv.Itoa(i+1)
			seetaDeviceRes.CameraParams[i].GatherSwitch = constants.GatherMap[configManager.Conf.Server.GatherSwitch]
		}

		var tmpRes seetaDeviceManager.ResWithDevice
		errCode = seetaDeviceManager.Post(
			seetaDeviceManager.ModuleCamera,
			seetaDeviceManager.ActionEdit,
			seetaDeviceManager.CameraEdit{
				DeviceCode:   addForm.DeviceCode,
				CameraParams: seetaDeviceRes.CameraParams,
			},
			&tmpRes,
		)
		if errCode != errs.Success || tmpRes.Res != seetaDeviceManager.Success || !tmpRes.DeviceResult {
			logManager.Warn("device: add: SeetaDevice set device<", addForm.DeviceCode, "> cameraParams error, errCode: ", strconv.Itoa(errCode))
		}
	}

	controller.JsonByCode(ctx, errs.Success)
}
