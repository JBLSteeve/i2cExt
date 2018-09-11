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
from collections import namedtuple

# Equipements
Eqts=[]

try:
	from jeedom.jeedom import *
except ImportError:
	print "Error: importing module jeedom.jeedom"
	sys.exit(1)

# ----------------------------------------------------------------------------
class CARDS(object):
	Register = namedtuple("Register", "W_OUTPUT R_OUTPUT R_INPUT W_HBEAT R_HBEAT R_VERSION"])

	def __init__(self, _cardAddress,_type):
		if (not isinstance(_cardAddress, int)):
			raise TypeError("Should be an integer")	
		#logging.debug("Create the class for the device with @:" + str(_cardAddress))
		self.type=_type
		self.address=_cardAddress
		self.hbeat=0
		self.status=self.manageHbeat(0)	
		self.loss_counter=0	#pas utilise
		self.version=self.aboutVersion()
		self._register = self.Register(W_OUTPUT=66, R_OUTPUT=65, R_INPUT=80, W_HBEAT=96, R_HBEAT=97, R_VERSION=144)
		
	def manageHbeat(self, hbeat_value):
		new_hbeat = jeedom_i2c.read(self.address,self._register.R_HBEAT)
		if(new_hbeat != self.hbeat):
			self.status = 1 #Communication OK
			jeedom_i2c.write(self.address,self._register.W_HBEAT,hbeat_value)
		else:
			self.status = 0 #Communication KO
			self.input = 0#self.reply_input
		self.hbeat = new_hbeat
		return self.status
			
	def aboutVersion(self):
		self.version = self.readCommand(self._register.R_VERSION)
		logging.info("The I2C card with the @:" + str(self.address) + " is in the version :" + str(self.version))
		return self.version
		
	def writeCommand(self,_command,_value):
		jeedom_i2c.write(self.address,_command,_value)
		logging.debug("Send command :" + str(_command) + " value :" + str(_value) + " for the IN8R8 board @:" + str(self.address))

	def readCommand(self,_command):
		value = jeedom_i2c.read(self.address,_command)
		logging.debug("Send command :" + str(_command) + " value :" + str(value) + " for the IN8R8 board @:" + str(self.address))
		return value
		
	def clearComIsOK(self):
		status=0
	
	def setComIsOK(self):
		status=1
	
	def updateOuput(self, _output,_value):
		if self.output[_output] == _value:
			return 0
		else:
			self.output[_output] = _value
			return 1
# ------------------------------------------------------------------------------
class IN8R8(CARDS):
	def __init__(self, _cardAddress,_type,_reply_input):
		if ((_cardAddress > 82) & (_cardAddress < 100)):
			super(IN8R8,self).__init__(_cardAddress,_type) #appel du constructeur de la classe parent (a verifier)
			self.inputchannel=8
			self.outputchannel=8
			self.input=0
			self.reply_input=_reply_input
			self.output=0
			self.routput=0
			self.routputChanged=0
			self.outputChanged=0
			self.inputChanged=0
		else :
			raise ValueError("The address " + str(_cardAddress) + " is not an IN8R8 card")	
			
# output methodes
	# status of the output setpoint
	def getSetpoint(self, _channel) :
		if jeedom_utils.testBit(self.output,_channel)==0:
			return 0
		else :
			return 1
	# set the output setpoint
	def newSP(self, _channel, _SP):
		self.outputChanged=1
		if _SP==0:
			self.output=jeedom_utils.clearBit(self.output,_channel)
		else :
			self.output=jeedom_utils.setBit(self.output,_channel)	
# input methodes
	def inputIsSet(self, _id) :
		if jeedom_utils.testBit(self.input,_id)==0:
			return 0
		else :
			return 1
