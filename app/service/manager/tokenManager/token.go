package tokenManager

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/manager/logManager"
	"SeetaDeviceCommunity/service/manager/mongoManager"
	"SeetaDeviceCommunity/service/model/mongo"
	"SeetaDeviceCommunity/utils"
	"fmt"
	"sync"
	"time"
)

const (
	expire = 10
	tick   = time.Minute

	TokenName = "Token"
)

type TokenInfo struct {
	Expire int
	Token  string
}

var tokens sync.Map

func Save(key string, value interface{}) {
	tokens.Store(key, value)
}

func Get(key string) interface{} {
	value, ok := tokens.Load(key)
	if !ok {
		return nil
	}

	return value
}

func Delete(key string) {
	tokens.Delete(key)
}

func Verify(token string) bool {
	adminId, err := GetAdminId(token)
	if err != nil {
		logManager.Debug("tokenManager: get adminId by token error: ", err.Error())
		return false
	}

	tokenInterface := Get(adminId)
	if tokenInterface == nil {
		logManager.Debug("tokenManager: without adminId<", adminId, ">'s token")
		Delete(token)
		return false
	}

	tokenInfo := tokenInterface.(TokenInfo)
	if tokenInfo.Token != token {
		logManager.Debug("tokenManager: token different", token, "should be ", tokenInterface.(TokenInfo).Token)
		Delete(token)
		return false
	}

	tokenInfo.Expire = 0
	Save(adminId, tokenInfo)

	return true
}

func GetAdminId(token string) (string, error) {
	adminId := Get(token)
	if adminId == nil {
		return "", errs.TokenNotExist
	}

	return adminId.(string), nil
}

func GetTokenInfo(adminId string) (TokenInfo, error) {
	tokenInterface := Get(adminId)
	if tokenInterface == nil {
		return TokenInfo{}, errs.TokenNotExist
	}

	return tokenInterface.(TokenInfo), nil
}

func SaveInfo(adminId string) (string, error) {
	token := utils.UUID()

	Save(token, adminId)
	//重复登录，删除原有token
	tokenInterface := Get(adminId)
	if tokenInterface != nil {
		tokenInfo, ok := tokenInterface.(TokenInfo)
		if ok && tokenInfo.Token != "" {
			Delete(tokenInfo.Token)
		}
	}
	Save(adminId, TokenInfo{
		Expire: 0,
		Token:  token,
	})
	return token, nil
}

func DeleteInfo(token string) error {
	adminId, err := GetAdminId(token)
	if err != nil {
		return err
	}

	Delete(adminId)
	Delete(token)
	return nil
}

func tickLoop() {
	fmt.Println("expire cycle: ", tick.Milliseconds()/1e3*expire, "(s)")
	ticker := time.NewTicker(tick)

	for {
		select {
		case <-ticker.C:
			tickFunc()
		}
	}
}

func tickFunc() {
	db := mongoManager.GetDB()
	defer db.Session.Close()

	adminIds, err := mongo.Admin{}.AllIds(db)
	if err != nil {
		logManager.Error("tokenManager: tick get all adminIds error: ", err.Error())
		return
	}
	for _, adminId := range adminIds {
		tokenInterface := Get(adminId)
		if tokenInterface != nil {
			tokenInfo, ok := tokenInterface.(TokenInfo)
			if !ok || tokenInfo.Expire >= expire {
				Delete(adminId)
				continue
			}

			tokenInfo.Expire += 1

			Save(adminId, tokenInfo)
		}
	}
}

func init() {
	utils.PrintLine("RUNNING TOKEN EXPIRE TICK")
	go tickLoop()
}
