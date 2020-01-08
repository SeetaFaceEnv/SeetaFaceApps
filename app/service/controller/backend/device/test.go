package device

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/seetaDeviceManager"
	"github.com/gin-gonic/gin"
	"net/http"
)

type TestForm struct {
	DeviceCode string  `json:"device_code" binding:"required"`
	CameraId   string  `json:"camera_id" binding:"omitempty"`
	Sound      string  `json:"sound" binding:"omitempty"`
	Display    string  `json:"display" binding:"omitempty"`
	Types      []int64 `json:"types" binding:"required,min=1"`
}

func Test(ctx *gin.Context) {
	var testForm TestForm

	err := ctx.ShouldBindJSON(&testForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	isCamera := false
	for _, testType := range testForm.Types {
		switch testType {
		case seetaDeviceManager.TestSound:
			//暂不支持
			//if testForm.Sound == "" {
			//	controller.JsonByCode(ctx, errs.Param)
			//	return
			//}
		case seetaDeviceManager.TestCamera:
			if testForm.CameraId == "" {
				controller.JsonByCode(ctx, errs.Param)
				return
			}

			isCamera = true
		case seetaDeviceManager.TestDisplay:
			if testForm.Display == "" {
				controller.JsonByCode(ctx, errs.Param)
				return
			}
		}
	}

	deviceTest := seetaDeviceManager.DeviceTest{
		DeviceCode: testForm.DeviceCode,
		CameraId:   testForm.CameraId,
		Sound:      testForm.Sound,
		Display:    testForm.Display,
		Types:      testForm.Types,
	}
	var res seetaDeviceManager.ResWitchCaptureImage
	errCode := seetaDeviceManager.Post(
		seetaDeviceManager.ModuleDevice,
		seetaDeviceManager.ActionTest,
		deviceTest,
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

	if !res.DeviceResult {
		controller.JsonByDeviceMsg(ctx, errs.DeviceFailed)
		return
	}

	if !isCamera {
		res.CaptureImage = ""
	}

	ctx.JSON(http.StatusOK, gin.H{
		controller.Res:  errs.Success,
		controller.Msg:  errs.GetMsgByCode(errs.Success),
		"capture_image": res.CaptureImage,
	})
}
