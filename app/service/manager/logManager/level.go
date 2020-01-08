package logManager

import (
	"SeetaDeviceCommunity/constants"
	configManager2 "SeetaDeviceCommunity/service/manager/configManager"
)

const (
	_ = iota
	DebugLevel
	InfoLevel
	WarnLevel
	ErrorLevel

	unknownTag = "unknown"
	debugTag   = "DEBUG"
	infoTag    = "INFO"
	warnTag    = "WARN"
	errorTag   = "ERROR"
)

var (
	levelTagMap = map[int]string{
		DebugLevel: debugTag,
		InfoLevel:  infoTag,
		WarnLevel:  warnTag,
		ErrorLevel: errorTag,
	}

	modeLevelMap = map[string]int{
		constants.DebugMode:   DebugLevel,
		constants.TestMode:    DebugLevel,
		constants.ReleaseMode: InfoLevel,
	}
)

func levelToString(level int) string {
	if tag, ok := levelTagMap[level]; ok {
		return tag
	}
	return unknownTag
}

func levelCtrl(level int) bool {
	return level >= modeLevelMap[configManager2.Conf.Server.Mode]
}
