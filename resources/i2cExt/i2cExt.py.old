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
from abc import ABCMeta, abstractmethod
from threading import Thread, Event, Lock

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
	print ("Error: importing module jeedom.jeedom")
	sys.exit(1)

# ----------------------------------------------------------------------------
class CARDS(object):
	__metaclass__ = ABCMeta	

	def __init__(self, _cardAddress,_board):
		if (not isinstance(_cardAddress, int)):
			raise TypeError("Should be an integer")	
		
		# Cards Registers
		self.W_HBEAT=96 #0x60
		self.R_HBEAT=97 #0x61
		self.R_VERSION=144 #0x90
		# Cards datas
		self.board=_board
		self.address=_cardAddress
		self.hbeat=0
		self._status=0
		self._status=self.manageHbeat(0)	
		self.version=self.aboutVersion()
		#logging.debug("Create the class CARDS for the device with @:" + str(_cardAddress))
		
	def manageHbeat(self, hbeat_value):
		'''Check communication with cards

		Read cards heartbit and check with last card value. If different, card is online else no communication.
		if card is online, send application heartbit to card.

		hbeat_value : numeric - application hearbit that will be send to card.

		Return current status of communication:
		- 0 : No communication
		- 1 : Online
		'''
		new_hbeat = jeedom_i2c.read(self.address,self.R_HBEAT)
		if(new_hbeat != self.hbeat):
			write_socket("status",self.address,self.board,"","Alive")
			if(self._status ==0):
				logging.debug("The card with the @ : " + str(self.address) + " is OK with heartbeat :" + str(new_hbeat))
			self.ComOK=1  #Communication OK
			jeedom_i2c.write(self.address,self.W_HBEAT,hbeat_value)
		else:
			write_socket("status",self.address,self.board,"","LossCom")
			if(self._status ==1):	
				logging.info("The I2C card with the @:" + str(self.address) + " is KO")
			self.ComOK=0  #Communication KO
			self.input = self.reply_input
		self.hbeat = new_hbeat
		return self._status
		
	def aboutVersion(self):
		'''Return card version
		When called, request card hardware version through i2c connection and return it.
		'''
		if(self.ComOK==1):
			self.version = self.readCommand(self.R_VERSION)
			logging.info("The I2C card with the @:" + str(self.address) + " is in the version :" + str(self.version))
			return self.version
		
	def writeCommand(self,_command,_value):
		'''Send raw command through i2c
		Create an i2c write request with _command and _value as argument.

		_command : numeric - id of requested command
		_value : any - argument of command.
		'''
		if(self.ComOK==1):
			jeedom_i2c.write(self.address,_command,_value)
			logging.debug("Send command :" + str(_command) + " value :" + str(_value) + " for board @:" + str(self.address))

	def readCommand(self,_command):
		'''Receive raw command through i2c
		Create an i2c read request with _command as argument.

		_command : numeric - id of requested command
		'''
		if(self.ComOK==1):
			value = jeedom_i2c.read(self.address,_command)
			logging.debug("Read command :" + str(_command) + " value :" + str(value) + " for board @:" + str(self.address))
			return value
		
	@property
	def ComOK(self):
		return self._status

	@ComOK.setter
	def ComOK(self, val):
		if isinstance(val, int):
			if val == 0 or val == 1:
				self._status = val
			else:
				raise ValueError("ComOK is either equal to 1 or 0")
		else:
			raise TypeError("ComOK has to be an integer")
		
	@abstractmethod
	def readCardInput(self):
		'''Read input signal from card
		This method sends a read request throught i2c connection to receive registrer containing input values and update class internal buffer.
		'''
		return -1

	@abstractmethod
	def readCardOutput(self):
		'''Read output signal from card
		This method sends a read request throught i2c connection to receive registrer containing ouput values and update class internal buffer.
		'''
		return -1
	
	@abstractmethod
	def write(self):
		'''Write output to card
		This method sends local ouput buffer to card throught i2c connection for application.
		'''
		return -1
