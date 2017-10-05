#!/usr/bin/env python
"""
This script is a small webserver that is listening on a port number given as the first argument or port 80 if none is given.
The webserver is waiting for a post from Actility TPW and will extract the data in the JSON posted.
The extracted data is logged to a logfile (lora_appserver.log) and written to a MySQL DB.
"""
from BaseHTTPServer import BaseHTTPRequestHandler, HTTPServer
import SocketServer
import json
import MySQLdb
import time
from datetime import datetime, timedelta
import logging

#write data to table loralog:
def write_DB(device,devaddr,raw_payload,temperature,pressure,light,gps,battery,event):
    conn = MySQLdb.connect(host= "localhost",
                  user="lora",
                  passwd="password",
                  db="loradb")
    x = conn.cursor()
    timestamp=datetime.now() + timedelta(hours=6)
    timestamp=timestamp.strftime('%Y-%m-%d %H:%M:%S')
    try:
        x.execute("""INSERT INTO loralog (timestamp,device,devaddr,raw_payload,temperature,pressure,light,gps,battery,event) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)""",(timestamp,device,devaddr,raw_payload,temperature,pressure,light,gps,battery,event))
        conn.commit()
    except (MySQLdb.Error, MySQLdb.Warning) as e:
        logging.error("MySQL error:",e)
        conn.rollback()
    finally:
        conn.close()

class S(BaseHTTPRequestHandler):
    def _set_headers(self):
        self.send_response(200)
        self.send_header('Content-type', 'text/html')
        self.end_headers()

    def do_POST(self):
        #execute on receiving HTTP POST
        content_length = int(self.headers['Content-Length'])
        post_data = self.rfile.read(content_length)
	json_data=json.loads(post_data)

        #get general lora device info:
        lora_deveui=json_data['DevEUI_uplink']['DevEUI']
        lora_payload=json_data['DevEUI_uplink']['payload_hex']
        lora_fport=json_data['DevEUI_uplink']['FPort']
        lora_devaddr=json_data['DevEUI_uplink']['DevAddr']

        #init variables 
        temperature=None
        pressure=None
        light=None
        battery=None
        gps=None
        event=None
        devtype="unknown"

        #based on known device types, using the first part of the DevEUI (probably there is a better way), extract usefull data
        if lora_deveui[0:8]=="33313832":
            #logic for Semtech LoRamote
	    raw_temp=int(lora_payload[6:10],16)
	    raw_press=int(lora_payload[2:6],16)
            temperature=float(raw_temp)/100-2
            pressure=float(raw_press)/10
            devtype="Semtech Loramote"
            event="interval"
        elif lora_deveui[0:8]=="0004A30B":
            #logic for The Things Node
            devtype="The Things Node"
            fport_values = ['setup', 'interval', 'motion', 'button']
            raw_temp=(int(lora_payload[8:10],16)<<8) + int(lora_payload[10:12],16)
            temperature=float(raw_temp)/100
            battery=(int(lora_payload[0:2],16)<<8) + int(lora_payload[2:4],16)
            light=(int(lora_payload[4:6],16)<<8) + int(lora_payload[6:8],16)
            event=fport_values[int(lora_fport)-1]
        elif lora_deveui[0:8]=="0018B200":
            #logic for Adeunis LoraWAN demonstrator
            devtype="Adeunis LoraWAN demonstrator"
            #find flags in first byte
            #temperature
            tmp_present=int(lora_payload[0:2],16)&128
            #acceleration detected
            acl_detect=int(lora_payload[0:2],16)&64
            #button pressed
            btn_pressed=int(lora_payload[0:2],16)&32
            #GPS info
            gps_present=int(lora_payload[0:2],16)&16
            #F up count
            fup_present=int(lora_payload[0:2],16)&8
            #F down count
            fdw_present=int(lora_payload[0:2],16)&4
            #battery (mV)
            bat_present=int(lora_payload[0:2],16)&2
            #SNR/RSSI
            sig_present=int(lora_payload[0:2],16)&1
            #offset becomes 8 when GPS data is present
            offset=0
            event="interval"
            if bool(tmp_present):
                temperature=int(lora_payload[2:4],16)
            if bool(acl_detect):
                event="motion"
            if bool(btn_pressed):
                event="button"
            if bool(gps_present):
                print "GPS location sent"
                offset=8
            #if bool(fup_present):
                #print "up count:"+str(int(lora_payload[4+offset:6+offset],16))
            #if bool(fdw_present):
                #print "down count:"+str(int(lora_payload[6+offset:8+offset],16))
            if bool(bat_present):
                battery=str(int(lora_payload[8+offset:10+offset],16))+str(int(lora_payload[10+offset:12+offset],16))
            #if bool(sig_present):
                #print "RSSI:"+str(int(lora_payload[12+offset:14+offset],16))
                #print "SNR:"+str(int(lora_payload[14+offset:16+offset],16)) 
        #write to DB:
        logging.info("Received LoRa data: DevEUI: %s - DevAddr: %s - Payload: %s",lora_deveui,lora_devaddr,lora_payload)
        logging.info("-- Payload contents for device: %s: Temperature: %s - Pressure: %s - Light: %s - GPS: %s - Battery: %s - Event: %s",devtype,temperature,pressure,light,gps,battery,event)   
        write_DB(lora_deveui,lora_devaddr,lora_payload,temperature,pressure,light,gps,battery,event)
        self._set_headers()

def run(server_class=HTTPServer, handler_class=S, port=80):
    server_address = ('', port)
    httpd = server_class(server_address, handler_class)
    logging.info('Starting webserver and waiting for post on port: %s',port)
    httpd.serve_forever()

if __name__ == "__main__":
    from sys import argv
    logging.basicConfig(filename="lora_appserver.log",level=logging.DEBUG,format="%(asctime)s - %(levelname)s - %(message)s")
    logging.info("Starting lora_appserver.py")

    if len(argv) == 2:
        run(port=int(argv[1]))
    else:
        run()
