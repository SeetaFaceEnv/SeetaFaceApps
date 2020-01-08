package constants

import "time"

const Version = `SeetaDeviceCommunity v1.1.beta
CHANGELOG: modify recognize_type && mv recognize_type,is_light to device_param`

const (
	ConfigFile = "resource/config/config.yaml"

	//log file name
	ErrorFile  = "error.log"
	AccessFile = "access.log"

	//program support mode
	DebugMode   = "debug"
	TestMode    = "test"
	ReleaseMode = "release"

	//time format
	FormatDay      = "2006-01-02"
	FormatSecond   = "2006-01-02 15:04:05"
	FormatMinute   = "2006-01-02 15:04"
	FormatOnlyHour = "15:04"

	//page
	Page = 10

	//dir type
	DirGather     = 1
	DirPassRecord = 2
	DirStyle      = 3
	DirPerson     = 4
	DirApk        = 5

	//log level
	LevelDebug = 1
	LevelInfo  = 2
	LevelWarn  = 3
	LevelError = 4

	//开关
	SwitchOpen  = 1
	SwitchClose = 2

	RecognizePass = 1
)

var (
	DefaultLoc = time.FixedZone("CST", 8*3600)

	MimeMap = map[string]string{
		"application/vnd.android.package-archive": ".apk",
	}

	GatherMap = map[string]int64{
		"all":     1,
		"close":   2,
		"pass":    3,
		"notPass": 4,
	}
)
