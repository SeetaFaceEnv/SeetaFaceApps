package controller

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/logManager"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"SeetaDeviceCommunity/utils"
	"crypto/md5"
	"encoding/base64"
	"encoding/hex"
	"github.com/gin-gonic/gin"
	"io"
	"net/http"
	"net/url"
	"os"
	"path/filepath"
	"strconv"
	"strings"

	"image"
	_ "image/gif"
	_ "image/jpeg"
	_ "image/png"
)

const (
	Res     = "res"
	Result  = "result"
	Msg     = "msg"
	Records = "records"
	Total   = "total"

	DefaultPage = 10

	StatusCallback = constants.DevStatus + constants.Callback
	ReportRouter   = constants.DevDevice + constants.Report
	AuthRouter     = constants.DevDevice + constants.Auth

	FileGetRouter         = constants.BackFile + constants.Get + "?"
	SeetaDeviceFileRouter = seetaDeviceManager.UriPrefix +
		seetaDeviceManager.ModuleFile +
		seetaDeviceManager.ActionGet + "?"
)

func JsonByCode(ctx *gin.Context, errCode int) {
	ctx.JSON(http.StatusOK, gin.H{
		Res: errCode,
		Msg: errs.GetMsgByCode(errCode),
	})
}

func JsonThirdByCode(ctx *gin.Context, errCode int) {
	ctx.JSON(http.StatusOK, gin.H{
		Result: errCode,
	})
}

func JsonBySeetaDeviceMsg(ctx *gin.Context, errMsg string) {
	ctx.JSON(http.StatusOK, gin.H{
		Res: errs.SeetaDeviceRes,
		Msg: "设备管理平台：" + errMsg,
	})
}

func JsonByDeviceMsg(ctx *gin.Context, errMsg string) {
	ctx.JSON(http.StatusOK, gin.H{
		Res: errs.SeetaDeviceRes,
		Msg: "设备：" + errMsg,
	})
}

func JsonList(ctx *gin.Context, total int, records interface{}) {
	ctx.JSON(http.StatusOK, gin.H{
		Res:     errs.Success,
		Msg:     errs.GetMsgByCode(errs.Success),
		Total:   total,
		Records: records,
	})
}

func SaveFile(ctx *gin.Context, name string) (string, int) {
	fh, err := ctx.FormFile(name)
	if err != nil {
		return "", errs.ImageNotUpload
	}

	fileName := utils.UUID() + filepath.Ext(fh.Filename)

	err = ctx.SaveUploadedFile(fh, configManager.Conf.Path.Data+fileName)
	if err != nil {
		return "", errs.ImageSave
	}

	return fileName, errs.Success
}

func GenFileRouter(fileType int, fileName string) string {
	param := url.Values{
		"token": []string{fileName},
		"type":  []string{strconv.Itoa(fileType)},
	}
	return FileGetRouter + param.Encode()
}

func GenSeetaDeviceFileRouter(fileToken string) string {
	param := url.Values{
		"file_token": []string{fileToken},
	}
	return SeetaDeviceFileRouter + param.Encode()
}

func Md5(filePath string) (string, error) {
	hash := md5.New()

	f, err := os.Open(filePath)
	if err != nil {
		logManager.Error("controller: open file error: ", err.Error())
		return "", err
	}

	_, err = io.Copy(hash, f)
	if err != nil {
		logManager.Error("controller: write file error: ", err.Error())
		return "", err
	}

	return hex.EncodeToString(hash.Sum(nil)), nil
}

func SaveBase64(imageBase64, fileDir string) (string, error) {
	idx := strings.Index(imageBase64, ";base64,")
	if idx < 0 {
		idx = -8
	}

	base64Reader := base64.NewDecoder(base64.StdEncoding, strings.NewReader(imageBase64[idx+8:]))
	_, ext, err := image.DecodeConfig(base64Reader)
	if err != nil {
		logManager.Error("controller: decode base64 config error: ", err.Error())
		return "", errs.Parse
	}

	fileName := utils.UUID() + "." + ext

	bytesData, err := base64.StdEncoding.DecodeString(imageBase64[idx+8:])
	if err != nil {
		logManager.Error("controller: decode base64 error: ", err.Error())
		return "", err
	}

	f, err := os.OpenFile(fileDir+fileName, os.O_CREATE|os.O_RDWR, 0777)
	if err != nil {
		logManager.Error("controller: open file error: ", err.Error())
		return "", err
	}
	defer f.Close()

	_, err = f.Write(bytesData)
	if err != nil {
		logManager.Error("controller: write file error: ", err.Error())
		return "", err
	}

	return fileName, nil
}

func VerifySecretKey(secretKey, timestamp string) bool {
	hash := md5.New()
	_, err := hash.Write([]byte(configManager.Conf.Server.Sign + timestamp))
	if err != nil {
		logManager.Error("controller: write md5 error: ", err.Error())
		return false
	}

	return hex.EncodeToString(hash.Sum(nil)) == secretKey
}