# ------------------------------------------------------------------------------
class IN8R8(CARDS):
	def __init__(self, _cardAddress,_board,_reply_input):
		if ((_cardAddress > 82) & (_cardAddress < 100)):
			super(IN8R8,self).__init__(_cardAddress,_board) #appel du constructeur de la classe parent (a verifier)
			# IN8R8 Registers
			self.W_OUTPUT=66 #0x42
			self.R_OUTPUT=65 #0x41
			self.R_INPUT=80 #0x50
			
			# IN8R8 datas
			self.outputchannel=8
			self.input=[False,False,False,False,False,False,False,False]
			self.inputOn=[False,False,False,False,False,False,False,False]
			self.maintained_delay=[4,4,4,4,4,4,4,4]
			self.timer_maintained=[0,0,0,0,0,0,0,0]
			self.reply_input=_reply_input
			self.output=0
			self.routput=[False,False,False,False,False,False,False,False]
			self.routputChanged=[False,False,False,False,False,False,False,False]
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
		input=splitbyte(jeedom_i2c.read(self.address,self.R_INPUT))
		for x in range(len(input)):
			if (input[x] != self.input[x]):	#Verifier que ce sont des tableau (longueur ...)
				if ((input[x] != False) & (self.inputOn[x] ==False)) :	# Cas haut -> bas, relachement avant ON
					logging.debug("Read pulse on input:" + str(x) + " for the IN8R8 board @:" + str(self.address))
					write_socket("input",self.address,self.board,x,"Pulse")
				elif ((input[x] != True) & (self.inputOn[x] ==True)) :	# Cas haut -> bas , relachement apres ON
					logging.debug("Read OFF on input:" + str(x) + " for the IN8R8 board @:" + str(self.address))
					write_socket("input",self.address,self.board,x,"Off")
								
				self.input[x] = input[x]
			
			if (self.input[x] == True):
				if (self.inputOn[x] == False):
					self.timer_maintained[x]+=1
					if (self.timer_maintained[x]>self.maintained_delay[x]):
						self.inputOn[x] = True
						logging.debug("Read ON on input:" + str(x) + " for the IN8R8 board @:" + str(self.address))
						write_socket("input",self.address,self.board,x,"On")
			else :
				self.inputOn[x] = False
				self.timer_maintained[x]=0
		globals.JEEDOM_COM.send_changes_async()
				
	# Read output feedback of the board
	def readCardOutput(self):
		routput=splitbyte(jeedom_i2c.read(self.address,self.R_OUTPUT))
		#logging.debug("Read output :" + str(routput) + " for the IN8R8 board @:" + str(self.address))
		if  ((self.routput != routput)):
			for x in range(len(routput)):
				if (routput[x] != self.routput[x]):
					logging.debug("Read new output feedback on channel:" + str(x) + " at " + str(routput[x]) + " for the IN8R8 board @:" + str(self.address))
					if (routput[x]==True):
						write_socket("output",self.address,self.board,x,"100")
					else:
						write_socket("output",self.address,self.board,x,"0")
						
					self.routput[x] = routput[x]
					self.routputChanged[x]=True
			globals.JEEDOM_COM.send_changes_async()
	
	# Send the status to the board
	def write(self):
		if self.outputChanged != 0:
			jeedom_i2c.write(self.address,self.W_OUTPUT,self.output)
			self.outputChanged = 0
			logging.debug("Send output :" + jeedom_utils.dec2bin(self.output,8) + " for the IN8R8 board @:" + str(self.address))
	
	def writeCommand(self,_command,_value):
		jeedom_i2c.write(self.address,_command,_value)
		logging.debug("Send command :" + str(_command) + " value :" + str(_value) + " for the board @:" + str(self.address))
