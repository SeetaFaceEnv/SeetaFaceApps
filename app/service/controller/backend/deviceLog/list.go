package deviceLog

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
)

type ListForm struct {
	DeviceCode string `json:"device_code" binding:"omitempty"`
	Level      int64  `json:"level" binding:"omitempty"`
	BeginDate  int64  `json:"begin_date" binding:"omitempty"`
	EndDate    int64  `json:"end_date" binding:"omitempty,gtefield=BeginDate"`
	Skip       int    `json:"skip" binding:"omitempty,min=1"`
	Limit      int    `json:"limit" binding:"omitempty,min=1"`
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

	deviceCodes := make([]string, 0)
	if listForm.DeviceCode != "" {
		deviceCodes = []string{listForm.DeviceCode}
	}

	deviceLogList := seetaDeviceManager.DeviceLogList{
		DeviceCodes: deviceCodes,
		Level:       listForm.Level,
		BeginDate:   listForm.BeginDate,
		EndDate:     listForm.EndDate,
		StartIndex:  listForm.Skip,
		Limit:       listForm.Limit,
	}

	var res seetaDeviceManager.ResWithLogsList
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleDeviceLog,
		seetaDeviceManager.ActionList,
		deviceLogList,
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

	controller.JsonList(ctx, res.Total, res.Logs)
}
