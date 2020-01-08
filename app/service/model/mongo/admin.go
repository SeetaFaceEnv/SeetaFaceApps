package mongo

import (
	"SeetaDeviceCommunity/constants/errs"
	"crypto/md5"
	"encoding/hex"
	"github.com/globalsign/mgo"
	"github.com/globalsign/mgo/bson"
	"golang.org/x/crypto/bcrypt"
)

const (
	defaultAdmin = "seeta"
	defaultPwd   = "seeta110"
)

type Admin struct {
	Id       bson.ObjectId `bson:"_id,omitempty" json:"id"`
	Name     string        `bson:"name" json:"name"`
	Password string        `bson:"password" json:"-"`
	Status   int64         `bson:"status" json:"-"`
}

func (Admin) col() string {
	return "t_admins"
}

func (a Admin) AllIds(db *mgo.Database) ([]string, error) {
	var adminIds []string
	var admins []Admin
	err := db.C(a.col()).Find(bson.M{"status": StatusNormal}).All(&admins)
	if err != nil {
		return nil, err
	}

	for _, dbAdmin := range admins {
		adminIds = append(adminIds, dbAdmin.Id.Hex())
	}
	return adminIds, err
}

func (a *Admin) Add(db *mgo.Database, hash bool) (err error) {
	if hash {
		a.Password, err = a.GenPwd(a.Password)
		if err != nil {
			return err
		}
	}
	bytePwd, err := bcrypt.GenerateFromPassword([]byte(a.Password), bcrypt.DefaultCost)
	if err != nil {
		return err
	}
	a.Password = string(bytePwd)

	return db.C(a.col()).Insert(a)
}

func (a *Admin) GetByName(db *mgo.Database, name string) error {
	return getByCond(db, a.col(), bson.M{"name": name, "status": StatusNormal}, a)
}

func (a *Admin) GetById(db *mgo.Database, id string) error {
	if !bson.IsObjectIdHex(id) {
		return errs.IdFormat
	}
	return getByCond(db, a.col(), bson.M{"_id": bson.ObjectIdHex(id), "status": StatusNormal}, a)
}

func (a Admin) CountByCond(db *mgo.Database, selector bson.M) (int, error) {
	return db.C(a.col()).Find(selector).Count()
}

func (a *Admin) UpdateByCond(db *mgo.Database, updater bson.M) (err error) {
	if value, ok := updater["password"]; ok {
		bytePwd, err := bcrypt.GenerateFromPassword([]byte(value.(string)), bcrypt.DefaultCost)
		if err != nil {
			return err
		}
		updater["password"] = string(bytePwd)
	}
	return updateByCond(db, a.col(), bson.M{"_id": a.Id}, bson.M{"$set": updater})
}

func (a *Admin) VerifyPwd(pwd string) bool {
	err := bcrypt.CompareHashAndPassword([]byte(a.Password), []byte(pwd))
	if err != nil {
		return false
	}
	return true
}

func (a *Admin) GenPwd(pwd string) (string, error) {
	hash := md5.New()
	hash.Write([]byte(pwd))
	md5PwdBytes := hash.Sum(nil)
	md5Pwd := hex.EncodeToString(md5PwdBytes)
	return md5Pwd, nil
}

func (a Admin) ListByCond(db *mgo.Database, selector bson.M, skip, limit int) ([]Admin, int, error) {
	results := make([]Admin, 0)

	num, err := listByCond(db, a.col(), selector, skip, limit, &results, "-_id")
	if err != nil {
		return results, 0, err
	}

	return results, num, nil
}

func initAdmin(db *mgo.Database) {
	var admin Admin

	num, err := admin.CountByCond(db, bson.M{"status": StatusNormal})
	if err != nil {
		panic(err)
	}

	if num == 0 {
		admin = Admin{
			Name:     defaultAdmin,
			Password: defaultPwd,
			Status:   StatusNormal,
		}

		err = admin.Add(db, true)
		if err != nil {
			panic(err)
		}
	}
}
