package admin

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
	Skip  int    `json:"skip" binding:"omitempty"`
	Limit int    `json:"limit" binding:"omitempty"`
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

	var admin mongo.Admin

	selector := bson.M{"status": mongo.StatusNormal}
	if listForm.Name != "" {
		selector["name"] = bson.RegEx{
			Pattern: ".*" + listForm.Name + ".*",
			Options: "i",
		}
	}

	records, total, err := admin.ListByCond(db, selector, listForm.Skip, listForm.Limit)
	if err != nil {
		controller.JsonByCode(ctx, errs.DbQuery)
		return
	}

	controller.JsonList(ctx, total, records)
}
