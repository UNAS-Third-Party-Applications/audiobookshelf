#!/bin/bash

# INSTALL_PATH='/opt/audiobookshelf'
VERSION='latest'

if [[ $1 == */ ]]; then
  INSTALL_PATH=${1%?}
else
  INSTALL_PATH=$1
fi

# 端口
PORT=$2

# 配置目录，元数据目录和配置目录使用一个目录
if [[ $3 == */ ]]; then
  CONFIG_PATH=${3%?}
else
  CONFIG_PATH=$3
fi

RED_COLOR='\e[1;31m'
GREEN_COLOR='\e[1;32m'
YELLOW_COLOR='\e[1;33m'
BLUE_COLOR='\e[1;34m'
PINK_COLOR='\e[1;35m'
SHAN='\e[1;33;5m'
RES='\e[0m'
clear

if [ "$(id -u)" != "0" ]; then
  echo -e "\r\n${RED_COLOR}出错了，请使用 root 权限重试！${RES}\r\n" 1>&2
  exit 1
fi

if [ ! -f "$INSTALL_PATH/audiobookshelf" ]; then
  echo -e "\r\n${RED_COLOR}出错了${RES}，当前系统未安装 Audiobookshelf\r\n"
  exit 1
fi

# 创建 systemd
cat >/etc/systemd/system/audiobookshelf.service <<EOF
[Unit]
Description=UNAS Audiobookshelf service
Wants=network.target
After=network.target network.service

[Service]
Type=simple
WorkingDirectory=$INSTALL_PATH
ExecStart=$INSTALL_PATH/audiobookshelf -p $PORT -c $CONFIG_PATH -m $CONFIG_PATH
ExecReload=/bin/kill -HUP $MAINPID
KillMode=process

[Install]
WantedBy=multi-user.target
EOF

# 添加开机启动
systemctl daemon-reload
systemctl enable audiobookshelf >/dev/null 2>&1
# 启动服务
systemctl start audiobookshelf >/dev/null 2>&1

