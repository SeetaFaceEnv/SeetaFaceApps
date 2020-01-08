package register

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
	"net/http"
)

type GetForm struct {
	UserId    string `form:"user_id" binding:"required"`
	Timestamp string `form:"timestamp" binding:"required"`
	SecretKey string `form:"secret_key" binding:"required"`
}

type MemberRes struct {
	Id         string            `json:"_id"`
	UserId     string            `json:"user_id"`
	Attributes map[string]string `json:"attributes"`
	Images     struct {
		Id       string `json:"id"`
		ImageUrl string `json:"image_url"`
	}
}

func Get(ctx *gin.Context) {
	var getForm GetForm

	err := ctx.ShouldBind(&getForm)
	if err != nil {
		controller.JsonThirdByCode(ctx, errs.Param)
		return
	}

	if !controller.VerifySecretKey(getForm.SecretKey, getForm.Timestamp) {
		controller.JsonThirdByCode(ctx, errs.SecretKeyVerify)
		return
	}

	var res seetaDeviceManager.ResWithPersonInfo
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModulePerson,
		seetaDeviceManager.ActionList,
		seetaDeviceManager.PersonList{
			PersonIds: []string{getForm.UserId},
			Skip:      0,
			Limit:     1,
		},
		&res,
	)
	if errCode != errs.Success {
		controller.JsonThirdByCode(ctx, errCode)
		return
	}

	if res.Res != seetaDeviceManager.Success || len(res.Persons) < 1 {
		controller.JsonThirdByCode(ctx, errs.SeetaDeviceRes)
		return
	}

	resPersonInfo := res.Persons[0]

	member := MemberRes{
		Id:         resPersonInfo.PersonId,
		UserId:     resPersonInfo.PersonId,
		Attributes: resPersonInfo.Attributes,
	}
	if len(resPersonInfo.Images) > 0 {
		member.Images.Id = resPersonInfo.Images[0].ImageId
		member.Images.ImageUrl = configManager.Conf.SeetaDevice.Addr + controller.GenSeetaDeviceFileRouter(resPersonInfo.Images[0].ImageId)
	}

	ctx.JSON(http.StatusOK, gin.H{
		controller.Result: errs.Success,
		"member":          member,
	})
}
