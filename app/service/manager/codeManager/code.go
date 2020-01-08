package codeManager

import (
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/utils"
	"fmt"
	"strings"
	"sync"
	"time"
)

const (
	//验证码噪点强度
	CaptchaComplexLower = iota
	CaptchaComplexMedium
	CaptchaComplexHigh
	LineNum = 2
)

const (
	width  = 100
	height = 30

	codeLen = 4
	expire  = time.Minute
)

var codeMap sync.Map

type codeInfo struct {
	code   string
	create int64
	used   bool
}

func GetImg(uuid string) ([]byte, error) {
	code := utils.RandomText(codeLen)
	codeMap.Store(uuid, codeInfo{
		code:   code,
		create: time.Now().Unix(),
		used:   false,
	})

	var err error
	defer func() {
		if err != nil {
			codeMap.Delete(uuid)
		}
	}()

	img, err := NewCaptchaImage(width, height, randLightColor())
	if err != nil {
		return nil, err
	}
	img.DrawNoise(CaptchaComplexLower)
	err = img.DrawTextNoise(CaptchaComplexLower)
	if err != nil {
		return nil, err
	}
	//画3条直线
	img.DrawLine(LineNum)
	//写入验证码
	err = img.DrawText(code)
	if err != nil {
		return nil, err
	}
	buf, err := img.ExportPngBin()
	if err != nil {
		return nil, err
	}
	return buf, nil
}

func Verify(uuid, code string) bool {
	valueInterface, ok := codeMap.Load(uuid)
	if !ok {
		return false
	}

	value, ok := valueInterface.(codeInfo)
	if !ok || value.used {
		return false
	}

	if strings.ToLower(value.code) != strings.ToLower(code) {
		return false
	}

	codeMap.Delete(uuid)
	return true
}

func Exist(tag string) bool {
	_, ok := codeMap.Load(tag)
	return ok
}

func tick() {
	fmt.Println("expire cycle: ", expire.Milliseconds()/1e3, "(s)")
	ticker := time.NewTicker(expire)
	for {
		select {
		case <-ticker.C:
			tickFunc()
		}
	}
}

func tickFunc() {
	curTime := time.Now().Unix()
	expireSecond := expire.Milliseconds() / 1e3
	codeMap.Range(func(key, value interface{}) bool {
		codeStruct, ok := value.(codeInfo)
		if !ok {
			codeMap.Delete(key)
			return true
		}

		if codeStruct.used || curTime-codeStruct.create >= expireSecond {
			codeMap.Delete(key)
		}
		return true
	})
}

func init() {
	utils.PrintLine("READ FONTS")
	fmt.Println("load fonts from: ", configManager.Conf.Path.Fonts)
	err := ReadFonts(configManager.Conf.Path.Fonts, ".ttf")
	if err != nil {
		panic(err)
	}

	utils.PrintLine("RUNNING CODE EXPIRE TICK")
	go tick()
}
