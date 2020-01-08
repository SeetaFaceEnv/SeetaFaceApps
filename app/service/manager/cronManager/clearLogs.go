package cronManager

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/logManager"
	"os"
	"path/filepath"
	"strings"
	"time"
)

func clearLogs() {
	fileNames, err := filepath.Glob(configManager.Conf.Path.Log + "*")
	if err != nil {
		logManager.Error("clearLogs: get files in path error: ", err.Error())
		return
	}

	curTimeUnix := time.Now().Unix()
	expireTime := (time.Hour * 24 * time.Duration(configManager.Conf.Server.LogCycle)).Milliseconds() / 1e3

	for _, fileName := range fileNames {
		dateTimeStr := strings.Split(filepath.Base(fileName), "_")[0]

		dateTime, err := time.ParseInLocation(constants.FormatDay, dateTimeStr, constants.DefaultLoc)
		if err != nil {
			logManager.Error("clearLogs: time parse file<", fileName, "> error: ", err.Error())
			continue
		}

		if curTimeUnix-dateTime.Unix() > expireTime {
			err = os.Remove(fileName)
			if err != nil {
				logManager.Error("clearLogs: remove file<", fileName, "> error: ", err.Error())
			}
		}
	}
}
