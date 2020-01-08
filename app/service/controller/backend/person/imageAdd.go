package person

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
	"os"
)

type ImageAddForm struct {
	PersonId string `form:"person_id" binding:"required"`
}

func ImageAdd(ctx *gin.Context) {
	var imageAddForm ImageAddForm

	err := ctx.ShouldBind(&imageAddForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	fileName, errCode := controller.SaveFile(ctx, "image")
	if errCode != errs.Success {
		controller.JsonByCode(ctx, errCode)
		return
	}
	defer os.Remove(configManager.Conf.Path.Data + fileName)

	personAddImage := seetaDeviceManager.PersonAddImage{
		PersonId: imageAddForm.PersonId,
		ImageUrl: configManager.Conf.SeetaDevice.CallbackAddr + controller.GenFileRouter(constants.DirPerson, fileName),
	}

	var res seetaDeviceManager.OnlyRes
	errCode = seetaDeviceManager.Post(
		seetaDeviceManager.ModulePerson,
		seetaDeviceManager.ActionAddImage,
		personAddImage,
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
