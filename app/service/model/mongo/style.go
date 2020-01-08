package mongo

import (
	"SeetaDeviceCommunity/constants/errs"
	"github.com/globalsign/mgo"
	"github.com/globalsign/mgo/bson"
)

const (
	_ = iota
	StyleScreenSaver
	StyleBackground
	StyleLogo
	StyleMarquee
	StyleBox
)

type Style struct {
	Id          bson.ObjectId `bson:"_id,omitempty" json:"id"`
	Name        string        `bson:"name" json:"name"`
	Type        int64         `bson:"type" json:"type"`
	Info        string        `bson:"info" json:"info"`
	DeviceCodes []string      `bson:"device_codes" json:"-"`
	Status      int64         `bson:"status" json:"-"`
}

func (Style) col() string {
	return "t_styles"
}

func (s *Style) Add(db *mgo.Database) error {
	return db.C(s.col()).Insert(s)
}

func (s *Style) GetById(db *mgo.Database, id string) error {
	if !bson.IsObjectIdHex(id) {
		return errs.IdFormat
	}

	return getByCond(db, s.col(), bson.M{"status": StatusNormal, "_id": bson.ObjectIdHex(id)}, s)
}

func (s *Style) GetByName(db *mgo.Database, name string) error {
	return getByCond(db, s.col(), bson.M{"status": StatusNormal, "name": name}, s)
}

func (s *Style) GetManyByCond(db *mgo.Database, selector bson.M) ([]Style, error) {
	results := make([]Style, 0)

	err := db.C(s.col()).Find(selector).All(&results)
	return results, err
}

func (s *Style) UpdateByCond(db *mgo.Database, updater bson.M) error {
	return updateByCond(db, s.col(), bson.M{"_id": s.Id}, bson.M{"$set": updater})
}

func (s *Style) Delete(db *mgo.Database) error {
	return updateByCond(db, s.col(), bson.M{"_id": s.Id}, bson.M{"$set": bson.M{"status": StatusDeleted}})
}

func (s *Style) ListByCond(db *mgo.Database, selector bson.M, skip, limit int) ([]Style, int, error) {
	results := make([]Style, 0)

	total, err := listByCond(db, s.col(), selector, skip, limit, &results, "-_id")
	if err != nil {
		return results, 0, err
	}

	return results, total, nil
}

func (s *Style) PushByCond(db *mgo.Database, selector, updater bson.M) error {
	_, err := db.C(s.col()).UpdateAll(
		selector,
		bson.M{"$push": updater},
	)

	return err
}

func (s *Style) PullByCond(db *mgo.Database, selector, updater bson.M) error {
	_, err := db.C(s.col()).UpdateAll(
		selector,
		bson.M{"$pull": updater},
	)

	return err
}
