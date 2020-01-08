package httpManager

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/logManager"
	"bytes"
	"crypto/tls"
	"encoding/json"
	"net/http"
	"strconv"
	"time"
)

var client *http.Client

func Request(method, uri string, data, res interface{}) int {
	reqData, err := json.Marshal(data)
	if err != nil {
		logManager.Error("httpManager: marshal reqData error: ", err.Error())
		return errs.JsonParse
	}

	bytesReader := bytes.NewReader(reqData)

	req, err := http.NewRequest(method, uri, bytesReader)
	if err != nil {
		logManager.Error("httpManager: new request error: ", err.Error())
		return errs.HttpNewRequest
	}

	resp, err := client.Do(req)
	if err != nil {
		logManager.Error("httpManager: do request error: ", err.Error())

		logManager.Info("httpManager: retry request")
		time.Sleep(time.Second)
		_, err := bytesReader.Seek(0, 0)
		if err != nil {
			logManager.Error("httpManager: bytes reader seek error: ", err.Error())
			return errs.Read
		}

		resp, err = client.Do(req)
		if err != nil {
			logManager.Error("httpManager: do request retry error: ", err.Error())
			return errs.HttpRequest
		}
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		logManager.Error("httpManager: response status not ok,code: ", strconv.Itoa(resp.StatusCode))
		return errs.HttpResponse
	}

	jsonDecoder := json.NewDecoder(resp.Body)
	err = jsonDecoder.Decode(res)
	if err != nil {
		logManager.Error("httpManager: decode response data error: ", err.Error())
		return errs.HttpParse
	}

	return errs.Success
}

func init() {
	tr := &http.Transport{
		TLSClientConfig: &tls.Config{InsecureSkipVerify: true},
	}
	client = &http.Client{
		Transport: tr,
		Timeout:   time.Second * time.Duration(configManager.Conf.SeetaDevice.Timeout),
	}
}
