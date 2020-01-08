package device

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"encoding/json"
	"errors"
	"github.com/gin-gonic/gin"
	"os"
)

type UpdateForm struct {
	DeviceCodes string `form:"device_codes" binding:"required"`
}

func Update(ctx *gin.Context) {
	var updateForm UpdateForm

	deviceCodes := make([]string, 0)

	err := ctx.ShouldBind(&updateForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	err = json.Unmarshal([]byte(updateForm.DeviceCodes), &deviceCodes)
	if err != nil {
		controller.JsonByCode(ctx, errs.JsonParse)
		return
	}

	if len(deviceCodes) < 1 {
		controller.JsonByCode(ctx, errs.DeviceNotExist)
		return
	}

	fileName, errCode := controller.SaveFile(ctx, "apk")
	if errCode != errs.Success {
		controller.JsonByCode(ctx, errCode)
		return
	}

	defer func() {
		if err != nil || errCode != errs.Success {
			os.Remove(configManager.Conf.Path.Data + fileName)
		}
	}()

	deviceUpdate := seetaDeviceManager.DeviceUpdate{
		GroupIds:    nil,
		DeviceCodes: deviceCodes,
		ApkUrl:      configManager.Conf.SeetaDevice.CallbackAddr + controller.GenFileRouter(constants.DirApk, fileName),
	}

	deviceUpdate.Etag, err = controller.Md5(configManager.Conf.Path.Data + fileName)
	if err != nil {
		controller.JsonByCode(ctx, errs.FileParse)
		return
	}

	var res seetaDeviceManager.OnlyRes
	errCode = seetaDeviceManager.Post(
		seetaDeviceManager.ModuleDevice,
		seetaDeviceManager.ActionUpdate,
		deviceUpdate,
		&res,
	)
	if errCode != errs.Success {
		controller.JsonByCode(ctx, errCode)
		return
	}

	if res.Res != seetaDeviceManager.Success {
		err = errors.New(res.Msg)
		controller.JsonBySeetaDeviceMsg(ctx, res.Msg)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
