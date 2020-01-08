package seetaDeviceManager

import (
	"SeetaDeviceCommunity/service/model/mongo"
)

const (
	UriPrefix = "/seetadevice/v1/platform"

	ModuleSystem     = "/system"
	ModuleGroup      = "/group"
	ModuleDevice     = "/device"
	ModuleCamera     = "/camera"
	ModulePerson     = "/person"
	ModuleImage      = "/image"
	ModuleDeviceLog  = "/device_log"
	ModuleRequestLog = "/request_log"
	ModuleFile       = "/file"
)

const (
	ActionGet         = "/get"
	ActionSet         = "/set"
	ActionReset       = "/reset"
	ActionCreate      = "/create"
	ActionDelete      = "/delete"
	ActionSetDefault  = "/set_default"
	ActionGetDefault  = "/get_default"
	ActionDiscover    = "/discover"
	ActionAdd         = "/add"
	ActionEdit        = "/edit"
	ActionReconstruct = "/reconstruct"
	ActionList        = "/list"
	ActionTest        = "/test"
	ActionRelayOpen   = "/relay_open"
	ActionPass        = "/pass"
	ActionOpen        = "/open"
	ActionSetStyle    = "/set_style"
	ActionResetStyle  = "/reset_style"
	ActionUpdate      = "/update"
	ActionRelayClose  = "/relay_close"
	ActionAddImage    = "/add_image"
	ActionDeleteImage = "/delete_image"
)

const (
	TestFlash   = 1
	TestSound   = 2
	TestCamera  = 3
	TestDisplay = 4
)

type SystemSet SystemParam

type SystemReset struct {
	ResetTypes []int64 `json:"reset_types"`
}

type GroupId struct {
	GroupId string `json:"group_id"`
}

type GroupAdd GroupId

type GroupDel GroupId

type DeviceParam struct {
	//1:debug 2:info 3:warning 4:error
	LogLevel int64 `bson:"log_level" json:"log_level" default:"value=1"`
	//1:开启 2:关闭 默认2
	VoiceSwitch int64 `bson:"voice_switch" json:"voice_switch"  default:"value=2"`
	//[0,100] 默认60
	Volume int64 `bson:"volume" json:"volume"  default:"value=60"`
	//默认3(s)
	RelayHoldTime int64 `bson:"relay_hold_time" json:"relay_hold_time" default:"value=4"`
	//0:不对调 1:对调
	RelaySignalAlignment int64 `bson:"relay_signal_alignment" json:"relay_signal_alignment" default:"value=2"`
	//1:开启 2:关闭 默认1
	ScreenSaverSwitch int64 `bson:"screensaver_switch" json:"screensaver_switch" default:"value=1"`
	//微信开关
	WechatSwitch int64  `bson:"wechat_switch" json:"wechat_switch" default:"value=2"`
	SeedSecretNo string `bson:"seed_secret_no" json:"seed_secret_no"`
	//默认100
	MinFace int64 `json:"min_face" default:"value=120"`
	//默认20
	MaxAngle int64 `json:"max_angle" default:"value=20"`
	//默认0.7
	Confidence float64 `json:"confidence" default:"value=0.7"`
	//闪光灯
	IsLight int64 `json:"is_light" default:"value=1"`
	//only use for pc gateway
	ReportUrl string `bson:"report_url" json:"report_url"`
	AuthUrl   string `bson:"auth_url" json:"auth_url"`
	RelayHost string `bson:"relay_host" json:"relay_host"`

	//识别参数
	RecognizeType       int64   `json:"recognize_type" default:"value=1"`
	RecognizeTypeBackup int64   `json:"recognize_type_backup"`
	FeatureComparison   int64   `json:"feature_comparison" default:"value=2"`
	IsExist             int64   `json:"is_exist" default:"value=2"`
	ExternalDevices     []int64 `json:"external_devices" default:"value="`
}

