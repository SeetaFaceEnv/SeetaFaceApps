package style

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo/bson"
)

type ListForm struct {
	Name  string `json:"name" binding:"omitempty"`
	Type  int64  `json:"type" binding:"omitempty,min=1,max=5"`
	Skip  int    `json:"skip" binding:"omitempty,min=1"`
	Limit int    `json:"limit" binding:"omitempty,min=1"`
}

func List(ctx *gin.Context) {
	var listForm ListForm

	err := ctx.ShouldBindJSON(&listForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	if listForm.Limit == 0 {
		listForm.Limit = constants.Page
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var style mongo.Style

	selector := bson.M{"status": mongo.StatusNormal}

	if listForm.Name != "" {
		selector["name"] = bson.RegEx{
			Pattern: ".*" + listForm.Name + ".*",
			Options: "i",
		}
	}

	if listForm.Type != 0 {
		selector["type"] = listForm.Type
	}

	records, total, err := style.ListByCond(db, selector, listForm.Skip, listForm.Limit)
	if err != nil {
		controller.JsonByCode(ctx, errs.DbQuery)
		return
	}

	for i := range records {
		if records[i].Type != mongo.StyleMarquee {
			records[i].Info = configManager.Conf.SeetaDevice.CallbackAddr + controller.GenFileRouter(constants.DirStyle, records[i].Info)
		}
	}

	controller.JsonList(ctx, total, records)
}
