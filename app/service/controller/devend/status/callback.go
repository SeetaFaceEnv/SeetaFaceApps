package status

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/logManager"
	"SeetaDeviceCommunity/service/manager/mqttManager"
	"github.com/gin-gonic/gin"
)

type CallbackForm struct {
	DeviceCode    string  `json:"device_code" binding:"required"`
	CameraStatus  *bool   `json:"camera_status" binding:"omitempty"`
	DisplayStatus *bool   `json:"display_status" binding:"omitempty"`
	Alive         *bool   `json:"alive" binding:"omitempty"`
	ApkVersion    *string `json:"apk_version" binding:"omitempty"`
	Timestamp     int64   `json:"timestamp" binding:"required"`
}

func Callback(ctx *gin.Context) {
	var callbackForm CallbackForm

	err := ctx.ShouldBindJSON(&callbackForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	if callbackForm.ApkVersion != nil {
		controller.JsonByCode(ctx, errs.Success)
		return
	}

	client, err := mqttManager.GetConn()
	if err != nil {
		logManager.Error("statusCallback: get mqtt connection error: ", err.Error())
		controller.JsonByCode(ctx, errs.Success)
		return
	}

	err = mqttManager.Publish(client, configManager.Conf.Mqtt.StatusTopic, callbackForm)
	if err != nil {
		logManager.Error("statusCallback: publish status change error: ", err.Error())
	}

	controller.JsonByCode(ctx, errs.Success)
}
