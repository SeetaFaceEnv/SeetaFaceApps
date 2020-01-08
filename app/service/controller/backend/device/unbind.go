package device

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo/bson"
)

type UnbindForm struct {
	DeviceCode string  `json:"device_code" binding:"required"`
	ResetTypes []int64 `json:"reset_types" binding:"omitempty"`
}

func Unbind(ctx *gin.Context) {
	var unbindForm UnbindForm

	err := ctx.ShouldBindJSON(&unbindForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var style mongo.Style

	deviceResetStyle := seetaDeviceManager.DeviceResetStyle{
		DeviceCodes: []string{unbindForm.DeviceCode},
		ResetTypes:  unbindForm.ResetTypes,
	}

	var res seetaDeviceManager.OnlyRes
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleDevice,
		seetaDeviceManager.ActionResetStyle,
		deviceResetStyle,
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

	selector := bson.M{"status": mongo.StatusNormal}
	if len(unbindForm.ResetTypes) > 0 {
		selector["type"] = bson.M{"$in": unbindForm.ResetTypes}
	}

	err = style.PullByCond(db,
		selector,
		bson.M{"device_codes": unbindForm.DeviceCode},
	)
	if err != nil {
		controller.JsonByCode(ctx, errs.DbOperate)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
