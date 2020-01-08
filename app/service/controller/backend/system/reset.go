package system

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/service/manager/tokenManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
)

type ResetForm struct {
	ResetTypes []int64 `json:"reset_types" binding:"required,min=1,max=2"`
	Password   string  `json:"password" binding:"required"`
}

func Reset(ctx *gin.Context) {
	var resetForm ResetForm

	err := ctx.ShouldBindJSON(&resetForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	adminId, err := tokenManager.GetAdminId(ctx.GetHeader(tokenManager.TokenName))
	if err != nil {
		controller.JsonByCode(ctx, errs.TokenFind)
		return
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()
	var admin mongo.Admin

	err = admin.GetById(db, adminId)
	if err != nil {
		controller.JsonByCode(ctx, errs.DbQuery)
		return
	}

	if !admin.VerifyPwd(resetForm.Password) {
		controller.JsonByCode(ctx, errs.AdminPassword)
		return
	}

	systemReset := seetaDeviceManager.SystemReset{
		ResetTypes: resetForm.ResetTypes,
	}

	var res seetaDeviceManager.OnlyRes

	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleSystem,
		seetaDeviceManager.ActionReset,
		systemReset,
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
