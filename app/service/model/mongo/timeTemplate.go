package mongo

import (
	"SeetaDeviceCommunity/constants/errs"
	"github.com/globalsign/mgo"
	"github.com/globalsign/mgo/bson"
)

type TimeValue struct {
	Value []string `bson:"value" json:"value"`
}

type Slot struct {
	Begin string `bson:"begin" json:"begin"`
	End   string `bson:"end" json:"end"`
}

type TimeSlot struct {
	Date  string `bson:"date" json:"date"`
	Slots []Slot `bson:"slots" json:"slots"`
}

type TimeTemplate struct {
	Id                 bson.ObjectId `bson:"_id,omitempty" json:"id"`
	Name               string        `bson:"name" json:"name"`
	Description        string        `bson:"description" json:"description"`
	ValidDate          []string      `bson:"valid_date" json:"valid_date"`
	InvalidDate        []TimeValue   `bson:"invalid_date" json:"invalid_date"`
	ValidTime          []TimeValue   `bson:"valid_time" json:"valid_time"`
	TimeSlots          []TimeSlot    `bson:"time_slots" json:"time_slots"`
	SpecialValidDate   []string      `bson:"special_valid_date" json:"special_valid_date"`
	SpecialInvalidDate []string      `bson:"special_invalid_date" json:"special_invalid_date"`
	ExcludeWeekend     int64         `bson:"exclude_weekend" json:"exclude_weekend"`
	Status             int64         `bson:"status" json:"-"`
	DeviceCodeCameras  []string      `bson:"device_code_cameras" json:"-"`
}

func (TimeTemplate) col() string {
	return "t_time_templates"
}

func (tt *TimeTemplate) Add(db *mgo.Database) error {
	return db.C(tt.col()).Insert(tt)
}

func (tt *TimeTemplate) GetByName(db *mgo.Database, name string) error {
	return getByCond(db, tt.col(), bson.M{"status": StatusNormal, "name": name}, tt)
}

func (tt *TimeTemplate) GetById(db *mgo.Database, id string) error {
	if !bson.IsObjectIdHex(id) {
		return errs.IdFormat
	}
	return getByCond(db, tt.col(), bson.M{"status": StatusNormal, "_id": bson.ObjectIdHex(id)}, tt)
}

func (tt *TimeTemplate) UpdateByCond(db *mgo.Database, updater bson.M) error {
	return updateByCond(db, tt.col(), bson.M{"_id": tt.Id}, bson.M{"$set": updater})
}

func (tt *TimeTemplate) Delete(db *mgo.Database) error {
	return updateByCond(db, tt.col(), bson.M{"_id": tt.Id}, bson.M{"$set": bson.M{"status": StatusDeleted}})
}

func (tt *TimeTemplate) ListByCond(db *mgo.Database, selector bson.M, skip, limit int) ([]TimeTemplate, int, error) {
	results := make([]TimeTemplate, 0)
	total, err := listByCond(db, tt.col(), selector, skip, limit, &results, "-_id")
	if err != nil {
		return results, 0, err
	}

	return results, total, nil
}

func (tt *TimeTemplate) PushDeviceCodeCamera(db *mgo.Database, deviceCode, cameraId string) error {
	return db.C(tt.col()).Update(
		bson.M{"_id": tt.Id, "device_code_cameras": bson.M{"$ne": deviceCode + "_" + cameraId}},
		bson.M{"$push": bson.M{"device_code_cameras": deviceCode + "_" + cameraId}},
	)
}

func (tt *TimeTemplate) PullDeviceCodeCamera(db *mgo.Database, id, deviceCode, cameraId string) error {
	if !bson.IsObjectIdHex(id) {
		return errs.IdFormat
	}
	return db.C(tt.col()).Update(
		bson.M{"_id": bson.ObjectIdHex(id)},
		bson.M{"$pull": bson.M{"device_code_cameras": deviceCode + "_" + cameraId}},
	)
}

func (tt *TimeTemplate) PullDeviceCode(db *mgo.Database, deviceCode string) error {
	_, err := db.C(tt.col()).UpdateAll(
		bson.M{},
		bson.M{"$pull": bson.M{"device_code_cameras": bson.RegEx{
			Pattern: deviceCode + "_.*",
			Options: "i",
		}}},
	)
	return err
}
