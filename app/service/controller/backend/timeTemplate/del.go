package timeTemplate

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
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

	var timeTemplate mongo.TimeTemplate

	err = timeTemplate.GetById(db, delForm.Id)
	if err != nil {
		controller.JsonByCode(ctx, errs.TimeTemplateNotExist)
		return
	}

	if len(timeTemplate.DeviceCodeCameras) > 0 {
		controller.JsonByCode(ctx, errs.TimeTemplateBind)
		return
	}

	err = timeTemplate.Delete(db)
	if err != nil {
		controller.JsonByCode(ctx, errs.TimeTemplateUpdate)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
