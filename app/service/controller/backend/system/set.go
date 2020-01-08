package system

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
)

type SetForm struct {
	AutoAdd               int64  `json:"auto_add" binding:"omitempty"`
	DeviceStatusCallback  string `json:"device_status_callback" binding:"omitempty"`
	RegisterImageCallback string `json:"register_image_callback" binding:"omitempty"`
	LogCallback           struct {
		Url   string `json:"url" binding:"omitempty"`
		Level int64  `json:"level" binding:"omitempty,min=1,max=4"`
	} `json:"log_callback"`
	SeetacloudUrl       string  `json:"seetacloud_url" binding:"omitempty"`
	MinFace             int64   `json:"min_face" binding:"omitempty"`
	MinClarity          float64 `json:"min_clarity" binding:"omitempty"`
	MaxAngle            float64 `json:"max_angle" binding:"omitempty"`
	HandshakeKey        string  `json:"handshake_key" binding:"omitempty"`
	HandshakeResponse   string  `json:"handshake_response" binding:"omitempty"`
	StatusCallbackCycle int64   `json:"status_callback_cycle" binding:"omitempty"`
}

func Set(ctx *gin.Context) {
	var setForm SetForm

	err := ctx.ShouldBindJSON(&setForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	if setForm.LogCallback.Level == 0 {
		setForm.LogCallback.Level = 1
	}

	systemSet := seetaDeviceManager.SystemSet{
		AutoAdd:               setForm.AutoAdd,
		DeviceStatusCallback:  setForm.DeviceStatusCallback,
		RegisterImageCallback: setForm.RegisterImageCallback,
		SeetacloudUrl:         setForm.SeetacloudUrl,
		MinFace:               setForm.MinFace,
		MinClarity:            setForm.MinClarity,
		MaxAngle:              setForm.MaxAngle,
		HandshakeKey:          setForm.HandshakeKey,
		HandshakeResponse:     setForm.HandshakeResponse,
		StatusCallbackCycle:   setForm.StatusCallbackCycle,
	}

	systemSet.LogCallback.Url = setForm.LogCallback.Url
	systemSet.LogCallback.Level = setForm.LogCallback.Level

	var res seetaDeviceManager.OnlyRes
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleSystem,
		seetaDeviceManager.ActionSet,
		systemSet,
		&res,
	)
	if errCode != errs.Success {
		controller.JsonByCode(ctx, errCode)
		return
	}

	if res.Res != seetaDeviceManager.Success {
		controller.JsonBySeetaDeviceMsg(ctx, res.Msg)
		return
	}

	controller.JsonByCode(ctx, errs.Success)
}
