package device

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
	"os"
	"time"
)

type GatherForm struct {
	OriginImage   string `json:"origin_image" binding:"required,base64"`
	InfraredImage string `json:"infrared_image" binding:"required,base64"`
	Type          int64  `json:"type" binding:"required,min=1,max=2"`
}

func Gather(ctx *gin.Context) {
	var gatherForm GatherForm

	err := ctx.ShouldBindJSON(&gatherForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	originFile, err := controller.SaveBase64(gatherForm.OriginImage, configManager.Conf.Path.Gather)
	if err != nil {
		controller.JsonByCode(ctx, errs.FileSave)
		return
	}
	defer func() {
		if err != nil {
			_ = os.Remove(configManager.Conf.Path.Gather + originFile)
		}
	}()

	infraredFile, err := controller.SaveBase64(gatherForm.InfraredImage, configManager.Conf.Path.Gather)
	if err != nil {
		controller.JsonByCode(ctx, errs.FileSave)
		return
	}
	defer func() {
		if err != nil {
			_ = os.Remove(configManager.Conf.Path.Gather + infraredFile)
		}
	}()

	db := mongoManager.GetDB()
	defer db.Session.Close()

	gather := mongo.Gather{
		OriginFile:   originFile,
		InfraredFile: infraredFile,
		Timestamp:    time.Now().Unix(),
		Type:         gatherForm.Type,
	}

	err = gather.Add(db)
	if err != nil {
		controller.JsonByCode(ctx, errs.DbOperate)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
