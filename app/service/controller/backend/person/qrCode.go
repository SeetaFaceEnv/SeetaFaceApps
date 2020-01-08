package person

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"bytes"
	"github.com/gin-gonic/gin"
	"github.com/skip2/go-qrcode"
	"io"
)

type QrCodeForm struct {
	QrCode string `json:"qr_code" binding:"required"`
}

func QrCode(ctx *gin.Context) {
	var qrCodeForm QrCodeForm

	err := ctx.ShouldBindJSON(&qrCodeForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	pngBytes, err := qrcode.Encode(qrCodeForm.QrCode, qrcode.Medium, 256)
	if err != nil {
		controller.JsonByCode(ctx, errs.QrCodeGenerate)
		return
	}

	ctx.Header("Content-Type", "image/png")
	_, err = io.Copy(ctx.Writer, bytes.NewReader(pngBytes))
	if err != nil {
		controller.JsonByCode(ctx, errs.QrCodeSend)
		return
	}
}
