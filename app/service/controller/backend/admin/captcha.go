package admin

import (
	"SeetaDeviceCommunity/constants/errs"
	"SeetaDeviceCommunity/service/controller"
	"SeetaDeviceCommunity/service/manager/codeManager"
	"github.com/gin-gonic/gin"
)

type CaptchaForm struct {
	Tag string `form:"tag" binding:"required"`
}

func Captcha(ctx *gin.Context) {
	var captchaForm CaptchaForm

	err := ctx.ShouldBindQuery(&captchaForm)
	if err != nil {
		controller.JsonByCode(ctx, errs.Param)
		return
	}

	if codeManager.Exist(captchaForm.Tag) {
		controller.JsonByCode(ctx, errs.CodeExist)
		return
	}

	imageBytes, err := codeManager.GetImg(captchaForm.Tag)
	if err != nil {
		controller.JsonByCode(ctx, errs.CodeGenerate)
		return
	}

	_, err = ctx.Writer.Write(imageBytes)
	if err != nil {
		controller.JsonByCode(ctx, errs.CodeSend)
		return
	}

	ctx.Header("Content-Type", "image/png")
}
