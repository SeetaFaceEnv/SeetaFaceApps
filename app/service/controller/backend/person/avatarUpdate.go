package person

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"errors"
	"github.com/gin-gonic/gin"
	"os"
)

type AvatarUpdateForm struct {
	PersonId string `form:"person_id" binding:"required"`
}

func AvatarUpdate(ctx *gin.Context) {
	var avatarUpdateForm AvatarUpdateForm

	err := ctx.ShouldBind(&avatarUpdateForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	fileName, errCode := controller.SaveFile(ctx, "image")
	if errCode != errs.Success {
		controller.JsonByCode(ctx, errCode)
	}

	defer os.Remove(configManager.Conf.Path.Data + fileName)

	personEdit := seetaDeviceManager.PersonAvatarUpdate{
		PersonId:      avatarUpdateForm.PersonId,
		PortraitImage: configManager.Conf.SeetaDevice.CallbackAddr + controller.GenFileRouter(constants.DirPerson, fileName),
	}

	var res seetaDeviceManager.OnlyRes
	errCode = seetaDeviceManager.Post(
		seetaDeviceManager.ModulePerson,
		seetaDeviceManager.ActionEdit,
		personEdit,
		&res,
	)
	if errCode != errs.Success {
		controller.JsonByCode(ctx, errCode)
		return
	}

	if res.Res != seetaDeviceManager.Success {
		err = errors.New(errs.GetMsgByCode(errs.SeetaDeviceRes))
		controller.JsonBySeetaDeviceMsg(ctx, res.Msg)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
