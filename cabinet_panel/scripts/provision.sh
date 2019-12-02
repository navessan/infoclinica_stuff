#!/bin/bash

echo ":APPURL http://kiosk/rasp2" | nc -u 192.168.7.46 41234 & sleep 1 && killall -9 nc

#echo ":APPURL http://kiosk/rasp2" | socat -UDP-DATAGRAM:255.255.255.255:41234,broadcast
