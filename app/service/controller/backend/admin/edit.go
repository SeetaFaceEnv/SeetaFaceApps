package admin

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo/bson"
)

type EditForm struct {
	Id          string `json:"id" binding:"required"`
	Password    string `json:"password" binding:"required"`
	NewPassword string `json:"new_password" binding:"required"`
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

	var admin mongo.Admin

	err = admin.GetById(db, editForm.Id)
	if err != nil {
		controller.JsonByCode(ctx, errs.AdminNotExist)
		return
	}

	if !admin.VerifyPwd(editForm.Password) {
		controller.JsonByCode(ctx, errs.AdminPassword)
		return
	}

	updater := bson.M{
		"password": editForm.NewPassword,
	}
	err = admin.UpdateByCond(db, updater)
	if err != nil {
		controller.JsonByCode(ctx, errs.AdminUpdate)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
