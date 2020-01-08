package middleware

import (
	"fmt"
	"github.com/gin-gonic/gin"
	"net/http"
)

func optionsHeader() gin.HandlerFunc {
	fmt.Println("+ optionsHeader")
	return func(context *gin.Context) {
		if context.Request.Method == http.MethodOptions {
			context.Status(http.StatusOK)
			context.Abort()
			return
		}

		context.Next()
	}
}
