package person

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
	"net/http"
)

type DelForm struct {
	PersonIds []string `json:"person_ids" binding:"required,min=1"`
}

func Del(ctx *gin.Context) {
	var delForm DelForm

	err := ctx.ShouldBindJSON(&delForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	failed := make([]string, 0)

	for _, personId := range delForm.PersonIds {
		var res seetaDeviceManager.OnlyRes
		errCode := seetaDeviceManager.Post(
			seetaDeviceManager.ModulePerson,
			seetaDeviceManager.ActionDelete,
			seetaDeviceManager.PersonDel{PersonId: personId},
			&res,
		)
		if errCode != errs.Success || res.Res != seetaDeviceManager.Success {
			failed = append(failed, personId)
		}
	}

	ctx.JSON(http.StatusOK, gin.H{
		controller.Res: errs.Success,
		controller.Msg: errs.GetMsgByCode(errs.Success),
		"failed":       failed,
	})
}