# output feedback methodes		
		# status of the output feedback
	def outputIsSet(self, _id) :
		if jeedom_utils.testBit(self.routput,_id)==0:
			return 0
		else :
			return 1	

	# Read input of the board
	def readCardInput(self):
		input=jeedom_i2c.read(self.address,self._register.R_INPUT)
		#logging.debug("Read input :" + jeedom_utils.dec2bin(input,8) + " for the IN8R8 board @:" + str(self.address))
		if ((self.input != input)):# & (self.inputChanged==0)):
			logging.debug("Read new input :" + jeedom_utils.dec2bin(input,8) + " for the IN8R8 board @:" + str(self.address))
			self.input = input
			self.inputChanged=1
			
	# Read output feedback of the board
	def readCardOutput(self):
		routput=jeedom_i2c.read(self.address,self._register.R_OUTPUT)
		#logging.debug("Read output :" + jeedom_utils.dec2bin(routput,8) + " for the IN8R8 board @:" + str(self.address))
		if  ((self.routput != routput)):# & (self.routputChanged==0)):
			logging.debug("Read new output feedback :" + jeedom_utils.dec2bin(routput,8) + " for the IN8R8 board @:" + str(self.address))
			self.routput = routput
			self.routputChanged=1
		if  ((self.output == routput)):# & (self.routputChanged==0)):
			self.outputChanged=0
	
	# A deplacer en lib jeedom
	def write(self):
		jeedom_i2c.write(self.address,self._register.W_OUTPUT,self.output)
		logging.debug("Send output :" + jeedom_utils.dec2bin(self.output,8) + " for the IN8R8 board @:" + str(self.address))
		#self.outputChanged=0
	
	def writeCommand(self,_command,_value):
		jeedom_i2c.write(self.address,_command,_value)
		#logging.debug("Send command :" + str(_command) + " value :" + str(_value) + " for the board @:" + str(self.address))
# ------------------------------------------------------------------------------		
class IN4DIM4(CARDS):
	def __init__(self, _cardAddress,_type,_reply_input):
		super(IN4DIM4,self).__init__(_cardAddress,_type)  #appel du constructeur de la classe parent (a verifier)
		self.inputchannel=4
		self.outputchannel=4
		self.input=[0, 0, 0, 0]
		self.output=[0, 0, 0, 0]
		self.reply_input=_reply_input
		#self.setpoint=[0, 0, 0, 0]
		#self.fade=[0, 0, 0, 0]
# ----------------------------------------------------------------------------

# ------------------------------------------------------------------------------
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
			board=str(message['board'])
			
			if message['apikey'] != _apikey:
				logging.error("Invalid apikey from socket : " + str(message))
			
			if message['apikey'] != _apikey:
				logging.error("Invalid apikey from socket : " + str(message))
			
			cardId=findCardAdress(address)
					
			if cardId == -1:
				if message['cmd'] == 'add':
					logging.debug("Add the device with @:" + str(address)) 
					Eqts.append(IN8R8(address,board,0))				
			
			else:
				
				if message['cmd'] == 'receive':
					if message['type'] == 'input':
						Eqts[cardId].inputChanged = 0
					if message['type'] == 'output':
						Eqts[cardId].routputChanged = 0
					
				if message['cmd'] == 'remove':
					logging.debug("Remove the device with @:" + str(Eqts[cardId].address))
					Eqts.remove(cardId)
				
				if message['cmd'] == 'send':
					if 'channel' in str(message):
						for i in range(Eqts[cardId].outputchannel):
							if ('channel' + str(i)) in str(message):
								Eqts[cardId].newSP(i, int(message['channel' + str(i)]))
					else:
						if ((message['output']=='100') | (message['output']=='On') | (message['output']=='ON')):
							for i in range(Eqts[cardId].outputchannel):
								Eqts[cardId].newSP(i, 100)
						else:
							for i in range(Eqts[cardId].outputchannel):
								Eqts[cardId].newSP(i, 0)
			
	except Exception,e:
		logging.error('Error on read socket : '+str(e))	

# ----------------------------------------------------------------------------
def read_i2cbus():
	for eqt in Eqts:
		if eqt.status != 0:
			eqt.readCardInput()			# read from board the input 
			eqt.readCardOutput()		# read from board the output feedback
			
