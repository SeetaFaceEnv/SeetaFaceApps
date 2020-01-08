package style

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
	"os"
)

type DelForm struct {
	Id string `json:"id" binding:"required"`
}

func Del(ctx *gin.Context) {
	var delForm DelForm

	err := ctx.ShouldBindJSON(&delForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var style mongo.Style

	err = style.GetById(db, delForm.Id)
	if err != nil {
		controller.JsonByCode(ctx, errs.StyleNotExist)
		return
	}

	if len(style.DeviceCodes) > 0 {
		controller.JsonByCode(ctx, errs.StyleBind)
		return
	}

	err = style.Delete(db)
	if err != nil {
		controller.JsonByCode(ctx, errs.StyleUpdate)
		return
	}

	if style.Type != mongo.StyleMarquee {
		_ = os.Remove(configManager.Conf.Path.Data + style.Info)
	}

	controller.JsonByCode(ctx, errs.Success)
}
