package device

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/service/manager/tokenManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
)

type ReloadForm struct {
	DeviceCode string `json:"device_code" binding:"required"`
	Password   string `json:"password" binding:"required"`
}

func Reload(ctx *gin.Context) {
	var reloadForm ReloadForm

	err := ctx.ShouldBindJSON(&reloadForm)
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

	if !admin.VerifyPwd(reloadForm.Password) {
		controller.JsonByCode(ctx, errs.AdminPassword)
		return
	}

	deviceReconstruct := seetaDeviceManager.DeviceReconstruct{DeviceCodes: []string{reloadForm.DeviceCode}}
	var res seetaDeviceManager.OnlyRes
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleDevice,
		seetaDeviceManager.ActionReconstruct,
		deviceReconstruct,
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
