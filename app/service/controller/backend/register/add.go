package register

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/logManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo/bson"
	"net/http"
	"os"
	"strconv"
)

type AddForm struct {
	UserId     string            `form:"user_id" binding:"omitempty"`
	Timestamp  string            `form:"timestamp" binding:"required"`
	SecretKey  string            `form:"secret_key" binding:"required"`
	Image      string            `form:"image" binding:"required"`
	Attributes map[string]string `form:"attributes" binding:"omitempty"`
}

func Add(ctx *gin.Context) {
	var addForm AddForm
	var errCode = 0

	err := ctx.ShouldBind(&addForm)
	if err != nil {
		controller.JsonThirdByCode(ctx, errs.Param)
		return
	}

	if !controller.VerifySecretKey(addForm.SecretKey, addForm.Timestamp) {
		controller.JsonThirdByCode(ctx, errs.SecretKeyVerify)
		return
	}

	if addForm.UserId == "" {
		addForm.UserId = bson.NewObjectId().Hex()
	}

	fileName, err := controller.SaveBase64(addForm.Image, configManager.Conf.Path.Data)
	if err != nil {
		controller.JsonThirdByCode(ctx, errs.FileSave)
		return
	}
	defer os.Remove(configManager.Conf.Path.Data + fileName)

	personAdd := seetaDeviceManager.ThirdPersonAdd{
		PersonId:      addForm.UserId,
		PortraitImage: configManager.Conf.SeetaDevice.CallbackAddr + controller.GenFileRouter(constants.DirPerson, fileName),
		Attributes:    addForm.Attributes,
	}

	var res seetaDeviceManager.OnlyRes
	errCode = seetaDeviceManager.Post(
		seetaDeviceManager.ModulePerson,
		seetaDeviceManager.ActionAdd,
		personAdd,
		&res,
	)

	if errCode != errs.Success {
		controller.JsonThirdByCode(ctx, errCode)
		return
	}

	if res.Res != seetaDeviceManager.Success {
		controller.JsonBySeetaDeviceMsg(ctx, res.Msg)
		return
	}

	defer func() {
		if err != nil || errCode != errs.Success {
			var tmpRes seetaDeviceManager.OnlyRes
			errCode = seetaDeviceManager.Post(
				seetaDeviceManager.ModulePerson,
				seetaDeviceManager.ActionDelete,
				seetaDeviceManager.PersonDel{PersonId: addForm.UserId},
				&tmpRes,
			)
			if errCode != errs.Success || tmpRes.Res != seetaDeviceManager.Success {
				logManager.Error("registerAdd: person<", addForm.UserId, "> delete failed, errCode: ", strconv.Itoa(errCode))
			}
		}
	}()

	var imageRes seetaDeviceManager.ResWithImageId
	errCode = seetaDeviceManager.Post(
		seetaDeviceManager.ModulePerson,
		seetaDeviceManager.ActionAddImage,
		seetaDeviceManager.PersonAddImage{
			PersonId: addForm.UserId,
			ImageUrl: configManager.Conf.SeetaDevice.CallbackAddr + controller.GenFileRouter(constants.DirPerson, fileName),
		},
		&imageRes,
	)
	if errCode != errs.Success {
		controller.JsonThirdByCode(ctx, errCode)
		return
	}

	if imageRes.Res != seetaDeviceManager.Success {
		errCode = errs.SeetaDeviceRes
		controller.JsonByCode(ctx, errCode)
		return
	}

	ctx.JSON(http.StatusOK, gin.H{
		controller.Result: errs.Success,
		"user_id":         addForm.UserId,
		"image_id":        imageRes.ImageId,
	})
}