type CameraParam struct {
	//安卓默认:default
	Id   string `json:"id" default:"value=default"`
	Name string `json:"name"`
	Url  string `json:"url"`
	//可选webcam,ipc-h264,seeta-hz001,seeta-hi001 默认webcam
	Type       string  `json:"type" default:"value=webcam"`
	MinClarity float64 `json:"min_clarity" default:"value=0.35"`
	//1:开启 2:关闭
	DetectBox int64 `json:"detect_box" default:"value=1"`
	//1:最大人脸 2:多人脸
	RecognitionMode int64 `json:"recognition_mode" default:"value=1"`
	//默认100
	MinFace int64 `json:"min_face" default:"value=120"`
	//默认20
	MaxAngle int64 `json:"max_angle" default:"value=20"`
	//默认1.2
	CropRatio float64 `json:"crop_ratio" default:"value=0.4"`
	ReportUrl string  `json:"report_url"`
	AuthUrl   string  `json:"auth_url"`
	//默认10(s)
	CaptureMaxInterval int64 `json:"capture_max_interval" default:"value=10"`
	//1:开启 2:关闭
	//IsWorking默认1
	IsWorking     int64 `json:"is_working" default:"value=1"`
	NotPassReport int64 `json:"not_pass_report" default:"value=2"`
	//默认1
	TopN int64 `json:"top_n" default:"value=1"`
	//默认0.7
	Threshold11 float64 `json:"threshold_11" default:"value=0.8"`
	Confidence  float64 `json:"confidence" default:"value=0.7"`
	//默认0.6
	Unsure float64 `json:"unsure" default:"value=0.6"`
	//分辨率
	FrameSize          int64            `json:"frame_size" default:"value=1"`
	CtrlSignalOut      int64            `json:"control_signal_out" default:"value=0"`
	TimeTemplateId     string           `json:"timeTemplateId"`
	TimeSlots          []mongo.TimeSlot `json:"time_slots"`
	IntervalFrameCount int64            `json:"interval_frame_count" default:"value=2"`
	UnrecognizedRate   int64            `json:"unrecognized_rate" default:"value=6"`
	RecognizeExpire    int64            `json:"recognize_expire" default:"value=3"`
	BioDetectionOpen   int64            `json:"bio_detection_open" default:"value=1"`
	SamplingFrame      int64            `json:"sampling_frame" default:"value=600"`
	FrameRate          int64            `json:"frame_rate" default:"value=200"`
	ThresholdLiving    float64          `json:"threshold_living" default:"value=0.85"`
	GatherSwitch       int64            `json:"gather_switch"`
	//only pc gateway
	CaptureFrequency int64   `json:"capture_frequency" default:"value=200"`
	FilterBoundary   int64   `json:"filter_boundary" default:"value=50"`
	FilterMaxFace    int64   `json:"filter_max_face" default:"value=500"`
	FilterLive       int64   `json:"filter_live" default:"value=2"`
	FilterType       int64   `json:"filter_type" default:"value=3"`
	FilterTypeWeb    []int64 `json:"filter_type_web" default:"value=1,2"`
	SmartIpcReDetect int64   `json:"smartipc_redetect" default:"value=1"`
	RelayChannels    []int64 `json:"relay_channels"`

	ClaritySwitch int64 `json:"clarity_switch" default:"value=1"`
}

type GroupSetDefault struct {
	GroupIds     []string    `json:"group_ids"`
	DeviceParams interface{} `json:"device_params"`
}

type GroupGetDefault struct {
	GroupIds []string `json:"group_ids"`
	Skip     int      `json:"skip"`
	Limit    int      `json:"limit"`
}

type DeviceAdd struct {
	DeviceCodes []string `json:"device_codes"`
	GroupId     string   `json:"group_id"`
}

type DeviceSet struct {
	DeviceCodes  []string    `json:"device_codes"`
	GroupId      *string     `json:"group_id"`
	DeviceParams DeviceParam `json:"device_params"`
}

type DeviceDel struct {
	DeviceCodes []string `json:"device_codes"`
}

type DeviceReconstruct struct {
	DeviceCodes []string `json:"device_codes"`
}

type DeviceList struct {
	DeviceCodes []string `json:"device_codes"`
	GroupIds    []string `json:"group_ids"`
	Ip          string   `json:"ip"`
	Status      int64    `json:"status"`
	Skip        int      `json:"skip"`
	Limit       int      `json:"limit"`
}

type DeviceTest struct {
	DeviceCode string  `json:"device_code"`
	CameraId   string  `json:"camera_id"`
	Sound      string  `json:"sound"`
	Display    string  `json:"display"`
	Types      []int64 `json:"types"`
}

