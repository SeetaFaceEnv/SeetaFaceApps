package person

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
)

type ImageDelForm struct {
	PersonId string `json:"person_id" binding:"required"`
	ImageId  string `json:"image_id" binding:"required"`
}

func ImageDel(ctx *gin.Context) {
	var imageDelForm ImageDelForm

	err := ctx.ShouldBindJSON(&imageDelForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	personDelImage := seetaDeviceManager.PersonDelImage{
		PersonId: imageDelForm.PersonId,
		ImageId:  imageDelForm.ImageId,
	}

	var res seetaDeviceManager.OnlyRes
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModulePerson,
		seetaDeviceManager.ActionDeleteImage,
		personDelImage,
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
