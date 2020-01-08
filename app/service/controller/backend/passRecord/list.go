package passRecord

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo/bson"
	"strings"
)

type ListForm struct {
	DeviceCode    string  `json:"device_code" binding:"omitempty"`
	StartTime     int64   `json:"start_time" binding:"omitempty"`
	EndTime       int64   `json:"end_time" binding:"omitempty,gtefield=StartTime"`
	RecognizeType int64   `json:"recognize_type" binding:"omitempty,min=1,max=3"`
	IsPass        int64   `json:"is_pass" binding:"omitempty,min=1,max=2"`
	Score         float64 `json:"score" binding:"omitempty,min=0,max=1"`
	Skip          int     `json:"skip" binding:"omitempty,min=0"`
	Limit         int     `json:"limit" binding:"omitempty,min=1"`
}

func List(ctx *gin.Context) {
	var listForm ListForm

	err := ctx.ShouldBindJSON(&listForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	if listForm.Limit == 0 {
		listForm.Limit = constants.Page
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var passRecord mongo.PassRecord
	selector := bson.M{}

	if listForm.DeviceCode != "" {
		selector["device_code"] = bson.RegEx{
			Pattern: ".*" + listForm.DeviceCode + ".*",
			Options: "i",
		}
	}

	timeSelector := bson.M{}
	if listForm.StartTime != 0 {
		timeSelector["$gte"] = listForm.StartTime * 1e3
	}
	if listForm.EndTime != 0 {
		timeSelector["$lte"] = listForm.EndTime * 1e3
	}
	if len(timeSelector) > 0 {
		selector["timestamp"] = timeSelector
	}

	if listForm.RecognizeType != 0 {
		selector["recognize_type"] = listForm.RecognizeType
	}

	if listForm.IsPass != 0 {
		selector["is_pass"] = listForm.IsPass
	}

	if listForm.Score != 0 {
		selector["score"] = bson.M{"$gte": listForm.Score}
	}

	records, total, err := passRecord.ListByCond(db, selector, listForm.Skip, listForm.Limit)
	if err != nil {
		controller.JsonByCode(ctx, errs.DbQuery)
		return
	}

	for i := range records {
		if records[i].CaptureUrl != "" {
			records[i].CaptureUrl = configManager.Conf.SeetaDevice.CallbackAddr + records[i].CaptureUrl
		}

		if records[i].MatchUrl != "" {
			if strings.HasPrefix(records[i].MatchUrl, constants.BackFile) {
				records[i].MatchUrl = configManager.Conf.SeetaDevice.CallbackAddr + records[i].MatchUrl
				continue
			}
			records[i].MatchUrl = configManager.Conf.SeetaDevice.Addr + records[i].MatchUrl
		}
	}

	controller.JsonList(ctx, total, records)
}
