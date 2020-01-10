## 一、项目简介

当前项目为视拓智慧园区社区版的后端部分



## 二、项目框架

当前项目使用的是gin框架



##  三、目录架构
* app ：为后台业务层代码
* docker_deployment：使用docker-compose部署项目的配置文件

> 程序目录：

app

- constants 			    【常量包】
  - errs/errs.go	            【错误码的映射】
  - constants.go 	            【常量定义】
  - path.go                     【路由常量定义】
- resource                  【资源包】
  - config                      【配置文件】
  - fonts                       【字体文件】
  - templates                   【前端代码】
- service                   【服务程序】
  - controller  	            【路由处理函数集合】
    - backend                       【/backend路由处理函数集合】
    - devend                        【/devend路由处理函数集合】
    - common.go                     【处理函数中常用函数定义】
  - manager                 【管理包】
    - codeManager               【验证码管理】
    - configManager             【配置文件管理】
    - cronManager               【定时任务管理】
    - httpManager               【http请求管理】
    - logManager                【日志管理】
    - mongoManager              【mongo连接管理】
    - mqttManager               【mqtt连接管理】
    - seetaDeviceManager        【设备管理平台请求管理】
    - tokenManager              【token管理】
  - middleware              【中间件】
    - access.go                 【请求内容存储】
    - auth.go                   【管理员身份验证】
    - cors.go                   【跨域允许】
    - init.go                   【中间件初始化】
    - optionsHeader.go          【options方法处理】
  - model/mongo             【mongo模型】
    - admin.go                  【管理员模型】
    - common.go                 【模型通用方法】
    - gather.go                 【数据采集模型】
    - passRecord.go             【通行记录模型】
    - style.go                  【样式模型】
    - timeTemplate.go           【时间模板模型】
  - router                  【路由注册】
    - backend.go                【/backend路由注册】
    - devend.go                 【/devend路由注册】
    - router.go                 【路由初始化】
- utils/utils.go            【通用工具】
- go.mod                    【程序依赖】
- SeetaDeviceCommunity.go       【程序入口文件】

## 四、系统设置

打开`resource/config/config.yaml`文件

**1.1、配置文件和日志存储路径**（存储内容包含：人员照片，通行记录照片，APK文件等）

```yaml
path:
  log: logs/ #日志路径
  data: data/ #文件存储路径
  pass_record: passRecords/ #通行记录照片
  gather: gather/ #数据采集照片
  fonts: resource/fonts/ #文字路径
```


**1.2、配置设备管理平台地址**

```yaml
seeta_device:
  addr: http://192.168.0.7:7878 #设备管理平台地址，一般与后台位于统一服务器
  callback_addr: http://192.168.0.18:6969 #设备管理平台回调地址
  timeout: 20 #设备管理平台请求超时
```

**1.3、配置mongodb、mqtt连接信息**

```yaml
# 将以下的mongodb的信息，改成实际的mongodb信息
mongo:
  ip: 127.0.0.1 #部署mongo的服务器地址
  port: 27017
  db: seeta_device_community
  username:
  password:

#将mqtt信息改成实际服务器信息，以及端口,若设置了账号的密码，则填写相应的mqtt账号密码
mqtt:
  ip: 192.168.0.18 #部署emq的服务器
  tcp_port: 1883
  ws_port: 8083
  web_scheme: ws #若后端为https，则改为wss
  username: admin
  password: public
  status_topic: status
  record_topic: record
```

**1.4、配置程序信息**

```yaml
server:
  mode: debug #运行模式，支持debug,release,test
  port: 6969 #运行端口
  third_report: #第三方上报地址
  sign: 4cc89aa9b9684076804b7974cc16caf1 #第三方调用信息签名
  log_cycle: 180 #日志清除间隔
```

## 五、管理员账号

系统初始账户为：

用户名：seeta

密    码：seeta110

创建管理员账号方式，请参考：[部署文档](<https://github.com/SeetaFaceEnv/SeetaFaceAppsDocs/blob/master/doc/deployment.md>)

