package admin

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"errors"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo"
)

type AddForm struct {
	Name     string `json:"name" binding:"required,min=6"`
	Password string `json:"password" binding:"required"`
}

func Add(ctx *gin.Context) {
	var addForm AddForm

	err := ctx.ShouldBindJSON(&addForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var admin mongo.Admin

	err = admin.GetByName(db, addForm.Name)
	if err == nil {
		controller.JsonByCode(ctx, errs.AdminExist)
		return
	} else if !errors.Is(err, mgo.ErrNotFound) {
		controller.JsonByCode(ctx, errs.DbOperate)
		return
	}

	admin = mongo.Admin{
		Name:     addForm.Name,
		Password: addForm.Password,
		Status:   mongo.StatusNormal,
	}

	err = admin.Add(db, false)
	if err != nil {
		controller.JsonByCode(ctx, errs.AdminAdd)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
