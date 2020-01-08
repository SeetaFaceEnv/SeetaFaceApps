package constants

const (
	//end
	Backend  = "/backend"
	Devend   = "/devend"
	Thirdend = "/thirdend"
	Frontend = "/frontend"

	//path
	AdminPath        = "/admin"
	SystemPath       = "/system"
	DevicePath       = "/device"
	TimeTemplatePath = "/time_template"
	FilePath         = "/file"
	StylePath        = "/style"
	DeviceLogPath    = "/device_log"
	StatusPath       = "/status"
	PassRecordPath   = "/pass_record"
	RegisterPath     = "/register"
	GroupPath        = "/group"
	PersonPath       = "/person"
	RequestLogPath   = "/request_log"
	ImageLogPath     = "/image_log"

	//action
	Captcha      = "/captcha"
	Get          = "/get"
	Add          = "/add"
	Del          = "/del"
	Delete       = "/delete"
	Edit         = "/edit"
	List         = "/list"
	Send         = "/send"
	Update       = "/update"
	Open         = "/open"
	Close        = "/close"
	Discover     = "/discover"
	Login        = "/login"
	Logout       = "/logout"
	Test         = "/test"
	CameraAdd    = "/camera_add"
	CameraEdit   = "/camera_edit"
	CameraDel    = "/camera_del"
	Bind         = "/bind"
	Unbind       = "/unbind"
	Callback     = "/callback"
	Report       = "/report"
	Auth         = "/auth"
	ImageAdd     = "/image_add"
	ImageDel     = "/image_del"
	Gather       = "/gather"
	Reload       = "/reload"
	Set          = "/set"
	Reset        = "/reset"
	QrCode       = "qr_code"
	AvatarUpdate = "avatar_update"

	BackAdmin        = Backend + AdminPath
	BackDevice       = Backend + DevicePath
	BackTimeTemplate = Backend + TimeTemplatePath
	BackSystem       = Backend + SystemPath
	BackFile         = Backend + FilePath
	BackStyle        = Backend + StylePath
	BackDeviceLog    = Backend + DeviceLogPath
	BackGroup        = Backend + GroupPath
	BackPerson       = Backend + PersonPath
	BackRequestLog   = Backend + RequestLogPath
	BackImageLog     = Backend + ImageLogPath
	BackPassRecord   = Backend + PassRecordPath
	BackRegister     = Backend + RegisterPath

	DevStatus = Devend + StatusPath
	DevDevice = Devend + DevicePath

	ThirdPerson = Thirdend + PersonPath
)
