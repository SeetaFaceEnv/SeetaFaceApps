package timeTemplate

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo/bson"
)

type ListForm struct {
	Name  string `json:"name" binding:"omitempty"`
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

	selector := bson.M{"status": mongo.StatusNormal}
	if listForm.Name != "" {
		selector["name"] = bson.RegEx{
			Pattern: ".*" + listForm.Name + ".*",
			Options: "i",
		}
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var timeTemplate mongo.TimeTemplate
	records, total, err := timeTemplate.ListByCond(db, selector, listForm.Skip, listForm.Limit)
	if err != nil {
		controller.JsonByCode(ctx, errs.DbQuery)
		return
	}

	controller.JsonList(ctx, total, records)
}
