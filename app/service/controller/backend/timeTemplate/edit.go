package timeTemplate

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/logManager"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"errors"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo"
	"github.com/globalsign/mgo/bson"
	"strconv"
	"strings"
)

type EditForm struct {
	Id                 string            `json:"id" binding:"required"`
	Name               string            `json:"name" binding:"omitempty"`
	Description        *string           `json:"description" binding:"omitempty"`
	ValidDate          []string          `json:"valid_date" binding:"omitempty,min=2"`
	InvalidDate        []mongo.TimeValue `json:"invalid_date" binding:"omitempty"`
	ValidTime          []mongo.TimeValue `json:"valid_time" binding:"required"`
	TimeSlots          []mongo.TimeSlot  `json:"time_slots" binding:"omitempty"`
	SpecialValidDate   []string          `json:"special_valid_date" binding:"omitempty"`
	SpecialInvalidDate []string          `json:"special_invalid_date" binding:"omitempty"`
	ExcludeWeekend     int64             `json:"exclude_weekend" binding:"omitempty,min=1,max=2"`
}

func Edit(ctx *gin.Context) {
	var editForm EditForm

	err := ctx.ShouldBindJSON(&editForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var timeTemplate mongo.TimeTemplate

	err = timeTemplate.GetById(db, editForm.Id)
	if err != nil {
		controller.JsonByCode(ctx, errs.TimeTemplateNotExist)
		return
	}

	updater := bson.M{}

	if editForm.Name != "" {
		var tmpTemplate mongo.TimeTemplate

		err = tmpTemplate.GetByName(db, editForm.Name)
		if err == nil && tmpTemplate.Id.Hex() != editForm.Id {
			controller.JsonByCode(ctx, errs.TimeTemplateNameExist)
			return
		} else if err != nil && !errors.Is(err, mgo.ErrNotFound) {
			controller.JsonByCode(ctx, errs.DbQuery)
			return
		}

		updater["name"] = editForm.Name
	}
	if editForm.Description != nil {
		updater["description"] = *editForm.Description
	}
	if editForm.ValidDate != nil {
		updater["valid_date"] = editForm.ValidDate
	}
	if editForm.InvalidDate != nil {
		updater["invalid_date"] = editForm.InvalidDate
	}
	if editForm.ValidTime != nil {
		updater["valid_time"] = editForm.ValidTime
	}
	if editForm.TimeSlots != nil {
		updater["time_slots"] = editForm.TimeSlots
	}
	if editForm.SpecialValidDate != nil {
		updater["special_valid_date"] = editForm.SpecialValidDate
	}
	if editForm.SpecialInvalidDate != nil {
		updater["special_invalid_date"] = editForm.SpecialInvalidDate
	}
	if editForm.ExcludeWeekend != 0 {
		updater["exclude_weekend"] = editForm.ExcludeWeekend
	}

	err = timeTemplate.UpdateByCond(db, updater)
	if err != nil {
		controller.JsonByCode(ctx, errs.TimeTemplateUpdate)
		return
	}

	if len(timeTemplate.DeviceCodeCameras) > 0 {
		existMap := make(map[string]bool)
		deviceCodes := make([]string, 0)

		for _, deviceCodeCamera := range timeTemplate.DeviceCodeCameras {
			deviceCode := strings.Split(deviceCodeCamera, "_")[0]

			if _, ok := existMap[deviceCode]; !ok {
				deviceCodes = append(deviceCodes, deviceCode)
				existMap[deviceCode] = true
			}
		}

		deviceList := seetaDeviceManager.DeviceList{
			DeviceCodes: deviceCodes,
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

		if res.Res != seetaDeviceManager.Success || res.Total != len(deviceCodes) {
			controller.JsonBySeetaDeviceMsg(ctx, res.Msg)
			return
		}

		for _, resDevice := range res.Devices {
			tmpResDevice := resDevice
			go func() {
				for i, cameraParam := range tmpResDevice.CameraParams {
					if cameraParam.TimeTemplateId == editForm.Id {
						tmpResDevice.CameraParams[i].TimeSlots = editForm.TimeSlots
					}
				}

				var res seetaDeviceManager.ResWithDevice
				errCode = seetaDeviceManager.Post(
					seetaDeviceManager.ModuleCamera,
					seetaDeviceManager.ActionEdit,
					seetaDeviceManager.CameraEdit{
						DeviceCode:   tmpResDevice.DeviceCode,
						CameraParams: tmpResDevice.CameraParams,
					},
					&res,
				)

				if errCode != errs.Success || res.Res != seetaDeviceManager.Success || !res.DeviceResult {
					logManager.Error(
						"timeTemplateEdit: device<",
						tmpResDevice.DeviceCode,
						"> modify time_slots<",
						editForm.Id,
						"> error, errCode: ",
						strconv.Itoa(errCode),
					)
				}
			}()
		}
	}

	controller.JsonByCode(ctx, errs.Success)
}