# ------------------------------------------------------------------------------		
class IN4DIM4(CARDS):
	def __init__(self, _cardAddress,_board,_reply_input):
		if ((_cardAddress > 39) & (_cardAddress < 57)):
			super(IN4DIM4,self).__init__(_cardAddress,_board)  #appel du constructeur de la classe parent (a verifier)
			# IN4DIM4 Registers
			self.W_OUTPUT=[7,23,39,55] #0x07,0x17,0x27,0x37,0x47
			self.W_FADE=[8,24,40,56]  #0x08,0x18,0x28,0x38,0x48
			self.R_OUTPUT=[10,26,42,58] #0x0A,0x1A,0x2A,0x3A	
			self.R_INPUT=80 #0x50
			
			# IN4DIM4 datas
			self.outputchannel=4
			self.input=[False,False,False,False]
			self.inputOn=[False,False,False,False]
			self.reply_input=_reply_input
			self.maintained_delay=[4,4,4,4]
			self.timer_maintained=[0,0,0,0]
		
			self.output=[0, 0, 0, 0]
			self.fade=[0, 0, 0, 0]
			self.routput=[0, 0, 0, 0]
			self.routputChanged=[False,False,False,False]
			self.outputChanged=[False,False,False,False]
			self.fadeChanged=[False,False,False,False]
			self.inputChanged=0
			
			#self.setpoint=[0, 0, 0, 0]
			#self.fade=[0, 0, 0, 0]
			

		
		else :
			raise ValueError("The address " + str(_cardAddress) + " is not an IN4DIM4 card")	
# output methodes
	# status of the output setpoint
	def getSetpoint(self, _channel) :
		return self.output[_channel]

			
	# set the output setpoint
	def newSP(self, _channel, _SP):
		self.outputChanged[_channel]=True
		self.output[_channel] = _SP

	# set the output setpoint
	def newFA(self, _channel, _FA):
		self.fadeChanged[_channel]=True
		self.fade[_channel] = _FA
				
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
		input=splitbyte(jeedom_i2c.read(self.address,self.R_INPUT))
		for x in range(len(self.input)):
			if (input[x] != self.input[x]):	#Verifier que ce sont des tableau (longueur ...)
				if ((input[x] != False) & (self.inputOn[x] ==False)) :	# Cas haut -> bas, relachement avant ON
					logging.debug("Read pulse on input:" + str(x) + " for the IN4DIM4 board @:" + str(self.address))
					write_socket("input",self.address,self.board,x,"Pulse")
				elif ((input[x] != True) & (self.inputOn[x] ==True)) :	# Cas haut -> bas , relachement apres ON
					logging.debug("Read OFF on input:" + str(x) + " for the IN4DIM4 board @:" + str(self.address))
					write_socket("input",self.address,self.board,x,"Off")
								
				self.input[x] = input[x]
			
			if (self.input[x] == True):
				if (self.inputOn[x] == False):
					self.timer_maintained[x]+=1
					if (self.timer_maintained[x]>self.maintained_delay[x]):
						self.inputOn[x] = True
						logging.debug("Read ON on input:" + str(x) + " for the IN4DIM4 board @:" + str(self.address))
						write_socket("input",self.address,self.board,x,"On")
			else :
				self.inputOn[x] = False
				self.timer_maintained[x]=0
		globals.JEEDOM_COM.send_changes_async()
				
	# Read output feedback of the board
	def readCardOutput(self):
		routput=[0,0,0,0]
		for x in range(len(self.routput)):
			routput[x]=jeedom_i2c.read(self.address,self.R_OUTPUT[x])
			#logging.debug("Read output " + str(x) + " at value:" + str(routput[x]) + " for the IN4DIM4 board @:" + str(self.address))
			if (routput[x] != self.routput[x]):
				logging.debug("Read new output feedback on channel:" + str(x) + " at " + str(routput[x]) + " for the IN4DIM4 board @:" + str(self.address))
				write_socket("output",self.address,self.board,x,routput[x])
				self.routput[x] = routput[x]
				self.routputChanged[x]=True
		globals.JEEDOM_COM.send_changes_async()
	
	# Send the status to the board
	def write(self):
		for x in range(len(self.output)):
			if self.outputChanged[x] != False:
				jeedom_i2c.write(self.address,self.W_OUTPUT[x],self.output[x])
				self.outputChanged[x] = False
				logging.debug("Send output " + str(x) + " at value:" + str(self.output[x]) + " for the IN4DIM4 board @:" + str(self.address))
			if self.fadeChanged[x] != False:
				jeedom_i2c.write(self.address,self.W_FADE[x],self.fade[x])
				self.fadeChanged[x] = False
				logging.debug("Send output " + str(x) + " new fade:" + str(self.fade[x]) + " for the IN4DIM4 board @:" + str(self.address))
	
	def writeCommand(self,_command,_value):
		jeedom_i2c.write(self.address,_command,_value)
		logging.debug("Send command :" + str(_command) + " value :" + str(_value) + " for the board @:" + str(self.address))
		
