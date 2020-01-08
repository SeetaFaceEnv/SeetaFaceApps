package logManager

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/service/manager/configManager"
	"fmt"
	"log"
	"os"
	"strings"
	"time"
)

//logToFile write msg to fileName with prefix
func logToFile(level int, fileName string, msg ...string) {
	if !levelCtrl(level) {
		return
	}

	//分离运行模式
	file, err := os.OpenFile(
		fmt.Sprintf(
			"%s%s_%s",
			configManager.Conf.Path.Log,
			time.Now().Format(constants.FormatDay),
			fileName,
		),
		os.O_RDWR|os.O_CREATE|os.O_APPEND,
		0666,
	)

	if err != nil {
		log.Fatalln("open log file error: ", err)
		return
	}
	defer file.Close()

	log.SetOutput(file)
	log.SetPrefix("[" + levelToString(level) + "]")
	log.SetFlags(log.Ldate | log.Ltime)

	log.Println(linkStr(msg...))
}

//linkStr connect msgs together
func linkStr(msgs ...string) string {
	var builder strings.Builder

	for _, msg := range msgs {
		builder.WriteString(msg)
	}

	return builder.String()
}

func Error(msg ...string) {
	logToFile(ErrorLevel, constants.ErrorFile, msg...)
}

func Info(msg ...string) {
	logToFile(InfoLevel, constants.ErrorFile, msg...)
}

func Debug(msg ...string) {
	logToFile(DebugLevel, constants.ErrorFile, msg...)
}

func Warn(msg ...string) {
	logToFile(WarnLevel, constants.ErrorFile, msg...)
}

func Access(msg ...string) {
	logToFile(InfoLevel, constants.AccessFile, msg...)
}

func init() {
	err := os.MkdirAll(configManager.Conf.Path.Log, 0777)
	if err != nil {
		panic(err)
	}

	err = os.MkdirAll(configManager.Conf.Path.PassRecord, 0777)
	if err != nil {
		panic(err)
	}

	err = os.MkdirAll(configManager.Conf.Path.Data, 0777)
	if err != nil {
		panic(err)
	}

	err = os.MkdirAll(configManager.Conf.Path.Gather, 0777)
	if err != nil {
		panic(err)
	}
}
