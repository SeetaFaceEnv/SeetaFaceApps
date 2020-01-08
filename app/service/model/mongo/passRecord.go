package mongo

import (
	"github.com/globalsign/mgo"
	"github.com/globalsign/mgo/bson"
)

type PassRecord struct {
	Id                  bson.ObjectId `bson:"_id,omitempty" json:"id"`
	PersonId            string        `bson:"person_id" json:"person_id"`
	IdCard              string        `bson:"id_card" json:"id_card"`
	IcCard              string        `bson:"ic_card" json:"ic_card"`
	QrCode              string        `bson:"qr_code" json:"qr_code"`
	CaptureUrl          string        `bson:"capture_url" json:"capture_url"`
	MatchUrl            string        `bson:"match_url" json:"match_url"`
	Timestamp           int64         `bson:"timestamp" json:"timestamp"`
	IsPass              int64         `bson:"is_pass" json:"is_pass"`
	DeviceCode          string        `bson:"device_code" json:"device_code"`
	Score               float64       `bson:"score" json:"score"`
	CameraId            string        `bson:"camera_id" json:"camera_id"`
	RecognizeType       int64         `bson:"recognize_type" json:"recognize_type"`
	RecognizeTypeBackup int64         `bson:"recognize_type_backup" json:"recognize_type_backup"`
	FeatureComparison   int64         `bson:"feature_comparison" json:"feature_comparison"`
	IsExist             int64         `bson:"is_exist" json:"is_exist"`
	RecognizeInfo       bson.M        `bson:"recognize_info" json:"recognize_info"`
}

func (PassRecord) col() string {
	return "t_pass_records"
}

func (p *PassRecord) Add(db *mgo.Database) error {
	return db.C(p.col()).Insert(p)
}

func (p PassRecord) ListByCond(db *mgo.Database, selector bson.M, skip, limit int) ([]PassRecord, int, error) {
	results := make([]PassRecord, 0)

	total, err := listByCond(db, p.col(), selector, skip, limit, &results, "-timestamp")
	if err != nil {
		return results, 0, err
	}

	return results, total, nil
}
