package person

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
)

type AddForm struct {
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

func Add(ctx *gin.Context) {
	var addForm AddForm

	err := ctx.ShouldBindJSON(&addForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	personAdd := seetaDeviceManager.PersonAdd{
		PersonId:        addForm.PersonId,
		GroupIds:        addForm.GroupIds,
		WechatUserId:    addForm.WechatUserId,
		IcCard:          addForm.IcCard,
		IdCard:          addForm.IdCard,
		DateBegin:       addForm.DateBegin,
		DateEnd:         addForm.DateEnd,
		QrCode:          addForm.QrCode,
		AuthSwitch:      addForm.AuthSwitch,
		SubtitlePattern: addForm.SubtitlePattern,
		Attributes:      addForm.Attributes,
	}

	var res seetaDeviceManager.OnlyRes
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModulePerson,
		seetaDeviceManager.ActionAdd,
		personAdd,
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
