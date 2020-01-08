package timeTemplate

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"errors"
	"github.com/gin-gonic/gin"
	"github.com/globalsign/mgo"
)

type AddForm struct {
	Name               string            `json:"name" binding:"required"`
	Description        string            `json:"description" binding:"omitempty"`
	ValidDate          []string          `json:"valid_date" binding:"required,min=2"`
	InvalidDate        []mongo.TimeValue `json:"invalid_date" binding:"omitempty"`
	ValidTime          []mongo.TimeValue `json:"valid_time" binding:"required"`
	TimeSlots          []mongo.TimeSlot  `json:"time_slots" binding:"omitempty"`
	SpecialValidDate   []string          `json:"special_valid_date" binding:"omitempty"`
	SpecialInvalidDate []string          `json:"special_invalid_date" binding:"omitempty"`
	ExcludeWeekend     int64             `json:"exclude_weekend" binding:"required,min=1,max=2"`
}

func Add(ctx *gin.Context) {
	var addForm AddForm

	err := ctx.ShouldBindJSON(&addForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	db := mongoManager.GetDB()
	defer db.Session.Close()

	var timeTemplate mongo.TimeTemplate

	err = timeTemplate.GetByName(db, addForm.Name)
	if err == nil {
		controller.JsonByCode(ctx, errs.TimeTemplateNameExist)
		return
	} else if !errors.Is(err, mgo.ErrNotFound) {
		controller.JsonByCode(ctx, errs.DbQuery)
		return
	}

	timeTemplate = mongo.TimeTemplate{
		Name:               addForm.Name,
		Description:        addForm.Description,
		ValidDate:          addForm.ValidDate,
		InvalidDate:        addForm.InvalidDate,
		ValidTime:          addForm.ValidTime,
		TimeSlots:          addForm.TimeSlots,
		SpecialValidDate:   addForm.SpecialValidDate,
		SpecialInvalidDate: addForm.SpecialInvalidDate,
		ExcludeWeekend:     addForm.ExcludeWeekend,
		Status:             mongo.StatusNormal,
	}

	err = timeTemplate.Add(db)
	if err != nil {
		controller.JsonByCode(ctx, errs.TimeTemplateAdd)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
