# This file is part of Jeedom.
#
# Jeedom is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# Jeedom is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

import globals
import serial
import struct
import logging
import string
import sys
import os
import time
import argparse
import datetime
import binascii
import re
import signal
import traceback
import xml.dom.minidom as minidom
from optparse import OptionParser
from os.path import join
import json

# Ajouts
import smbus

# Card Registers
W_OUTPUT=66 #0x42
R_OUTPUT=65 #0x41
R_INPUT=80 #0x50
W_HBEAT=96 #0x60
R_HBEAT=97 #0x61
R_VERSION=144 #0x90

# Equipements
Eqts=[]

try:
	from jeedom.jeedom import *
except ImportError:
	print "Error: importing module jeedom.jeedom"
	sys.exit(1)

# ----------------------------------------------------------------------------
class CARDS(object):
	def __init__(self, _cardAddress):
		if (not isinstance(_cardAddress, int)):
			raise TypeError("Should be an integer")	
		logging.debug("Create the class for the device with @:" + str(_cardAddress))
		self.address=_cardAddress
		self.hbeat=0
		
		self.status=self.manageHbeat(0)	
		self.loss_counter=0
		self.version=self.aboutVersion()
		
		
	def manageHbeat(self, hbeat_value):
		new_hbeat = i2cReadbus(self.address,R_HBEAT)
		if(new_hbeat != self.hbeat):
			status = 1 #Communication OK
			i2cWritebus(self.address,W_HBEAT,hbeat_value)
		else:
			status = 0 #Communication KO
			self.input = self.reply_input
		self.hbeat = new_hbeat
		return status
			
	def aboutVersion(self):
		self.version = i2cReadbus(self.address,R_VERSION)
		logging.info("The card with the @:" + str(self.address) + " is in the version :" + str(self.version))
		return self.version
		
	def updateOuput(self, _output,_value):
		if self.ouput[_output] == _value:
			return 0
		else:
			self.ouput[_output] = _value
			return 1

class IN8R8(CARDS):
	def __init__(self, _cardAddress,_reply_input):
		super(IN8R8,self).__init__(_cardAddress) #appel du constructeur de la classe parent (a verifier)
		self.input=0
		self.reply_input=_reply_input
		self.output=0
	
	def readCardInput(self, _address):
		readData=i2cReadbus(_address,R_INPUT)
		if  self.input == readData:
			return 0
		else :
			 self.input = readData
			 return 1

	def readCardOutput(self, _address):
		readData=i2cReadbus(_address,R_OUTPUT)
		if  self.output == readData:
			return 0
		else :
			 self.output = readData
			 return 1
			 
	
class IN4DIM4(CARDS):
	def __init__(self, _cardAddress,_reply_input):
		super(IN4DIM4,self).__init__(_cardAddress)  #appel du constructeur de la classe parent (a verifier)
		self.input=[0, 0, 0, 0]
		self.output=[0, 0, 0, 0]
		self.reply_input=_reply_input
		#self.setpoint=[0, 0, 0, 0]
		#self.fade=[0, 0, 0, 0]
		
# ----------------------------------------------------------------------------	
def i2cWritebus(_cardAddress, _command, _value):
	try:
		#logging.debug("Write I2C data on board @:" + str(_cardAddress) + " command:" + str(_command) + " valeur:" + str(_value))
		jeedom_bus.write_byte_data(int(_cardAddress),int(_command),int(_value))
	except: # exception if I2C read failed
		self.status = 0
		
# ----------------------------------------------------------------------------	
def i2cReadbus(_cardAddress, _command):
	try:
		data = jeedom_bus.read_byte_data(int(_cardAddress),int(_command))
		#logging.debug("Read I2C data on board @:" + str(_cardAddress) + " command:" + str(_command) + " valeur:" + str(data))
	except: # exception if I2C read failed
		self.status = 0
	return data
# ----------------------------------------------------------------------------
def listToDec(_list):
	data=0
	logging.debug("listToDec :" + str(_list))
	for i in _list : 
		data = data + (int(_list[i]) * (2 ** i))
		logging.debug("data :" + str(data) + " i :" + str(i))
	return data
# ----------------------------------------------------------------------------
# testBit() returns a nonzero result, 2**offset, if the bit at 'offset' is one.
def testBit(int_type, offset):
    mask = 1 << offset
    return(int_type & mask)
# ----------------------------------------------------------------------------
# setBit() returns an integer with the bit at 'offset' set to 1.
def setBit(int_type, offset):
    mask = 1 << offset
    return(int_type | mask)
# ----------------------------------------------------------------------------
# clearBit() returns an integer with the bit at 'offset' cleared.
def clearBit(int_type, offset):
    mask = ~(1 << offset)
    return(int_type & mask)
# ----------------------------------------------------------------------------
# toggleBit() returns an integer with the bit at 'offset' inverted, 0 -> 1 and 1 -> 0.
def toggleBit(int_type, offset):
    mask = 1 << offset
    return(int_type ^ mask)
# ----------------------------------------------------------------------------	
def findCardAdress(_address):
	if len(Eqts) == 0:
		return -1
	else :
		for index in range(len(Eqts)):
			if Eqts[index].address == _address:
				return index
				break

			
