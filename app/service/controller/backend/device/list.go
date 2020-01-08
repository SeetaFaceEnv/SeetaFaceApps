package device

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"SeetaDeviceCommunity/utils"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo/bson"
	"strconv"
)

type ListForm struct {
	DeviceCode string `json:"device_code" binding:"omitempty"`
	GroupId    string `json:"group_id" binding:"omitempty"`
	Ip         string `json:"ip" binding:"omitempty"`
	Alive      int64  `json:"alive" binding:"omitempty,min=1,max=2"`
	Skip       int    `json:"skip" binding:"omitempty"`
	Limit      int    `json:"limit" binding:"omitempty"`
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

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var style mongo.Style

	groupIds := make([]string, 0)
	if listForm.GroupId != "" {
		groupIds = []string{listForm.GroupId}
	}
	deviceCodes := make([]string, 0)
	if listForm.DeviceCode != "" {
		deviceCodes = []string{listForm.DeviceCode}
	}

	deviceList := seetaDeviceManager.DeviceList{
		DeviceCodes: deviceCodes,
		GroupIds:    groupIds,
		Ip:          listForm.Ip,
		Status:      listForm.Alive,
		Skip:        listForm.Skip,
		Limit:       listForm.Limit,
	}
	var res seetaDeviceManager.ResWithDeviceList
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleDevice,
		seetaDeviceManager.ActionList,
		deviceList,
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

	for i, resDevice := range res.Devices {
		deviceStyles, err := style.GetManyByCond(db, bson.M{"device_codes": resDevice.DeviceCode})
		if err != nil {
			controller.JsonByCode(ctx, errs.DbQuery)
			return
		}

		style := seetaDeviceManager.FrontStyle{
			ScreenSavers: make([]string, 0),
		}

		for _, deviceStyle := range deviceStyles {
			switch deviceStyle.Type {
			case mongo.StyleScreenSaver:
				style.ScreenSavers = append(style.ScreenSavers, deviceStyle.Id.Hex())
			case mongo.StyleBackground:
				style.Background = deviceStyle.Id.Hex()
			case mongo.StyleLogo:
				style.Logo = deviceStyle.Id.Hex()
			case mongo.StyleMarquee:
				style.Marquee = deviceStyle.Id.Hex()
			case mongo.StyleBox:
				style.Box = deviceStyle.Id.Hex()
			}

			res.Devices[i].StyleNum += 1
		}

		res.Devices[i].FrontStyles = style

		utils.SetDefault(&res.Devices[i].DeviceParams)

		for j, cameraParam := range res.Devices[i].CameraParams {
			//seetaDeviceManager.SetDefault(&res.Devices[i].CameraParams[j])
			if cameraParam.Name == "" {
				res.Devices[i].CameraParams[j].Name = "默认流" + strconv.Itoa(j+1)
			}
		}
	}

	controller.JsonList(ctx, res.Total, res.Devices)
}
