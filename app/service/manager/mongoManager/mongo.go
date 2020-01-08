package mongoManager

import (
	"SeetaDeviceCommunity/service/manager/configManager"
	"github.com/globalsign/mgo"
	"sync"
	"time"
)

var mgoSession *mgo.Session
var once sync.Once

//getSession get mongodb connection session
func getSession() *mgo.Session {
	once.Do(func() {
		var err error
		dialInfo := &mgo.DialInfo{
			Addrs:    []string{configManager.Conf.Mongo.Ip + ":" + configManager.Conf.Mongo.Port},
			Timeout:  time.Second * 4,
			Database: configManager.Conf.Mongo.Db,
			Source:   "admin",
			Username: configManager.Conf.Mongo.Username,
			Password: configManager.Conf.Mongo.Password,
		}

		mgoSession, err = mgo.DialWithInfo(dialInfo)
		if err != nil {
			panic(err)
		}
	})

	if err := mgoSession.Ping(); err != nil {
		dialInfo := &mgo.DialInfo{
			Addrs:    []string{configManager.Conf.Mongo.Ip + ":" + configManager.Conf.Mongo.Port},
			Timeout:  time.Second * 4,
			Database: configManager.Conf.Mongo.Db,
			Source:   "admin",
			Username: configManager.Conf.Mongo.Username,
			Password: configManager.Conf.Mongo.Password,
		}
		mgoSession, err = mgo.DialWithInfo(dialInfo)
		if err != nil {
			panic(err)
		}
	}

	return mgoSession.Copy()
}

//GetDB get database of mongodb connection
func GetDB() *mgo.Database {
	session := getSession()
	return session.DB(configManager.Conf.Mongo.Db)
}