# ----------------------------------------------------------------------------			
def read_socket():
	try:
		global JEEDOM_SOCKET_MESSAGE
		if not JEEDOM_SOCKET_MESSAGE.empty():
			message = json.loads(jeedom_utils.stripped(JEEDOM_SOCKET_MESSAGE.get()))
			address = int(message['address'])
			if message['apikey'] != _apikey:
				logging.error("Invalid apikey from socket : " + str(message))
			
			if message['apikey'] != _apikey:
				logging.error("Invalid apikey from socket : " + str(message))
				
			cardId=findCardAdress(address)
						
			if message['cmd'] == 'add':
				if cardId == -1:
					logging.debug("Add the device with @:" + str(address)) 
					Eqts.append(IN8R8(address,0))				
			
			if message['cmd'] == 'send':
				if cardId != -1:
					if Eqts[cardId].address == address:
						logging.debug("The device with @:" + str(Eqts[cardId].address) + " is find for send command")
						output = int(message['output'])
						value = int(message['value'])
						if value !=0:
							Newoutput=setBit(Eqts[cardId].output,output)
						else :
							Newoutput=clearBit(Eqts[cardId].output,output)
						if Eqts[cardId].output!=Newoutput :
							Eqts[cardId].output=Newoutput
							i2cWritebus(Eqts[cardId].address,W_OUTPUT,Eqts[cardId].output)
						
			if message['cmd'] == 'del':
				if Eqts[cardId].address == address:
					logging.debug("Remove the device with @:" + str(Eqts[cardId].address))
					Eqts.remove(cardId)
	

	except Exception,e:
		logging.error('Error on read socket : '+str(e))	
# ----------------------------------------------------------------------------	
def read_i2cbus():
	for eqt in Eqts:
		if eqt.status != 0:
			if eqt.readCardInput(eqt.address):
				logging.debug("Update of input " + str(eqt.input) + " detected on board @:" + str(eqt.address))
				#Construction JSON
				action = {}
				action['address'] = str(eqt.address)
				action['input'] = str(eqt.input)
				try:
					globals.JEEDOM_COM.add_changes('devices::'+action['address'],action)
					logging.debug("send web")
				except Exception, e:
					pass
			if eqt.readCardOutput(eqt.address):
				logging.debug("Update of output " + str(eqt.output) + " detected on board @:" + str(eqt.address))
				#Construction JSON
				action = {}
				action['address'] = str(eqt.address)
				action['output'] = str(eqt.output)
				try:
					globals.JEEDOM_COM.add_changes('devices::'+action['address'],action)
					logging.debug("send web")
				except Exception, e:
					pass


		
# ----------------------------------------------------------------------------	
def cards_hbeat():
	for eqt in Eqts:
		eqt.manageHbeat(eqt.hbeat)
		if eqt.status == 0:
			logging.info("The card with the @:" + str(eqt.address) + " is KO")
		"""else :
			logging.debug("The card with the @ : " + str(eqt.address) + " is OK with heartbeat :" + str(eqt.hbeat))"""
# ----------------------------------------------------------------------------	


def listen():
	logging.debug("Start listening...")
	# Start I2C
	jeedom_bus = smbus.SMBus(1)
	jeedom_socket.open()
	logging.debug("Start deamon")
	try:
		while 1:
			time.sleep(0.03)
			cards_hbeat()
			read_i2cbus()
			read_socket()
	except KeyboardInterrupt:
		shutdown()

# ----------------------------------------------------------------------------
def handler(signum=None, frame=None):
	logging.debug("Signal %i caught, exiting..." % int(signum))
	shutdown()
	
# ----------------------------------------------------------------------------
def shutdown():
	logging.debug("Shutdown")
	logging.debug("Removing PID file " + str(_pidfile))
	try:
		os.remove(_pidfile)
	except:
		pass
	try:
		jeedom_socket.close()
	except:
		pass
	try:
		byte.close()
	except:
		pass
	logging.debug("Exit 0")
	sys.stdout.flush()
	os._exit(0)

# ----------------------------------------------------------------------------
_log_level = "error"
_socket_port = 55550
_socket_host = '127.0.0.1'
_device = 'auto'
_pidfile = '/tmp/i2cExt.pid'
_apikey = ''
_callback = ''
_cycle = 0.3
parser = argparse.ArgumentParser(description='i2cExt Daemon for Jeedom plugin')
parser.add_argument("--device", help="Device", type=str)
parser.add_argument("--socketport", help="Socketport for server", type=str)
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--cycle", help="Cycle to send event", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
args = parser.parse_args()

if args.device:
	_device = args.device
if args.socketport:
	_socket_port = int(args.socketport)
if args.loglevel:
	_log_level = args.loglevel
if args.callback:
	_callback = args.callback
if args.apikey:
	_apikey = args.apikey
if args.pid:
	_pidfile = args.pid
if args.cycle:
	_cycle = float(args.cycle)
	
jeedom_utils.set_log_level(_log_level)

logging.info('Start i2cExt daemon')
logging.info('Log level : '+str(_log_level))
logging.info('Socket port : '+str(_socket_port))
logging.info('Socket host : '+str(_socket_host))
logging.info('PID file : '+str(_pidfile))
logging.info('Device : '+str(_device))
logging.info('Apikey : '+str(_apikey))
logging.info('Callback : '+str(_callback))
logging.info('Cycle : '+str(_cycle))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)	

try:
	jeedom_utils.write_pid(str(_pidfile))
	globals.JEEDOM_COM = jeedom_com(apikey = _apikey,url = _callback,cycle=_cycle)
	print('api ',_apikey)
	if not globals.JEEDOM_COM.test():
		logging.error('Network communication issues. Please fixe your Jeedom network configuration.')
		shutdown()
	jeedom_bus = smbus.SMBus(1)
	jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)
	listen()
except Exception, e:
	logging.error('Fatal error : '+str(e))
	logging.debug(traceback.format_exc())
	shutdown()
