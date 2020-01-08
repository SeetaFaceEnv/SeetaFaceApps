package configManager

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/utils"
	"fmt"
	"gopkg.in/yaml.v2"
	"io/ioutil"
	"os"
)

type Config struct {
	Server      Server      `yaml:"server"`
	Mongo       Mongo       `yaml:"mongo"`
	Mqtt        Mqtt        `yaml:"mqtt"`
	Path        Path        `yaml:"path"`
	SeetaDevice SeetaDevice `yaml:"seeta_device"`
}

type Server struct {
	Port         string `yaml:"port"`
	Mode         string `yaml:"mode"`
	GatherSwitch string `yaml:"gather_switch"`
	ThirdReport  string `yaml:"third_report"`
	Sign         string `yaml:"sign"`
	LogCycle     int64  `yaml:"log_cycle"`
}

type Mongo struct {
	Ip       string `yaml:"ip"`
	Port     string `yaml:"port"`
	Db       string `yaml:"db"`
	Username string `yaml:"username"`
	Password string `yaml:"password"`
}

type Mqtt struct {
	Ip          string `yaml:"ip"`
	TcpPort     string `yaml:"tcp_port"`
	WsPort      string `yaml:"ws_port"`
	WebScheme   string `yaml:"web_scheme"`
	Username    string `yaml:"username"`
	Password    string `yaml:"password"`
	StatusTopic string `yaml:"status_topic"`
	RecordTopic string `yaml:"record_topic"`
	ExpireTopic string `yaml:"expire_topic"`
}

type Path struct {
	Log        string `yaml:"log"`
	Data       string `yaml:"data"`
	PassRecord string `yaml:"pass_record"`
	Gather     string `yaml:"gather"`
	Fonts      string `yaml:"fonts"`
}

type SeetaDevice struct {
	Addr         string `yaml:"addr"`
	CallbackAddr string `yaml:"callback_addr"`
	Timeout      int64  `yaml:"timeout"`
}

var Conf Config

//readYaml read yaml file to obj
func readYaml(filePath string, obj interface{}) {
	data, err := ioutil.ReadFile(filePath)
	if err != nil {
		panic(err)
	}

	err = yaml.Unmarshal(data, obj)
	if err != nil {
		panic(err)
	}
}

func getEnv(env string, value *string) {
	envValue := os.Getenv(env)
	if envValue != "" {
		*value = envValue
		fmt.Println("use env<", env, ">: ", *value)
	}
}

func init() {
	utils.PrintLine("READ CONFIG")
	fmt.Println("read from: ", constants.ConfigFile)
	readYaml(constants.ConfigFile, &Conf)

	//check env
	utils.PrintLine("CHECK ENV CONFIG")
	//mongo
	getEnv("MONGO_IP", &Conf.Mongo.Ip)
	getEnv("MONGO_PORT", &Conf.Mongo.Port)
	getEnv("MONGO_USER", &Conf.Mongo.Username)
	getEnv("MONGO_PASSWORD", &Conf.Mongo.Password)
	//mqtt
	getEnv("MQTT_IP", &Conf.Mqtt.Ip)
	getEnv("MQTT_TCP", &Conf.Mqtt.TcpPort)
	getEnv("MQTT_WS", &Conf.Mqtt.WsPort)
	getEnv("MQTT_USER", &Conf.Mqtt.Username)
	getEnv("MQTT_PASSWORD", &Conf.Mqtt.Password)
	getEnv("MQTT_WEB_SCHEME", &Conf.Mqtt.WebScheme)
	//server
	getEnv("SERVER_MODE", &Conf.Server.Mode)
	getEnv("SERVER_PORT", &Conf.Server.Port)
}
