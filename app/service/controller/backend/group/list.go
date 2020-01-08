package group

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/utils"
	"github.com/gin-gonic/gin"
)

type ListForm struct {
	GroupId string `json:"group_id" binding:"omitempty"`
	Skip    int    `json:"skip" binding:"omitempty"`
	Limit   int    `json:"limit" binding:"omitempty"`
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

	groupIds := make([]string, 0)
	if listForm.GroupId != "" {
		groupIds = []string{listForm.GroupId}
	}

	groupGetDefault := seetaDeviceManager.GroupGetDefault{
		GroupIds: groupIds,
		Skip:     listForm.Skip,
		Limit:    listForm.Limit,
	}
	var res seetaDeviceManager.ResWithGroup

	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleGroup,
		seetaDeviceManager.ActionGetDefault,
		groupGetDefault,
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

	groupIdNumMap := make(map[string]int)
	for i := range res.Defaults {
		utils.SetDefault(&res.Defaults[i].DeviceParams)

		groupIdNumMap[res.Defaults[i].GroupId] = i

		if res.Defaults[i].DeviceCodes == nil {
			res.Defaults[i].DeviceCodes = make([]string, 0)
		}
	}

	var deviceRes seetaDeviceManager.ResWithDeviceList
	errCode = seetaDeviceManager.Post(
		seetaDeviceManager.ModuleDevice,
		seetaDeviceManager.ActionList,
		new(struct{}),
		&deviceRes,
	)

	if errCode != errs.Success {
		controller.JsonByCode(ctx, errCode)
		return
	}

	if deviceRes.Res != seetaDeviceManager.Success {
		controller.JsonBySeetaDeviceMsg(ctx, res.Msg)
		return
	}

	for _, device := range deviceRes.Devices {
		if value, ok := groupIdNumMap[device.GroupId]; ok {
			res.Defaults[value].DeviceCodes = append(res.Defaults[value].DeviceCodes, device.DeviceCode)
		}
	}

	controller.JsonList(ctx, res.Total, res.Defaults)
}
