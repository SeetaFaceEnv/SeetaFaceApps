package register

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
)

type DeleteForm struct {
	UserId    string `form:"user_id" binding:"required"`
	Timestamp string `form:"timestamp" binding:"required"`
	SecretKey string `form:"secret_key" binding:"required"`
}

func Delete(ctx *gin.Context) {
	var deleteForm DeleteForm

	err := ctx.ShouldBind(&deleteForm)
	if err != nil {
		controller.JsonThirdByCode(ctx, errs.Param)
		return
	}

	if !controller.VerifySecretKey(deleteForm.SecretKey, deleteForm.Timestamp) {
		controller.JsonThirdByCode(ctx, errs.SecretKeyVerify)
		return
	}

	var res seetaDeviceManager.OnlyRes
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModulePerson,
		seetaDeviceManager.ActionDelete,
		seetaDeviceManager.PersonDel{PersonId: deleteForm.UserId},
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

	controller.JsonThirdByCode(ctx, errs.Success)
}
