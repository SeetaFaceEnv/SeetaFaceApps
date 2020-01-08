package file

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/service/manager/configManager"
	"github.com/gin-gonic/gin"
	"mime"
	"net/http"
	"path/filepath"
)

type GetForm struct {
	Token string `form:"token" binding:"required"`
	Type  int64  `form:"type" binding:"required,min=1,max=5"`
}

func Get(ctx *gin.Context) {
	var getForm GetForm

	err := ctx.ShouldBindQuery(&getForm)
	if err != nil {
		ctx.Status(http.StatusNotFound)
		return
	}

	ctx.Header(`Content-Type`, mime.TypeByExtension(filepath.Ext(getForm.Token)))

	dir := ""
	switch getForm.Type {
	case constants.DirGather:
		dir = configManager.Conf.Path.Gather
	case constants.DirPassRecord:
		dir = configManager.Conf.Path.PassRecord
	case constants.DirStyle, constants.DirPerson, constants.DirApk:
		dir = configManager.Conf.Path.Data
	}

	ctx.File(dir + getForm.Token)
}
