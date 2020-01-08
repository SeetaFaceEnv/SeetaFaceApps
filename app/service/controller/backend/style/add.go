package style

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"errors"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo"
	"os"
)

type AddForm struct {
	Name string `form:"name" binding:"required"`
	Type int64  `form:"type" binding:"required,min=1,max=5"`
	Info string `form:"info" binding:"omitempty"`
}

func Add(ctx *gin.Context) {
	var addForm AddForm

	err := ctx.ShouldBind(&addForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var style mongo.Style

	err = style.GetByName(db, addForm.Name)
	if err == nil {
		controller.JsonByCode(ctx, errs.StyleNameExist)
		return
	} else if !errors.Is(err, mgo.ErrNotFound) {
		controller.JsonByCode(ctx, errs.DbQuery)
		return
	}

	style = mongo.Style{
		Name:   addForm.Name,
		Type:   addForm.Type,
		Status: mongo.StatusNormal,
	}

	if addForm.Type == mongo.StyleMarquee {
		style.Info = addForm.Info
	} else {
		fileName, errCode := controller.SaveFile(ctx, "image")
		if errCode != errs.Success {
			controller.JsonByCode(ctx, errCode)
			return
		}
		defer func() {
			if err != nil {
				_ = os.Remove(configManager.Conf.Path.Data + fileName)
			}
		}()

		style.Info = fileName
	}

	err = style.Add(db)
	if err != nil {
		controller.JsonByCode(ctx, errs.StyleAdd)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
