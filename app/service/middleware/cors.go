package middleware

import (
	"fmt"
	"github.com/gin-gonic/gin"
)

func cors() gin.HandlerFunc {
	fmt.Println("+ cors")
	return func(context *gin.Context) {
		context.Header("Access-Control-Allow-Origin", "*")
		context.Header("Access-Control-Allow-Methods", "GET,POST,OPTIONS")
		context.Header("Access-Control-Allow-Headers", "Token,Content-Type")
		context.Header("Access-Control-Max-Age", "86400")

		context.Next()
	}
}
