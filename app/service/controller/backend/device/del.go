package device

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/service/manager/tokenManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo/bson"
)

type DelForm struct {
	DeviceCode string `json:"device_code" binding:"required"`
	Password   string `json:"password" binding:"required"`
}

func Del(ctx *gin.Context) {
	var delForm DelForm

	err := ctx.ShouldBindJSON(&delForm)
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

	var (
		style        mongo.Style
		admin        mongo.Admin
		timeTemplate mongo.TimeTemplate
	)

	err = admin.GetById(db, adminId)
	if err != nil {
		controller.JsonByCode(ctx, errs.DbQuery)
		return
	}

	if !admin.VerifyPwd(delForm.Password) {
		controller.JsonByCode(ctx, errs.AdminPassword)
		return
	}

	deviceDel := seetaDeviceManager.DeviceDel{DeviceCodes: []string{delForm.DeviceCode}}
	var res seetaDeviceManager.ResWithDeviceInfos

	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleDevice,
		seetaDeviceManager.ActionDelete,
		deviceDel,
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
		controller.JsonByDeviceMsg(ctx, res.DeviceResults[0].Info)
		return
	}

	err = style.PullByCond(
		db,
		bson.M{"status": mongo.StatusNormal},
		bson.M{"device_codes": delForm.DeviceCode},
	)
	if err != nil {
		controller.JsonByCode(ctx, errs.DbOperate)
		return
	}

	err = timeTemplate.PullDeviceCode(db, delForm.DeviceCode)
	if err != nil {
		controller.JsonByCode(ctx, errs.DbOperate)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
