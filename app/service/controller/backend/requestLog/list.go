package requestLog

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
)

type ListForm struct {
	Ip        string `json:"ip" binding:"omitempty"`
	Router    string `json:"router" binding:"omitempty"`
	BeginDate int64  `json:"begin_date" binding:"omitempty"`
	EndDate   int64  `json:"end_date"  binding:"omitempty,gtefield=BeginDate"`
	Skip      int    `json:"skip" binding:"omitempty"`
	Limit     int    `json:"limit" binding:"omitempty"`
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

	requestLogList := seetaDeviceManager.RequestLogList{
		Ip:         listForm.Ip,
		Router:     listForm.Router,
		BeginDate:  listForm.BeginDate,
		EndDate:    listForm.EndDate,
		StartIndex: listForm.Skip,
		Limit:      listForm.Limit,
	}

	var res seetaDeviceManager.ResWithLogsList
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleRequestLog,
		seetaDeviceManager.ActionList,
		requestLogList,
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
