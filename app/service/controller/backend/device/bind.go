package device

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/logManager"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"fmt"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo/bson"
)

type BindForm struct {
	DeviceCode   string   `json:"device_code" binding:"required"`
	ScreenSavers []string `json:"screen_savers" binding:"omitempty"`
	Background   string   `json:"background" binding:"omitempty"`
	Logo         string   `json:"logo" binding:"omitempty"`
	Marquee      string   `json:"marquee" binding:"omitempty"`
	Box          string   `json:"box" binding:"omitempty"`
}

func Bind(ctx *gin.Context) {
	var bindForm BindForm

	err := ctx.ShouldBindJSON(&bindForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var style mongo.Style

	dbStyles, err := style.GetManyByCond(db, bson.M{"status": mongo.StatusNormal, "device_codes": bindForm.DeviceCode})
	if err != nil {
		controller.JsonByCode(ctx, errs.DbQuery)
		return
	}

	frontStyle := seetaDeviceManager.FrontStyle{
		ScreenSavers: make([]string, 0),
	}

	for _, dbStyle := range dbStyles {
		switch dbStyle.Type {
		case mongo.StyleScreenSaver:
			frontStyle.ScreenSavers = append(frontStyle.ScreenSavers, dbStyle.Id.Hex())
		case mongo.StyleBackground:
			frontStyle.Background = dbStyle.Id.Hex()
		case mongo.StyleLogo:
			frontStyle.Logo = dbStyle.Id.Hex()
		case mongo.StyleMarquee:
			frontStyle.Marquee = dbStyle.Id.Hex()
		case mongo.StyleBox:
			frontStyle.Box = dbStyle.Id.Hex()
		}
	}

	styleIds := make([]bson.ObjectId, 0)
	for _, screenSaver := range bindForm.ScreenSavers {
		if !bson.IsObjectIdHex(screenSaver) {
			controller.JsonByCode(ctx, errs.Param)
			return
		}

		styleIds = append(styleIds, bson.ObjectIdHex(screenSaver))
	}
	if len(bindForm.ScreenSavers) < 1 && len(frontStyle.ScreenSavers) > 0 {
		for _, screenSaver := range frontStyle.ScreenSavers {
			styleIds = append(styleIds, bson.ObjectIdHex(screenSaver))
		}
	}

	if bindForm.Background != "" {
		if !bson.IsObjectIdHex(bindForm.Background) {
			controller.JsonByCode(ctx, errs.Param)
			return
		}

		styleIds = append(styleIds, bson.ObjectIdHex(bindForm.Background))
	} else if frontStyle.Background != "" {
		styleIds = append(styleIds, bson.ObjectIdHex(frontStyle.Background))
	}

	if bindForm.Logo != "" {
		if !bson.IsObjectIdHex(bindForm.Logo) {
			controller.JsonByCode(ctx, errs.Param)
			return
		}

		styleIds = append(styleIds, bson.ObjectIdHex(bindForm.Logo))
	} else if frontStyle.Logo != "" {
		styleIds = append(styleIds, bson.ObjectIdHex(frontStyle.Logo))
	}

	if bindForm.Marquee != "" {
		if !bson.IsObjectIdHex(bindForm.Marquee) {
			controller.JsonByCode(ctx, errs.Param)
			return
		}

		styleIds = append(styleIds, bson.ObjectIdHex(bindForm.Marquee))
	} else if frontStyle.Marquee != "" {
		styleIds = append(styleIds, bson.ObjectIdHex(frontStyle.Marquee))
	}

	if bindForm.Box != "" {
		if !bson.IsObjectIdHex(bindForm.Box) {
			controller.JsonByCode(ctx, errs.Param)
			return
		}

		styleIds = append(styleIds, bson.ObjectIdHex(bindForm.Box))
	} else if frontStyle.Box != "" {
		styleIds = append(styleIds, bson.ObjectIdHex(frontStyle.Box))
	}

	bindStyles, err := style.GetManyByCond(db, bson.M{
		"_id":    bson.M{"$in": styleIds},
		"status": mongo.StatusNormal},
	)
	if err != nil || len(bindStyles) != len(styleIds) {
		controller.JsonByCode(ctx, errs.StyleNotExist)
		return
	}

	deviceSetStyle := seetaDeviceManager.DeviceSetStyle{
		DeviceCodes: []string{bindForm.DeviceCode},
		Styles:      make([]seetaDeviceManager.Style, 0),
	}

	for _, bindStyle := range bindStyles {
		if bindStyle.Type == mongo.StyleMarquee {
			deviceSetStyle.Styles = append(deviceSetStyle.Styles, seetaDeviceManager.Style{
				Type: bindStyle.Type,
				Info: bindStyle.Info,
			})
			continue
		}

		deviceSetStyle.Styles = append(deviceSetStyle.Styles, seetaDeviceManager.Style{
			Type: bindStyle.Type,
			Info: configManager.Conf.SeetaDevice.CallbackAddr + controller.GenFileRouter(constants.DirStyle, bindStyle.Info),
		})
	}
	var res seetaDeviceManager.ResWithFailedStyles
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleDevice,
		seetaDeviceManager.ActionSetStyle,
		deviceSetStyle,
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

	if len(res.FailedStyles) > 0 {
		logManager.Warn(fmt.Sprintf("bind: set styles failed: %v", res.FailedStyles))
	}

	{
		err = style.PullByCond(db, bson.M{}, bson.M{"device_codes": bindForm.DeviceCode})
		if err != nil {
			controller.JsonByCode(ctx, errs.DbOperate)
			return
		}

		err = style.PushByCond(db, bson.M{"_id": bson.M{"$in": styleIds}}, bson.M{"device_codes": bindForm.DeviceCode})
		if err != nil {
			controller.JsonByCode(ctx, errs.DbOperate)
			return
		}
	}

	controller.JsonByCode(ctx, errs.Success)
}
