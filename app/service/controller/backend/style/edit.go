package style

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"errors"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo"
	"github.com/globalsign/mgo/bson"
)

type EditForm struct {
	Id   string `json:"id" binding:"required"`
	Name string `json:"name" binding:"required"`
}

func Edit(ctx *gin.Context) {
	var editForm EditForm

	err := ctx.ShouldBindJSON(&editForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var style mongo.Style

	err = style.GetByName(db, editForm.Name)
	if err == nil {
		if style.Id.Hex() == editForm.Id {
			controller.JsonByCode(ctx, errs.Success)
			return
		}

		controller.JsonByCode(ctx, errs.StyleNameExist)
		return
	} else if !errors.Is(err, mgo.ErrNotFound) {
		controller.JsonByCode(ctx, errs.DbQuery)
		return
	}

	err = style.GetById(db, editForm.Id)
	if err != nil {
		controller.JsonByCode(ctx, errs.StyleNotExist)
		return
	}

	err = style.UpdateByCond(db, bson.M{"name": editForm.Name})
	if err != nil {
		controller.JsonByCode(ctx, errs.StyleUpdate)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
