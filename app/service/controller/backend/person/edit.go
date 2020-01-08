package person

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
)

type EditForm struct {
	PersonId        string            `json:"person_id" binding:"required"`
	GroupIds        []string          `json:"group_ids" binding:"omitempty"`
	WechatUserId    string            `json:"wechat_user_id" binding:"omitempty"`
	IcCard          string            `json:"ic_card" binding:"omitempty"`
	IdCard          string            `json:"id_card" binding:"omitempty"`
	DateBegin       int64             `json:"date_begin" binding:"omitempty"`
	DateEnd         int64             `json:"date_end" binding:"omitempty,gtefield=DateBegin"`
	QrCode          string            `json:"qr_code" binding:"omitempty"`
	AuthSwitch      int64             `json:"auth_switch" binding:"omitempty,min=1,max=2"`
	SubtitlePattern []string          `json:"subtitle_pattern" binding:"omitempty"`
	Attributes      map[string]string `json:"attributes" binding:"omitempty"`
}

func Edit(ctx *gin.Context) {
	var editForm EditForm

	err := ctx.ShouldBindJSON(&editForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	personEdit := seetaDeviceManager.PersonEdit{
		PersonId:        editForm.PersonId,
		GroupIds:        editForm.GroupIds,
		WechatUserId:    editForm.WechatUserId,
		IcCard:          editForm.IcCard,
		IdCard:          editForm.IdCard,
		DateBegin:       editForm.DateBegin,
		DateEnd:         editForm.DateEnd,
		QrCode:          editForm.QrCode,
		AuthSwitch:      editForm.AuthSwitch,
		SubtitlePattern: editForm.SubtitlePattern,
		Attributes:      editForm.Attributes,
	}

	var res seetaDeviceManager.OnlyRes
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModulePerson,
		seetaDeviceManager.ActionEdit,
		personEdit,
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
