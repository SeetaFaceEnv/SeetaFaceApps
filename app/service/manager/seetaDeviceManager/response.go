package seetaDeviceManager

type OnlyRes struct {
	Res int    `json:"res"`
	Msg string `json:"msg"`
}

type SystemParam struct {
	AutoAdd               int64  `json:"auto_add"`
	DeviceStatusCallback  string `json:"device_status_callback"`
	RegisterImageCallback string `json:"register_image_callback"`
	LogCallback           struct {
		Url   string `json:"url"`
		Level int64  `json:"level"`
	} `json:"log_callback"`
	SeetacloudUrl       string  `json:"seetacloud_url"`
	MinFace             int64   `json:"min_face"`
	MinClarity          float64 `json:"min_clarity"`
	MaxAngle            float64 `json:"max_angle"`
	HandshakeKey        string  `json:"handshake_key"`
	HandshakeResponse   string  `json:"handshake_response"`
	StatusCallbackCycle int64   `json:"status_callback_cycle"`
}

type ResWithSystem struct {
	OnlyRes
	Param SystemParam `json:"param"`
}

type ResWithGroup struct {
	OnlyRes
	Total    int `json:"total"`
	Defaults []struct {
		GroupId      string      `json:"group_id"`
		DeviceCodes  []string    `json:"device_codes"`
		DeviceParams DeviceParam `json:"device_params"`
	} `json:"defaults"`
}

type ResWithDevices struct {
	OnlyRes
	Devices []struct {
		GroupId       string `json:"group_id"`
		DeviceCode    string `json:"device_code"`
		Type          int64  `json:"type"`
		Ip            string `json:"ip"`
		ApkVersion    string `json:"apk_version"`
		DeviceVersion string `json:"device_version"`
	} `json:"devices"`
}

type DeviceResult struct {
	DeviceCode   string        `json:"device_code"`
	Result       bool          `json:"result"`
	Info         string        `json:"info"`
	DeviceParams DeviceParam   `json:"device_params"`
	CameraParams []CameraParam `json:"camera_params"`
}

type ResWithDeviceAllParam struct {
	OnlyRes
	DeviceResults []DeviceResult `json:"device_results"`
}

type ResWithDevice struct {
	OnlyRes
	DeviceResult bool `json:"device_result"`
}

type ResWithDeviceInfos struct {
	OnlyRes
	DeviceResults []struct {
		DeviceCode string `json:"device_code"`
		Result     bool   `json:"result"`
		Info       string `json:"info"`
	} `json:"device_results"`
}

type DeviceListRes struct {
	DeviceCode    string        `json:"device_code"`
	GroupId       string        `json:"group_id"`
	Type          int64         `json:"type"`
	Ip            string        `json:"ip"`
	ApkVersion    string        `json:"apk_version"`
	DeviceVersion string        `json:"device_version"`
	DeviceParams  DeviceParam   `json:"device_params"`
	CameraParams  []CameraParam `json:"camera_params"`
	CameraStatus  bool          `json:"camera_status"`
	DisplayStatus bool          `json:"display_status"`
	Alive         int64         `json:"alive"`
	StyleNum      int64         `json:"style_num"`
	FrontStyles   FrontStyle    `json:"front_styles"`
}

type FrontStyle struct {
	ScreenSavers []string `json:"screen_savers"`
	Background   string   `json:"background"`
	Logo         string   `json:"logo"`
	Marquee      string   `json:"marquee"`
	Box          string   `json:"box"`
}

type ResWithDeviceList struct {
	OnlyRes
	Total   int             `json:"total"`
	Devices []DeviceListRes `json:"devices"`
}

type ResWitchCaptureImage struct {
	ResWithDevice
	CaptureImage string `json:"capture_image"`
}

type ResWithFailedStyles struct {
	OnlyRes
	FailedStyles []Style `json:"failed_styles"`
}

type ResWithPersonList struct {
	OnlyRes
	Total   int `json:"total"`
	Persons []struct {
		PersonId        string      `json:"person_id"`
		GroupIds        []string    `json:"group_ids"`
		WechatUserId    string      `json:"wechat_user_id"`
		IcCard          string      `json:"ic_card"`
		IdCard          string      `json:"id_card"`
		DateBegin       int64       `json:"date_begin"`
		DateEnd         int64       `json:"date_end"`
		QrCode          string      `json:"qr_code"`
		BoxImage        string      `json:"box_image"`
		AuthSwitch      int64       `json:"auth_switch"`
		PortraitImage   string      `json:"portrait_image"`
		VoicePattern    string      `json:"voice_pattern"`
		Attributes      interface{} `json:"attributes"`
		SubtitlePattern []string    `json:"subtitle_pattern"`
		Images          []ImageObj  `json:"images"`
	} `json:"persons"`
}

type ImageObj struct {
	ImageId  string `json:"image_id"`
	ImageUrl string `json:"image_url"`
}

type ResWithImageList struct {
	OnlyRes
	Total      int         `json:"total"`
	SyncImages interface{} `json:"sync_images"`
}

type ResWithLogsList struct {
	OnlyRes
	Total int         `json:"total"`
	Logs  interface{} `json:"logs"`
}

type ResWithImageId struct {
	OnlyRes
	ImageId string `json:"image_id"`
}

type ResWithPersonInfo struct {
	OnlyRes
	Persons []struct {
		PersonId   string            `json:"person_id"`
		Attributes map[string]string `json:"attributes"`
		Images     []struct {
			ImageId string `json:"image_id"`
		}
	} `json:"persons"`
}
