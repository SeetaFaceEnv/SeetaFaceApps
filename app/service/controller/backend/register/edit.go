package register

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
	"net/http"
	"os"
)

type EditForm struct {
	UserId     string            `form:"user_id" binding:"required"`
	Timestamp  string            `form:"timestamp" binding:"required"`
	SecretKey  string            `form:"secret_key" binding:"required"`
	DelImageId string            `form:"del_image_id" binding:"omitempty"`
	Image      string            `form:"image" binding:"omitempty"`
	Attributes map[string]string `form:"attributes" binding:"omitempty"`
}

func Edit(ctx *gin.Context) {
	var editForm EditForm

	err := ctx.ShouldBind(&editForm)
	if err != nil {
		controller.JsonThirdByCode(ctx, errs.Param)
		return
	}

	if !controller.VerifySecretKey(editForm.SecretKey, editForm.Timestamp) {
		controller.JsonThirdByCode(ctx, errs.SecretKeyVerify)
		return
	}

	if (editForm.DelImageId == "" && editForm.Image != "") ||
		(editForm.DelImageId != "" && editForm.Image == "") {
		controller.JsonThirdByCode(ctx, errs.ThirdPersonImageNum)
		return
	}

	fileUrl := ""
	if editForm.Image != "" {
		fileName, err := controller.SaveBase64(editForm.Image, configManager.Conf.Path.Data)
		if err != nil {
			controller.JsonThirdByCode(ctx, errs.FileSave)
			return
		}
		defer os.Remove(configManager.Conf.Path.Data + fileName)

		fileUrl = configManager.Conf.SeetaDevice.CallbackAddr + controller.GenFileRouter(constants.DirPerson, fileName)
	}

	if editForm.Attributes != nil || editForm.Image != "" {
		personEdit := seetaDeviceManager.ThirdPersonEdit{
			PersonId:   editForm.UserId,
			Attributes: editForm.Attributes,
		}

		if fileUrl != "" {
			personEdit.PortraitImage = &fileUrl
		}

		var res seetaDeviceManager.OnlyRes
		errCode := seetaDeviceManager.Post(
			seetaDeviceManager.ModulePerson,
			seetaDeviceManager.ActionEdit,
			personEdit,
			&res,
		)
		if errCode != errs.Success {
			controller.JsonThirdByCode(ctx, errCode)
			return
		}
		if res.Res != seetaDeviceManager.Success {
			controller.JsonThirdByCode(ctx, errs.SeetaDeviceRes)
			return
		}
	}

	if editForm.DelImageId != "" {
		var res seetaDeviceManager.OnlyRes
		errCode := seetaDeviceManager.Post(
			seetaDeviceManager.ModulePerson,
			seetaDeviceManager.ActionDeleteImage,
			seetaDeviceManager.PersonDelImage{
				PersonId: editForm.UserId,
				ImageId:  editForm.DelImageId,
			},
			&res,
		)
		if errCode != errs.Success {
			controller.JsonThirdByCode(ctx, errCode)
			return
		}
		if res.Res != seetaDeviceManager.Success {
			controller.JsonThirdByCode(ctx, errs.SeetaDeviceRes)
			return
		}
	}

	imageId := ""
	if editForm.Image != "" {
		var res seetaDeviceManager.ResWithImageId
		errCode := seetaDeviceManager.Post(
			seetaDeviceManager.ModulePerson,
			seetaDeviceManager.ActionAddImage,
			seetaDeviceManager.PersonAddImage{
				PersonId: editForm.UserId,
				ImageUrl: fileUrl,
			},
			&res,
		)
		if errCode != errs.Success {
			controller.JsonThirdByCode(ctx, errCode)
			return
		}
		if res.Res != seetaDeviceManager.Success {
			controller.JsonThirdByCode(ctx, errs.SeetaDeviceRes)
			return
		}
		imageId = res.ImageId
	}

	ctx.JSON(http.StatusOK, gin.H{
		controller.Result: errs.Success,
		"image_id":        imageId,
	})
}
