package admin

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/codeManager"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/manager/tokenManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"encoding/base64"
	"fmt"
	"github.com/gin-gonic/gin"
	"net/http"
)

type LoginForm struct {
	Name     string `json:"name" binding:"required"`
	Password string `json:"password" binding:"required"`
	Tag      string `json:"tag" binding:"required"`
	Code     string `json:"code" binding:"omitempty"`
}

func Login(ctx *gin.Context) {
	var loginForm LoginForm

	err := ctx.ShouldBindJSON(&loginForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	if loginForm.Code == "" {
		controller.JsonByCode(ctx, errs.CodeNotExist)
		return
	}

	if !codeManager.Verify(loginForm.Tag, loginForm.Code) {
		controller.JsonByCode(ctx, errs.CodeVerify)
		return
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var admin mongo.Admin

	err = admin.GetByName(db, loginForm.Name)
	if err != nil {
		controller.JsonByCode(ctx, errs.AdminNotExist)
		return
	}

	if !admin.VerifyPwd(loginForm.Password) {
		controller.JsonByCode(ctx, errs.AdminPassword)
		return
	}

	//save token
	token, err := tokenManager.SaveInfo(admin.Id.Hex())
	if err != nil {
		controller.JsonByCode(ctx, errs.TokenGenerate)
		return
	}

	ctx.JSON(http.StatusOK, gin.H{
		controller.Res: errs.Success,
		controller.Msg: errs.GetMsgByCode(errs.Success),
		"token":        token,
		"mqtt_url": fmt.Sprintf(
			"%s://%s:%s/mqtt",
			configManager.Conf.Mqtt.WebScheme,
			configManager.Conf.Mqtt.Ip,
			configManager.Conf.Mqtt.WsPort,
		),
		"mqtt_user":     base64.StdEncoding.EncodeToString([]byte(configManager.Conf.Mqtt.Username)),
		"mqtt_password": base64.StdEncoding.EncodeToString([]byte(configManager.Conf.Mqtt.Password)),
		"status_topic":  configManager.Conf.Mqtt.StatusTopic,
		"record_topic":  configManager.Conf.Mqtt.RecordTopic,
	})
}