type DeviceOpen struct {
	DeviceCode string `json:"device_code"`
	CameraId   string `json:"camera_id"`
}

type DeviceRelayOpen DeviceOpen

type DevicePass struct {
	DeviceCode string `json:"device_code"`
	CameraId   string `json:"camera_id"`
	PersonId   string `json:"person_id"`
}

type Style struct {
	Type int64  `json:"type"`
	Info string `json:"info"`
}

type DeviceSetStyle struct {
	DeviceCodes []string `json:"device_codes"`
	Styles      []Style  `json:"styles"`
}

type DeviceResetStyle struct {
	DeviceCodes []string `json:"device_codes"`
	ResetTypes  []int64  `json:"reset_types"`
}

type DeviceUpdate struct {
	GroupIds    []string `json:"group_ids"`
	DeviceCodes []string `json:"device_codes"`
	ApkUrl      string   `json:"apk_url"`
	Etag        string   `json:"etag"`
}

type CameraAdd struct {
	DeviceCode   string        `json:"device_code"`
	CameraParams []CameraParam `json:"camera_params"`
}

type CameraEdit CameraAdd

type CameraDel struct {
	DeviceCode string   `json:"device_code"`
	CameraIds  []string `json:"camera_ids"`
}

type DeviceRelayClose struct {
	DeviceCode string `json:"device_code"`
	CameraId   string `json:"camera_id"`
}

type PersonAdd struct {
	PersonId        string            `json:"person_id"`
	GroupIds        []string          `json:"group_ids"`
	WechatUserId    string            `json:"wechat_user_id"`
	IcCard          string            `json:"ic_card"`
	IdCard          string            `json:"id_card"`
	DateBegin       int64             `json:"date_begin"`
	DateEnd         int64             `json:"date_end"`
	QrCode          string            `json:"qr_code"`
	AuthSwitch      int64             `json:"auth_switch"`
	SubtitlePattern []string          `json:"subtitle_pattern"`
	Attributes      map[string]string `json:"attributes"`
}

type PersonAddImage struct {
	PersonId string `json:"person_id"`
	ImageUrl string `json:"image_url"`
}

type PersonDel struct {
	PersonId string `json:"person_id"`
}

type PersonDelImage struct {
	PersonId string `json:"person_id"`
	ImageId  string `json:"image_id"`
}

type PersonEdit PersonAdd

type PersonList struct {
	PersonIds    []string `json:"person_ids"`
	IcCard       string   `json:"ic_card"`
	IdCard       string   `json:"id_card"`
	WechatUserId string   `json:"wechat_user_id"`
	Skip         int      `json:"skip"`
	Limit        int      `json:"limit"`
}

type PersonAvatarUpdate struct {
	PersonId      string `json:"person_id"`
	PortraitImage string `json:"portrait_image"`
}

type ImageList struct {
	DeviceCodes []string `json:"device_codes"`
	ImageIds    []string `json:"image_ids"`
	BeginDate   int64    `json:"begin_date"`
	EndDate     int64    `json:"end_date"`
	StartIndex  int      `json:"start_index"`
	Limit       int      `json:"limit"`
}

type DeviceLogList struct {
	DeviceCodes []string `json:"device_codes"`
	Level       int64    `json:"level"`
	BeginDate   int64    `json:"begin_date"`
	EndDate     int64    `json:"end_date"`
	StartIndex  int      `json:"start_index"`
	Limit       int      `json:"limit"`
}

type RequestLogList struct {
	Ip         string `json:"ip"`
	Router     string `json:"router"`
	BeginDate  int64  `json:"begin_date"`
	EndDate    int64  `json:"end_date"`
	StartIndex int    `json:"start_index"`
	Limit      int    `json:"limit"`
}

type ThirdPersonEdit struct {
	PersonId      string            `json:"person_id"`
	PortraitImage *string           `json:"portrait_image"`
	Attributes    map[string]string `json:"attributes"`
}

type ThirdPersonAdd struct {
	PersonId      string            `json:"person_id"`
	PortraitImage string            `json:"portrait_image"`
	Attributes    map[string]string `json:"attributes"`
}