def write_i2cbus():
	for eqt in Eqts:
		if eqt.status != 0:
			if eqt.outputChanged != 0:
				eqt.write()				# Write to board the output
				

			
def write_socket():
	for eqt in Eqts:
		if eqt.status != 0:
			board ={}
			board['address'] = str(eqt.address).replace('\x00', '')
			board['board'] = str(eqt.type).replace('\x00', '')
			if eqt.inputChanged == 1:
				logging.debug("Send update of input for the board @:" + str(eqt.address))
				#Construction JSON
				input ={}
				input['address'] = str(eqt.address).replace('\x00', '')
				input['board'] = str(eqt.type).replace('\x00', '')
				for i in range(eqt.inputchannel):
					input['channel' + str(i)] = str(eqt.inputIsSet(i))
	
				try:
					globals.JEEDOM_COM.add_changes('devices::'+board['address'],board)
					globals.JEEDOM_COM.add_changes('input::',input)
					#eqt.inputChanged = 0
				except Exception, e:
					logging.error("Send to jeedom error for input:" + jeedom_utils.dec2bin(eqt.input,8) + " on board @:" + str(eqt.address))
			
			if eqt.routputChanged == 1:
				logging.debug("Update of read output for the board @:" + str(eqt.address))
				#Construction JSON
				routput = {}
				routput['address'] = str(eqt.address).replace('\x00', '')
				routput['board'] = str(eqt.type).replace('\x00', '')
				for i in range(eqt.outputchannel):
					routput['channel' + str(i)] = str(eqt.outputIsSet(i)).replace('\x00', '')
				try:
					globals.JEEDOM_COM.add_changes('devices::'+board['address'],board)
					globals.JEEDOM_COM.add_changes('output::',routput)
					#eqt.routputChanged = 0
				except Exception, e:
					logging.error("Send to jeedom error for output feedback:" + jeedom_utils.dec2bin(eqt.routput,8) + " on board @:" + str(eqt.address))
			
# ----------------------------------------------------------------------------	
def cards_hbeat():
	for eqt in Eqts:
		eqt.manageHbeat(eqt.hbeat)
		if eqt.status == 0:
			logging.info("The I2C card with the @:" + str(eqt.address) + " is KO")
		"""else :
			logging.debug("The card with the @ : " + str(eqt.address) + " is OK with heartbeat :" + str(eqt.hbeat))"""
# ----------------------------------------------------------------------------	


def listen():
	logging.debug("Start listening...")
	# Start I2C
	jeedom_i2c.open()
	jeedom_socket.open()
	logging.debug("Start deamon")
	try:
		while 1:
			#Faire toutes les secondes
			cards_hbeat()
			
			time.sleep(0.03)
			
			# Read i2c bus for boards inputs
			read_i2cbus()
			# write i2c bus for boards outputs
			write_i2cbus()	
			
			# Read from jeedom socket request
			read_socket()
			# Send to jeedom socket feedback
			write_socket()
			
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
		jeedom_i2c.close()
	except:
		pass
	logging.debug("Exit 0")
	sys.stdout.flush()
	os._exit(0)

# ----------------------------------------------------------------------------
_log_level = "error"
_socket_port = 55550
_socket_host = '127.0.0.1'
_device = '1'
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

_device=1
if _device is None:
	logging.error('No i2c device found')
	shutdown()	

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)	

try:
	jeedom_utils.write_pid(str(_pidfile))
	globals.JEEDOM_COM = jeedom_com(apikey = _apikey,url = _callback,cycle=_cycle)
	print('api ',_apikey)
	if not globals.JEEDOM_COM.test():
		logging.error('Network communication issues. Please fixe your Jeedom network configuration.')
		shutdown()
	jeedom_i2c = jeedom_i2c(port=_device)
	jeedom_socket = jeedom_socket(port=_socket_port,address=_socket_host)
	listen()
except Exception, e:
	logging.error('Fatal error : '+str(e))
	logging.debug(traceback.format_exc())
	shutdown()