# ----------------------------------------------------------------------------
def splitbyte(_byte):
	return [b == '1' for b in bin(_byte)[2:].rjust(8)[::-1]] 
# ------------------------------------------------------------------------------
def findCardAdress(_address):
	if len(Eqts) == 0:
		return None
	else:
		for eqt in Eqts:
			if eqt.address == _address :
				return eqt
		else:
			return None
	
# ----------------------------------------------------------------------------			
def read_socket():

	try:
		global JEEDOM_SOCKET_MESSAGE
		if not JEEDOM_SOCKET_MESSAGE.empty():
			message = json.loads(jeedom_utils.stripped(JEEDOM_SOCKET_MESSAGE.get()))
			logging.debug("message : " + str(message))
			address = int(message['address'])
			board=str(message['board'])
			
			if message['apikey'] != _apikey:
				raise KeyError()
						
 			
			card=findCardAdress(address)

			
			if card == None:
				if message['cmd'] == 'add':
					#logging.debug("Add the device with @:" + str(address)) 
					if message['board'] == 'IN8R8':
						Eqts.append(IN8R8(address,board,[False,False,False,False,False,False,False,False]))
					if message['board'] == 'IN4DIM4':
						Eqts.append(IN4DIM4(address,board,[0,0,0,0]))
			
			else:
				if message['cmd'] == 'receive':
					if message['type'] == 'input':
						card.inputChanged = 0
					if message['type'] == 'output':
						card.routputChanged = 0
						
				elif message['cmd'] == 'remove':
					logging.debug("Remove the device with @:" + str(card.address))
					card.remove(card.address)

				elif message['cmd'] == 'send':
					if 'channel' in str(message):
						if 'value' in str(message):
							if ((message['output']['channel']=='ALL') | (message['output']['channel']=='All' )):
								if ((message['output']=='100') | (message['output']=='On') | (message['output']=='ON')):
									for i in range(card.outputchannel):
										card.newSP(i, 100)
								else:
									for i in range(card.outputchannel):
										card.newSP(i, 0)
							else:
								if ((message['output']['value']=='On') | (message['output']['value']=='ON')):
									card.newSP(int(message['output']['channel']), 100)
								elif ((message['output']['value']=='Off') | (message['output']['value']=='OFF')):
									card.newSP(int(message['output']['channel']), 0)
								else:
									card.newSP(int(message['output']['channel']), int(message['output']['value']))
						if 'fade' in str(message):
							card.newFA(int(message['output']['channel']), int(message['output']['fade']))


								
	except TypeError as te:
		logging.error('Error on read socket : '+ str(te) + str(message))
	except KeyError as ke:
		logging.error("Invalid apikey from socket : " + str(ke) + " message :" + str(message))
	#Catch all other exception and print exception name raised
	except:
		logging.error('Error on read socket : '+ str(sys.exc_info()[0]))	

# ----------------------------------------------------------------------------
def read_i2cbus():
	for eqt in Eqts:		# Loop for each boards
		if eqt.ComOK == 1:
			eqt.readCardInput()			# read from board the input 
			eqt.readCardOutput()		# read from board the output feedback

# ----------------------------------------------------------------------------			
def write_i2cbus():
	for eqt in Eqts:		# Loop for each boards
		if eqt.ComOK == 1:
			eqt.write()				# Write to board the output

