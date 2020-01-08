package mongo

import (
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"github.com/globalsign/mgo"
	"github.com/globalsign/mgo/bson"
)

const (
	StatusNormal  = 1
	StatusDeleted = 9

	DeviceCard      = 1
	DeviceAccess    = 2
	DeviceGateway   = 3
	DevicePcGateway = 4
	DeviceRegister  = 5
)

func getByCond(db *mgo.Database, col string, selector bson.M, res interface{}) error {
	return db.C(col).Find(selector).One(res)
}

func updateByCond(db *mgo.Database, col string, selector, updater bson.M) error {
	return db.C(col).Update(selector, updater)
}

func listByCond(db *mgo.Database, col string, selector bson.M, skip, limit int, res interface{}, sorts ...string) (int, error) {
	query := db.C(col).Find(selector)

	num, err := query.Count()
	if err != nil {
		return 0, err
	}

	err = query.Skip(skip).Limit(limit).Sort(sorts...).All(res)
	if err != nil {
		return 0, err
	}

	return num, nil
}

func init() {
	db := mongoManager.GetDB()
	defer db.Session.Close()

	initAdmin(db)
}
