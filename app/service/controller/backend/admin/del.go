package admin

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/manager/tokenManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo/bson"
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

	adminId, err := tokenManager.GetAdminId(ctx.GetHeader(tokenManager.TokenName))
	if err != nil {
		controller.JsonByCode(ctx, errs.TokenFind)
		return
	}

	if adminId == delForm.Id {
		controller.JsonByCode(ctx, errs.AdminSelfDel)
		return
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var admin mongo.Admin

	err = admin.GetById(db, delForm.Id)
	if err != nil {
		controller.JsonByCode(ctx, errs.AdminNotExist)
		return
	}

	err = admin.UpdateByCond(db, bson.M{"status": mongo.StatusDeleted})
	if err != nil {
		controller.JsonByCode(ctx, errs.AdminUpdate)
		return
	}
	//删除token信息
	tokenInfo, err := tokenManager.GetTokenInfo(delForm.Id)
	if err == nil {
		tokenManager.Delete(tokenInfo.Token)
		tokenManager.Delete(delForm.Id)
	}

	controller.JsonByCode(ctx, errs.Success)
}
