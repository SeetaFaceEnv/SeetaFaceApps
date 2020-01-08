package mqttManager

import (
	"SeetaDeviceCommunity/service/manager/configManager"
	"SeetaDeviceCommunity/service/manager/logManager"
	"encoding/json"
	"errors"
	"fmt"
	"github.com/eclipse/paho.mqtt.golang"
	"strconv"
	"sync"
	"time"
)

var (
	mqttClient mqtt.Client
	once       sync.Once
)

//GetConn get a mqtt connection
func GetConn() (mqtt.Client, error) {
	var err error
	once.Do(func() {
		mqttClient, err = newClient()
	})
	if err != nil {
		return nil, err
	}
	if !mqttClient.IsConnected() {
		token := mqttClient.Connect()
		if token.Wait(); token.Error() != nil {
			return nil, errors.New("mqtt connect fail")
		}
	}
	return mqttClient, nil

}

//newClient create a new mqtt connection client with opt
func newClient() (mqtt.Client, error) {
	opt := mqtt.NewClientOptions()
	opt.SetUsername(configManager.Conf.Mqtt.Username)
	opt.SetPassword(configManager.Conf.Mqtt.Password)
	opt.SetAutoReconnect(true)
	opt.SetClientID("server_" + strconv.FormatInt(time.Now().UnixNano(), 36))
	opt.AddBroker(fmt.Sprintf(`%s://%s:%s`, "tcp", configManager.Conf.Mqtt.Ip, configManager.Conf.Mqtt.TcpPort))
	opt.SetOnConnectHandler(onConnected)
	opt.SetConnectionLostHandler(onDisconnected)
	client := mqtt.NewClient(opt)
	token := client.Connect()

	if token.Wait(); token.Error() != nil {
		logManager.Error("mqttManager: connected to emq error: ", token.Error().Error())
		return client, token.Error()
	}
	return client, nil
}

//onConnected callback when connect
func onConnected(client mqtt.Client) {
	logManager.Info("mqttManager: connect to emq")
}

//onDisconnected callback when disconnected
func onDisconnected(client mqtt.Client, e error) {
	logManager.Warn("mqttManager: disconnected to emq")
}

//publish publish mqtt message to topic
func Publish(sender mqtt.Client, topic string, msg interface{}) error {

	jsonMsg, err := json.Marshal(msg)
	if err != nil {
		logManager.Error("mqttManager: json marshal msg error: ", err.Error())
		return err
	}

	token := sender.Publish(topic, 1, false, string(jsonMsg))
	if token.WaitTimeout(time.Second*5) && token.Error() != nil {
		logManager.Error("mqttManager: send msg to topic<", topic, "> error: ", token.Error().Error())
		return errors.New("mqtt connect fail")
	}
	return nil
}
