package middleware

import (
	"SeetaDeviceCommunity/service/manager/logManager"
	"bytes"
	"fmt"
	"github.com/gin-gonic/gin"
	"io/ioutil"
	"net/http"
)

func access() gin.HandlerFunc {
	fmt.Println("+ access")
	return func(ctx *gin.Context) {
		//get request data
		reqData, err := ctx.GetRawData()
		if err != nil {
			ctx.Abort()
			ctx.Status(http.StatusInternalServerError)
			return
		}

		//write data back
		ctx.Request.Body = ioutil.NopCloser(bytes.NewBuffer(reqData))

		uri := ctx.Request.RequestURI

		logManager.Access(ctx.Request.RemoteAddr, " access <", uri, "> succeed, request body :↙ \n", string(reqData))

		ctx.Next()
	}
}
