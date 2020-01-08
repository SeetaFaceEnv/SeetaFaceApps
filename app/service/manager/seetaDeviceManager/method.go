package seetaDeviceManager

import (
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/httpManager"
	"net/http"
)

const (
	Success = 0
)

func Post(module, action string, data, res interface{}) int {
	return httpManager.Request(
		http.MethodPost,
		configManager.Conf.SeetaDevice.Addr+UriPrefix+module+action,
		data,
		res,
	)
}

func Get(module, action string, data, res interface{}) int {
	return httpManager.Request(
		http.MethodGet,
		configManager.Conf.SeetaDevice.Addr+UriPrefix+module+action,
		data,
		res,
	)
}
