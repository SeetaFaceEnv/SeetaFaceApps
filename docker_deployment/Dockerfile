FROM alpine:3.10

RUN echo http://mirrors.ustc.edu.cn/alpine/v3.10/main > /etc/apk/repositories; \
    echo http://mirrors.ustc.edu.cn/alpine/v3.10/community >> /etc/apk/repositories

RUN apk add tzdata && cp /usr/share/zoneinfo/Asia/Shanghai /etc/localtime \
    && echo "Asia/Shanghai" > /etc/timezone \
    && apk del tzdata

VOLUME ["/code/logs","/code/data","/code/passRecords","/code/gather","/code/resource"]

WORKDIR /code

COPY ../app/resource .
COPY SeetaDeviceCommunity .

RUN chmod +x SeetaDeviceCommunity

CMD ["./SeetaDeviceCommunity"]