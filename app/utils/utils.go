package utils

import (
	"fmt"
	"github.com/satori/go.uuid"
	"math/rand"
	"reflect"
	"strconv"
	"strings"
	"time"
)

func UUID() string {
	u1 := uuid.NewV4()
	str := u1.String()
	return strings.Replace(str, "-", "", -1)
}

//PrintLine print ========<msg>========
func PrintLine(msg string) {
	fmt.Println("========", msg, "========")
}

var (
	rander = rand.New(rand.NewSource(time.Now().UnixNano()))
)

func RandomText(l int) string {
	text := []byte("ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789")
	buf := make([]byte, 0, l)
	for len(buf) < cap(buf) {
		i := rander.Intn(56)
		buf = append(buf, text[i])
	}
	return string(buf)
}

func SetDefault(target interface{}) {
	if reflect.TypeOf(target).Kind() != reflect.Ptr {
		panic("utils: method: target is not pointer")
	}

	targetType := reflect.TypeOf(target).Elem()
	targetValue := reflect.ValueOf(target).Elem()

	for i := 0; i < targetType.NumField(); i++ {
		fieldType := targetType.Field(i)
		fieldValue := targetValue.Field(i)

		tagValue := fieldType.Tag.Get("default")
		if tagValue == "" {
			continue
		}

		splitArr := strings.Split(tagValue, "=")
		if len(splitArr) < 2 {
			panic(fmt.Sprintf("utils: method: get format error, value: %s ", tagValue))
		}

		tagType := splitArr[0]
		setValue := splitArr[1]

		switch tagType {
		case "value":
			switch fieldType.Type.Kind() {
			case reflect.Int64:
				if fieldValue.Int() == 0 {
					intValue, err := strconv.ParseInt(setValue, 10, 64)
					if err != nil {
						panic(fmt.Sprintf("utils: method: parse int error, value: %s", setValue))
					}

					fieldValue.SetInt(intValue)
				}
			case reflect.String:
				if fieldValue.String() == "" {
					fieldValue.SetString(setValue)
				}
			case reflect.Slice:
				if fieldValue.IsNil() {
					reflectSlice := reflect.MakeSlice(fieldType.Type, 0, 0)
					if setValue != "" {
						strSlice := strings.Split(setValue, ",")

						for _, str := range strSlice {
							intValue, err := strconv.ParseInt(str, 10, 64)
							if err != nil {
								panic(fmt.Sprintf("utils: method: parse int, value: %s,error: %v", str, err))
							}

							reflectSlice = reflect.Append(reflectSlice, reflect.ValueOf(intValue))
						}
					}

					fieldValue.Set(reflectSlice)
				}
			}
		}
	}
}
