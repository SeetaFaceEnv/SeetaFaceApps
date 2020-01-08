package device

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/httpManager"
	"SeetaDeviceCommunity/service/manager/logManager"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/manager/mqttManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo/bson"
	"net/http"
	"os"
	"strconv"
)

type ReportForm struct {
	DeviceCode          string  `json:"device_code" binding:"required"`
	CaptureImage        string  `json:"capture_image" binding:"omitempty"`
	PersonId            string  `json:"person_id" binding:"omitempty"`
	IdCard              string  `json:"id_card" binding:"omitempty"`
	IcCard              string  `json:"ic_card" binding:"omitempty"`
	QrCode              string  `json:"qr_code" binding:"omitempty"`
	CardInfo            bson.M  `json:"card_info" binding:"omitempty"`
	Matches             []Match `json:"matches" binding:"omitempty"`
	RecognizeType       int64   `json:"recognize_type" binding:"required,min=1,max=3"`
	RecognizeTypeBackup int64   `json:"recognize_type_backup" binding:"omitempty,min=2,max=3"`
	FeatureComparison   int64   `json:"feature_comparison" binding:"omitempty,min=1,max=2"`
	IsExist             int64   `json:"is_exist" binding:"omitempty,min=1,max=2"`
	RecognizeInfo       bson.M  `json:"recognize_info" binding:"omitempty"`
	Timestamp           int64   `json:"timestamp" binding:"required"`
	CameraId            string  `json:"camera_id" binding:"required"`
	Score               float64 `json:"score" binding:"omitempty"`
	IsPass              int64   `json:"is_pass" binding:"required,min=1,max=2"`
}

type Match struct {
	PersonId string  `json:"person_id" binding:"required"`
	ImageId  string  `json:"image_id" binding:"required"`
	Score    float64 `json:"score" binding:"required"`
}

func Report(ctx *gin.Context) {
	var reportForm ReportForm

	err := ctx.ShouldBindJSON(&reportForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	captureUrl := ""
	if reportForm.CaptureImage != "" {
		fileName, err := controller.SaveBase64(reportForm.CaptureImage, configManager.Conf.Path.PassRecord)
		if err != nil {
			controller.JsonByCode(ctx, errs.FileSave)
			return
		}
		defer func() {
			if err != nil {
				_ = os.Remove(configManager.Conf.Path.PassRecord + fileName)
			}
		}()

		captureUrl = controller.GenFileRouter(constants.DirPassRecord, fileName)
	}

	matchUrl := ""
	if len(reportForm.Matches) > 0 {
		match := reportForm.Matches[0]

		matchUrl = controller.GenSeetaDeviceFileRouter(match.ImageId)
	} else if len(reportForm.CardInfo) > 0 {
		if cardImage, ok := reportForm.CardInfo["card_image"]; ok {
			fileName, err := controller.SaveBase64(cardImage.(string), configManager.Conf.Path.PassRecord)
			if err != nil {
				controller.JsonByCode(ctx, errs.FileSave)
				return
			}
			defer func() {
				if err != nil {
					_ = os.Remove(configManager.Conf.Path.PassRecord + fileName)
				}
			}()

			matchUrl = controller.GenFileRouter(constants.DirPassRecord, fileName)
		}
	}

	passRecord := mongo.PassRecord{
		PersonId:            reportForm.PersonId,
		IdCard:              reportForm.IdCard,
		IcCard:              reportForm.IcCard,
		QrCode:              reportForm.QrCode,
		CaptureUrl:          captureUrl,
		MatchUrl:            matchUrl,
		Timestamp:           reportForm.Timestamp,
		IsPass:              reportForm.IsPass,
		DeviceCode:          reportForm.DeviceCode,
		CameraId:            reportForm.CameraId,
		RecognizeType:       reportForm.RecognizeType,
		RecognizeTypeBackup: reportForm.RecognizeTypeBackup,
		FeatureComparison:   reportForm.FeatureComparison,
		IsExist:             reportForm.IsExist,
		RecognizeInfo:       reportForm.RecognizeInfo,
		Score:               reportForm.Score,
	}

	err = passRecord.Add(db)
	if err != nil {
		controller.JsonByCode(ctx, errs.DbOperate)
		return
	}

	if configManager.Conf.Server.ThirdReport != "" {
		var res seetaDeviceManager.OnlyRes
		errCode := httpManager.Request(
			http.MethodPost,
			configManager.Conf.Server.ThirdReport,
			reportForm,
			&res,
		)
		if errCode != errs.Success || res.Res != seetaDeviceManager.Success {
			logManager.Error("report: third report failed,errCode: ", strconv.Itoa(errCode), " ,res: ", strconv.Itoa(res.Res))
		}
	}

	if reportForm.IsPass == constants.RecognizePass {
		client, err := mqttManager.GetConn()
		if err != nil {
			logManager.Error("report: get mqtt connection error: ", err.Error())
			controller.JsonByCode(ctx, errs.Success)
			return
		}

		err = mqttManager.Publish(client, configManager.Conf.Mqtt.RecordTopic, reportForm)
		if err != nil {
			logManager.Error("report: publish record error: ", err.Error())
		}
	}

	controller.JsonByCode(ctx, errs.Success)
}