# ----------------------------------------------------------------------------
def write_socket(type,address,board,channelid,value):	#type=input or output or status
	logging.debug("Send update of " + str(type) + " for the board @:" + str(address))
	#Construction JSON
	message ={}
	message['address'] = str(address).replace('\x00', '')
	message['board'] = str(board).replace('\x00', '')
	status ={}
	if  (type == "status"):
		status['status'] = str(value)
	else :
		status['channel' + str(channelid)] = str(value)
		
	try:
		globals.JEEDOM_COM.add_changes('devices::'+message['address'],message)
		globals.JEEDOM_COM.add_changes(str(type) + '::',status)
		
	except Exception:
		logging.error("Send to jeedom error for channel " +str(type) + " id :" + str(channelid) + " on board @:" + str(address))
				
# ----------------------------------------------------------------------------	
def cards_hbeat(hbeat):
	for eqt in Eqts:
		eqt.manageHbeat(hbeat)
		globals.JEEDOM_COM.send_changes_async()

# ----------------------------------------------------------------------------	
def main(ioLock):
	logging.debug("Start deamon")
	try:
		while 1:
			time.sleep(0.1)
			with ioLock:
				# Read i2c bus for boards inputs
				read_i2cbus()
				# write i2c bus for boards outputs
				write_i2cbus()	
				
			# Read from jeedom socket request
			read_socket()
			#Release lock

			
	except KeyboardInterrupt:
		shutdown()

# ----------------------------------------------------------------------------
def handler(signum=None, frame=None):
	logging.debug("Signal %i caught, exiting..." % int(signum))
	shutdown()
	
# - Shutdown ---------------------------------------------------------------------------
def shutdown():
	logging.debug("Shutdown")
	logging.debug("Removing PID file " + str(_pidfile))
	
	#Request halt of communication check thread
	_stop.set()
	#Wait for task to close
	_ComOKThread.join()

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

# - Threading functions --------------------------------------------------------------------
def CommunicationCheck(stopEvent, ioLock, period):
	hbeat=0
	while not stopEvent.is_set():
		#Get lock
		ioLock.acquire()
		#Get hearbit of all cards
		cards_hbeat(hbeat)
		#Manage daemon hearbit for all cards
		hbeat=hbeat+1
		if (hbeat>256):
			hbeat=0
		#release lock
		ioLock.release()

		#Wait for the given period before or a stopEvent (this enable to quit without waiting for a full period)
		stopEvent.wait(period)

# - Main program ---------------------------------------------------------------------------
_log_level = "error"
_socket_port = 55550
_socket_host = '127.0.0.1'
_device = '1'
_pidfile = '/tmp/i2cExt.pid'
_apikey = ''
_callback = ''
_refreshPeriod = 5.0
_cycle = 0.3
parser = argparse.ArgumentParser(description='i2cExt Daemon for Jeedom plugin')
parser.add_argument("--device", help="Device", type=int)
parser.add_argument("--socketport", help="Socketport for server", type=str)
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--callback", help="Callback", type=str)
parser.add_argument("--apikey", help="Apikey", type=str)
parser.add_argument("--cycle", help="Cycle to send event", type=str)
parser.add_argument("--pid", help="Pid file", type=str)
parser.add_argument("--refreshPeriod", help="Heartbit pooling period (in seconds, default : 1s)", type=float)
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
if args.refreshPeriod:
	_refreshPeriod = args.refreshPeriod
	
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
logging.info('Refresh period : '+str(_refreshPeriod))


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

	logging.debug("Start ...")
	# Start Ethernet socket
	jeedom_socket.open()
	# Start I2C
	jeedom_i2c.open()
	

	logging.debug("Start communication check thread")

	_ioLock = Lock()
	_stop = Event()
	_ComOKThread = Thread(name="ComOKTask", target=CommunicationCheck, args=(_stop, _ioLock, _refreshPeriod,))
	_ComOKThread.start()
	
	#Start main program
	main(_ioLock)
except:
	logging.error('Fatal error : '+ str(sys.exc_info()[0]))
	logging.debug(traceback.format_exc())
	shutdown()