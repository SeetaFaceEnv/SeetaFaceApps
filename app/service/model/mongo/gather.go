package mongo

import (
	"github.com/globalsign/mgo"
	"github.com/globalsign/mgo/bson"
)

type Gather struct {
	Id           bson.ObjectId `bson:"_id,omitempty" json:"id"`
	OriginFile   string        `bson:"origin_file" json:"origin_file"`
	InfraredFile string        `bson:"infrared_file" json:"infrared_file"`
	Timestamp    int64         `bson:"timestamp" json:"timestamp"`
	Type         int64         `bson:"type" json:"type"`
}

func (Gather) col() string {
	return "t_gathers"
}

func (g *Gather) Add(db *mgo.Database) error {
	return db.C(g.col()).Insert(g)
}
