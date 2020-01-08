package group

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
)

type DelForm struct {
	GroupId string `json:"group_id" binding:"required"`
}

func Del(ctx *gin.Context) {
	var delForm DelForm

	err := ctx.ShouldBindJSON(&delForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	groupDel := seetaDeviceManager.GroupDel{GroupId: delForm.GroupId}

	var res seetaDeviceManager.OnlyRes

	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleGroup,
		seetaDeviceManager.ActionDelete,
		groupDel,
		&res,
	)
	if errCode != errs.Success {
		controller.JsonByCode(ctx, errCode)
		return
	}

	if res.Res != seetaDeviceManager.Success {
		controller.JsonBySeetaDeviceMsg(ctx, res.Msg)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
