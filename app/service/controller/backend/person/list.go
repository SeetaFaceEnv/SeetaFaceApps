package person

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
)

type ListForm struct {
	PersonId     string `json:"person_id" binding:"omitempty"`
	IcCard       string `json:"ic_card" binding:"omitempty"`
	IdCard       string `json:"id_card" binding:"omitempty"`
	WechatUserId string `json:"wechat_user_id" binding:"omitempty"`
	Skip         int    `json:"skip" binding:"omitempty,min=1"`
	Limit        int    `json:"limit" binding:"omitempty,min=1"`
}

func List(ctx *gin.Context) {
	var listForm ListForm

	err := ctx.ShouldBindJSON(&listForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	if listForm.Limit == 0 {
		listForm.Limit = controller.DefaultPage
	}

	personIds := make([]string, 0)
	if listForm.PersonId != "" {
		personIds = []string{listForm.PersonId}
	}

	personList := seetaDeviceManager.PersonList{
		PersonIds:    personIds,
		IcCard:       listForm.IcCard,
		IdCard:       listForm.IdCard,
		WechatUserId: listForm.WechatUserId,
		Skip:         listForm.Skip,
		Limit:        listForm.Limit,
	}

	var res seetaDeviceManager.ResWithPersonList
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModulePerson,
		seetaDeviceManager.ActionList,
		personList,
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

	for i := range res.Persons {
		if res.Persons[i].BoxImage != "" {
			res.Persons[i].BoxImage = configManager.Conf.SeetaDevice.Addr + controller.GenSeetaDeviceFileRouter(res.Persons[i].BoxImage)
		}
		if res.Persons[i].PortraitImage != "" {
			res.Persons[i].PortraitImage = configManager.Conf.SeetaDevice.Addr + controller.GenSeetaDeviceFileRouter(res.Persons[i].PortraitImage)
		}

		for j := range res.Persons[i].Images {
			res.Persons[i].Images[j].ImageUrl = configManager.Conf.SeetaDevice.Addr + controller.GenSeetaDeviceFileRouter(res.Persons[i].Images[j].ImageId)
		}
	}

	controller.JsonList(ctx, res.Total, res.Persons)
}
