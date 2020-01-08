package middleware

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/service/manager/tokenManager"
	"fmt"
	"github.com/gin-gonic/gin"
	"net/http"
	"strings"
)

var (
	preWhiteList = []string{
		constants.BackAdmin + constants.Captcha,
		constants.BackFile + constants.Get,
		constants.Frontend,
		constants.Devend,
		constants.BackRegister,
	}

	sufWhiteList = []string{
		constants.Login,
	}
)

func auth() gin.HandlerFunc {
	fmt.Println("+ auth")
	return func(context *gin.Context) {
		requestUri := context.Request.RequestURI
		if !inWhiteList(requestUri) {
			uuid := context.GetHeader(tokenManager.TokenName)

			if uuid == "" || !tokenManager.Verify(uuid) {
				context.Status(http.StatusUnauthorized)
				context.Abort()
				return
			}
		}

		context.Next()
	}
}

func inWhiteList(uri string) bool {
	for _, whitePrefix := range preWhiteList {
		if strings.HasPrefix(uri, whitePrefix) {
			return true
		}
	}

	for _, whiteSuffix := range sufWhiteList {
		if strings.HasSuffix(uri, whiteSuffix) {
			return true
		}
	}

	return uri == "/"
}
