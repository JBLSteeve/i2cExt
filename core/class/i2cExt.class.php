<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

include_file('core', 'i2cExt_output', 'class', 'i2cExt');
include_file('core', 'i2cExt_input', 'class', 'i2cExt');


class i2cExt extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

	public static function deamon_info() {
		$return = array();
		$return['log'] = 'i2cExt';
		$return['state'] = 'nok';
		$pid_file = jeedom::getTmpFolder('i2cExt') . '/deamon.pid';
		if (file_exists($pid_file)) {
			$pid = trim(file_get_contents($pid_file));
			if (is_numeric($pid) && posix_getsid($pid)) {
				$return['state'] = 'ok';
			} else {
				shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null;rm -rf ' . $pid_file . ' 2>&1 > /dev/null;');
			}
		}
		$return['launchable'] = 'ok';
		return $return;
	}

	public static function deamon_start() {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$i2cExt_path = realpath(dirname(__FILE__) . '/../../resources/i2cExt');
		// ajout 'nice -n 19 ' pour limiter la conso CPU
		$cmd = 'nice -n 19 /usr/bin/python ' . $i2cExt_path . '/i2cExt.py';
		$cmd .= ' --device ' . config::byKey('port', 'i2cExt');
		$cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel('i2cExt'));
		$cmd .= ' --socketport ' . config::byKey('socketport', 'i2cExt');
		$cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/i2cExt/core/php/i2cExt.php';
		$cmd .= ' --apikey ' . jeedom::getApiKey('i2cExt');
		$cmd .= ' --refreshPeriod ' . config::byKey('refreshPeriod', 'i2cExt');
		$cmd .= ' --pid ' . jeedom::getTmpFolder('i2cExt') . '/deamon.pid';
		log::add('i2cExt', 'info', 'Lancement démon i2cExt : ' . $cmd);
		exec($cmd . ' >> ' . log::getPathToLog('i2cExt') . ' 2>&1 &');
		$i = 0;
		while ($i < 30) {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 30) {
			log::add('i2cExt', 'error', 'Impossible de lancer le démon i2cExt, vérifiez le log', 'unableStartDeamon');
			return false;
		}
		message::removeAll('i2cExt', 'unableStartDeamon');
		sleep(2);
		self::sendIdToDeamon();
		config::save('include_mode', 0, 'i2cExt');
		log::add('i2cExt', 'info', 'Démon i2cExt lancé');
		return true;
	}

	public static function deamon_stop() {
		$pid_file = jeedom::getTmpFolder('i2cExt') . '/deamon.pid';
		if (file_exists($pid_file)) {
			$pid = intval(trim(file_get_contents($pid_file)));
			system::kill($pid);
		}
		system::kill('i2cExt.py');
		system::fuserk(config::byKey('socketport', 'i2cExt'));
		sleep(1);
	}

	public static function sendIdToDeamon() {
		// OK
		foreach (self::byType('i2cExt') as $eqLogic) {
			usleep(300);
			$eqLogic->allowDevice();
		}
	}
	
	public function disallowDevice() {
		//OK
		if ($this ->getLogicalId() ==''){
				return;
		}
		$value = array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'remove','address' => hexdec($this->getConfiguration('address')),'board' => $this->getConfiguration('board'));
		$value = json_encode($value);
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'i2cExt'));
		socket_write($socket, $value, strlen($value));
		socket_close($socket);
	}

	public function allowDevice() {
		//OK
		$value = array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'add','address' => hexdec($this->getConfiguration('address')),'board' => $this->getConfiguration('board'));
		$value = json_encode($value);
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'i2cExt'));
		socket_write($socket, $value, strlen($value));
		socket_close($socket);
	}
	
	public static function pull() {
	//Appele toute les secondes scan ou verification status
	}
	
	public function preInsert(){
		$this->setIsVisible(0);
	}

	public function postInsert(){
		log::add('i2cExt','debug',"function post");

		$cmd = $this->getCmd(null, 'status');
		if ( ! is_object($cmd) ) {
			$cmd = new i2cExtCmd();
			$cmd->setName('Etat');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setType('info');
			$cmd->setSubType('binary');
			$cmd->setLogicalId('status');
			$cmd->setIsVisible(1);
			$cmd->setEventOnly(1);
			$cmd->setDisplay('generic_type','GENERIC_INFO');
			$cmd->save();
		}
        $all_on = $this->getCmd(null, 'all_on');
        if ( ! is_object($all_on) ) {
            $all_on = new i2cExtCmd();
            if ($this->getConfiguration('board')=="IN8P4"){
				$all_on->setName('All Economique');
			}else {
				$all_on->setName('All On');
			}
			$all_on->setEqLogic_id($this->getId());
			$all_on->setType('action');
			$all_on->setSubType('other');
			$all_on->setLogicalId('all_on');
			$all_on->setEventOnly(1);
			$all_on->setDisplay('generic_type','GENERIC_ACTION');
			$all_on->save();
		}
        $all_off = $this->getCmd(null, 'all_off');
        if ( ! is_object($all_off) ) {
            $all_off = new i2cExtCmd();
            if ($this->getConfiguration('board')=="IN8P4"){
				$all_off->setName('All Confort');
			}else {
				$all_off->setName('All Off');
			}
			$all_off->setEqLogic_id($this->getId());
			$all_off->setType('action');
			$all_off->setSubType('other');
			$all_off->setLogicalId('all_off');
			$all_off->setEventOnly(1);
			$all_off->setDisplay('generic_type','GENERIC_ACTION');
			$all_off->save();
		}
		/*
		foreach (self::byType('i2cExt_output') as $eqLogic) {
			if ( substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
				log::add('i2cExt','debug','Suppression output : '.$eqLogic->getName());
				$eqLogic->remove();
			}
		}
		foreach (self::byType('i2cExt_input') as $eqLogic) {
			if ( substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
				log::add('i2cExt','debug','Suppression input : '.$eqLogic->getName());
				$eqLogic->remove();
			}
		}
		*/
		if ($this->getConfiguration('board')=="IN8R8") {
			for ($compteurId = 0; $compteurId <= 7; $compteurId++) {
				if ( ! is_object(self::byLogicalId($this->getId()."_O".$compteurId, 'i2cExt_output')) ) {
					log::add('i2cExt','debug','Creation output for IN8R8  : '.$this->getId().'_O'.$compteurId);
					$eqLogic = new i2cExt_output();
					$eqLogic->setLogicalId($this->getId().'_O'.$compteurId);
					$eqLogic->setName($this->getName() . ' - Sortie ' . ($compteurId+1));
					$eqLogic->setConfiguration('board',$this->getConfiguration('board'));
					$eqLogic->save();
				}
			}
			for ($compteurId = 0; $compteurId <= 7; $compteurId++) {
				if ( ! is_object(self::byLogicalId($this->getId()."_I".$compteurId, 'i2cExt_input')) ) {
					log::add('i2cExt','debug','Creation input for IN8R8 : '.$this->getId().'_I'.$compteurId);
					$eqLogic = new i2cExt_input();
					$eqLogic->setLogicalId($this->getId().'_I'.$compteurId);
					$eqLogic->setName($this->getName() . ' - Entrée ' . ($compteurId+1));
					$eqLogic->setConfiguration('board',$this->getConfiguration('board'));
					$eqLogic->save();
				}
			}
		} else if ($this->getConfiguration('board')=="IN8P4") {
			for ($compteurId = 0; $compteurId <= 3; $compteurId++) {
				if ( ! is_object(self::byLogicalId($this->getId()."_O".$compteurId, 'i2cExt_output')) ) {
					log::add('i2cExt','debug','Creation output for IN8P4  : '.$this->getId().'_O'.$compteurId);
					$eqLogic = new i2cExt_output();
					$eqLogic->setLogicalId($this->getId().'_O'.$compteurId);
					$eqLogic->setName($this->getName() . ' - Sortie ' . ($compteurId+1));
					$eqLogic->setConfiguration('board',$this->getConfiguration('board'));
					$eqLogic->save();
				}
			}
			for ($compteurId = 0; $compteurId <= 8; $compteurId++) {
				if ( ! is_object(self::byLogicalId($this->getId()."_I".$compteurId, 'i2cExt_input')) ) {
					log::add('i2cExt','debug','Creation input for IN8P4 : '.$this->getId().'_I'.$compteurId);
					$eqLogic = new i2cExt_input();
					$eqLogic->setLogicalId($this->getId().'_I'.$compteurId);
					$eqLogic->setName($this->getName() . ' - Entrée ' . ($compteurId+1));
					$eqLogic->setConfiguration('board',$this->getConfiguration('board'));
					$eqLogic->save();
				}
			}
		}else if ($this->getConfiguration('board')=="IN4DIM4") {
			for ($compteurId = 0; $compteurId <= 3; $compteurId++) {
				if ( ! is_object(self::byLogicalId($this->getId()."_O".$compteurId, 'i2cExt_dim')) ) {
					log::add('i2cExt','debug','Creation dim for IN4DIM4 : '.$this->getId().'_O'.$compteurId);
					$eqLogic = new i2cExt_dim();
					$eqLogic->setLogicalId($this->getId().'_O'.$compteurId);
					$eqLogic->setName($this->getName() . ' - Sortie ' . ($compteurId+1));
					$eqLogic->setConfiguration('board',$this->getConfiguration('board'));
					$eqLogic->save();
				}
			}
			for ($compteurId = 0; $compteurId <= 3; $compteurId++) {
				if ( ! is_object(self::byLogicalId($this->getId()."_I".$compteurId, 'i2cExt_input')) ) {
					log::add('i2cExt','debug','Creation input for IN4DIM4: '.$this->getId().'_I'.$compteurId);
					$eqLogic = new i2cExt_input();
					$eqLogic->setLogicalId($this->getId().'_I'.$compteurId);
					$eqLogic->setName($this->getName() . ' - Entrée ' . ($compteurId+1));
					$eqLogic->setConfiguration('board',$this->getConfiguration('board'));
					$eqLogic->save();
				}
			} 
		}
	}
		
	public function preUpdate(){
		log::add('i2cExt','debug',"function preUp");
		if ( $this->getIsEnable() )
		{
			// todo
		}
	}

	public function postUpdate(){
		foreach (self::byType('i2cExt_output') as $eqLogic) {
			if ( substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
				log::add('i2cExt','debug','Suppression output : '.$eqLogic->getName());
				$eqLogic->remove();
			}
		}
		foreach (self::byType('i2cExt_input') as $eqLogic) {
			if ( substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
				log::add('i2cExt','debug','Suppression input : '.$eqLogic->getName());
				$eqLogic->remove();
			}
		}
		if ($this->getConfiguration('board')=="IN8R8") {
			for ($compteurId = 0; $compteurId <= 7; $compteurId++) {
				if ( ! is_object(self::byLogicalId($this->getId()."_O".$compteurId, 'i2cExt_output')) ) {
					log::add('i2cExt','debug','Creation output for IN8R8  : '.$this->getId().'_O'.$compteurId);
					$eqLogic = new i2cExt_output();
					$eqLogic->setLogicalId($this->getId().'_O'.$compteurId);
					$eqLogic->setName($this->getName() . ' - Sortie ' . ($compteurId+1));
					$eqLogic->setConfiguration('board',$this->getConfiguration('board'));
					$eqLogic->save();
				}
			}
			for ($compteurId = 0; $compteurId <= 7; $compteurId++) {
				if ( ! is_object(self::byLogicalId($this->getId()."_I".$compteurId, 'i2cExt_input')) ) {
					log::add('i2cExt','debug','Creation input for IN8R8 : '.$this->getId().'_I'.$compteurId);
					$eqLogic = new i2cExt_input();
					$eqLogic->setLogicalId($this->getId().'_I'.$compteurId);
					$eqLogic->setName($this->getName() . ' - Entrée ' . ($compteurId+1));
					$eqLogic->setConfiguration('board',$this->getConfiguration('board'));
					$eqLogic->save();
				}
			}
		} else 	if ($this->getConfiguration('board')=="IN8P4") {
			for ($compteurId = 0; $compteurId <= 3; $compteurId++) {
				if ( ! is_object(self::byLogicalId($this->getId()."_O".$compteurId, 'i2cExt_output')) ) {
					log::add('i2cExt','debug','Creation output for IN8P4  : '.$this->getId().'_O'.$compteurId);
					$eqLogic = new i2cExt_output();
					$eqLogic->setLogicalId($this->getId().'_O'.$compteurId);
					$eqLogic->setName($this->getName() . ' - Sortie ' . ($compteurId+1));
					$eqLogic->setConfiguration('board',$this->getConfiguration('board'));
					$eqLogic->save();
				}
			}
			for ($compteurId = 0; $compteurId <= 8; $compteurId++) {
				if ( ! is_object(self::byLogicalId($this->getId()."_I".$compteurId, 'i2cExt_input')) ) {
					log::add('i2cExt','debug','Creation input for IN8P4 : '.$this->getId().'_I'.$compteurId);
					$eqLogic = new i2cExt_input();
					$eqLogic->setLogicalId($this->getId().'_I'.$compteurId);
					$eqLogic->setName($this->getName() . ' - Entrée ' . ($compteurId+1));
					$eqLogic->setConfiguration('board',$this->getConfiguration('board'));
					$eqLogic->save();
				}
			}
		}else if ($this->getConfiguration('board')=="IN4DIM4") {
			for ($compteurId = 0; $compteurId <= 3; $compteurId++) {
				if ( ! is_object(self::byLogicalId($this->getId()."_O".$compteurId, 'i2cExt_dim')) ) {
					log::add('i2cExt','debug','Creation dim for IN4DIM4 : '.$this->getId().'_O'.$compteurId);
					$eqLogic = new i2cExt_output();
					$eqLogic->setLogicalId($this->getId().'_O'.$compteurId);
					$eqLogic->setName($this->getName() . ' - Sortie ' . ($compteurId+1));
					$eqLogic->setConfiguration('board',$this->getConfiguration('board'));
					$eqLogic->save();
				}
			}
			for ($compteurId = 0; $compteurId <= 3; $compteurId++) {
				if ( ! is_object(self::byLogicalId($this->getId()."_I".$compteurId, 'i2cExt_input')) ) {
					log::add('i2cExt','debug','Creation input for IN4DIM4: '.$this->getId().'_I'.$compteurId);
					$eqLogic = new i2cExt_input();
					$eqLogic->setLogicalId($this->getId().'_I'.$compteurId);
					$eqLogic->setName($this->getName() . ' - Entrée ' . ($compteurId+1));
					$eqLogic->setConfiguration('board',$this->getConfiguration('board'));
					$eqLogic->save();
				}
			} 
		}
		$cmd = $this->getCmd(null, 'status');
		if ( ! is_object($cmd) ) {
			$cmd = new i2cExtcmd();
			$cmd->setName('Etat');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setType('info');
			$cmd->setSubType('binary');
			$cmd->setLogicalId('status');
			$cmd->setIsVisible(1);
			$cmd->setEventOnly(1);
			$cmd->setDisplay('generic_type','GENERIC_INFO');
			$cmd->save();
		}
		else
		{
			if ( $cmd->getDisplay('generic_type') == "" )
			{
				$cmd->setDisplay('generic_type','GENERIC_INFO');
				$cmd->save();
			}
		}
		$all_on = $this->getCmd(null, 'all_on');
		if ( ! is_object($all_on)) {
            $all_on = new i2cExtCmd();
            if ($this->getConfiguration('board')=="IN8P4"){
				$all_on->setName('All Economique');
			}else {
				$all_on->setName('All On');
			}
			$all_on->setEqLogic_id($this->getId());
			$all_on->setType('action');
			$all_on->setSubType('other');
			$all_on->setLogicalId('all_on');
			$all_on->setEventOnly(1);
			$all_on->setDisplay('generic_type','GENERIC_ACTION');
			$all_on->save();
		}
		else
		{
			if ( $all_on->getDisplay('generic_type') == "" )
			{
				$all_on->setDisplay('generic_type','GENERIC_ACTION');
				$all_on->save();
			}
			 if ($this->getConfiguration('board')=="IN8P4"){
				$all_on->setName('All Economique');
			}else {
				$all_on->setName('All On');
			}
		}

		$all_off = $this->getCmd(null, 'all_off');
		if ( ! is_object($all_off)) {
            $all_off = new i2cExtCmd();
			if ($this->getConfiguration('board')=="IN8P4"){
				$all_off->setName('All Confort');
			}else {
				$all_off->setName('All Off');
			}
			$all_off->setEqLogic_id($this->getId());
			$all_off->setType('action');
			$all_off->setSubType('other');
			$all_off->setLogicalId('all_off');
			$all_off->setEventOnly(1);
			$all_off->setDisplay('generic_type','GENERIC_ACTION');
			$all_off->save();
		}
		else
		{
			if ( $all_off->getDisplay('generic_type') == "" )
			{
				$all_off->setDisplay('generic_type','GENERIC_ACTION');
				$all_off->save();
			}
			if ($this->getConfiguration('board')=="IN8P4"){
				$all_off->setName('All Confort');
			}else {
				$all_off->setName('All Off');
			}
		}
		

		$this->allowDevice();
	}


	public function getChildEq(){
		$ChildList = array();
		foreach (self::byType('i2cExt_output') as $eqLogic) {
			if ( substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
				array_push($ChildList, $eqLogic->getId());
			}
		}
		foreach (self::byType('i2cExt_input') as $eqLogic) {
			if ( substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
				array_push($ChildList, $eqLogic->getId());
			}
		}
		return $ChildList;
	}

	public function preRemove(){
		foreach (self::byType('i2cExt_output') as $eqLogic) {
			if ( substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
				log::add('i2cExt','debug','Suppression output : '.$eqLogic->getName());
				$eqLogic->remove();
			}
		}
		foreach (self::byType('i2cExt_input') as $eqLogic) {
			if ( substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
				log::add('i2cExt','debug','Suppression input : '.$eqLogic->getName());
				$eqLogic->remove();
			}
		}
	$this->disallowDevice();	// Suppression dans le module python
	}

	public function event() {
		log::add('i2cExt','debug',"function event");

	}
		
	public function getImage() {
			return 'plugin/i2cExt/core/config/device/' . $this->getConfiguration('board') . '.jpg';
	}
    
    /*     * **********************Getteur Setteur*************************** */
}

class i2cExtCmd extends cmd 
{
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*     * **********************Getteur Setteur*************************** */
    public function execute($_options = null) {
		
		$eqLogic = $this->getEqLogic();
        if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
            throw new Exception(__('Equipement desactivé impossible d\éxecuter la commande : ' . $this->getHumanName(), __FILE__));
        }
	
		if ( $this->getLogicalId() == 'all_on' )
		{
			log::add('i2cExt','debug',"execute - all on");
			$message = trim(json_encode(array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'send','board' => $eqLogic->getConfiguration('board'), 'address' => hexdec($eqLogic->getConfiguration('address')), 'output' => array('channel' => 'ALL' ,'value' => 'ON'))));
			$socket = socket_create(AF_INET, SOCK_STREAM, 0);
			socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'i2cExt'));
			socket_write($socket, trim($message), strlen(trim($message)));
			socket_close($socket);

		}
		elseif ( $this->getLogicalId() == 'all_off' )
		{
			log::add('i2cExt','debug',"execute - all off");
			$message = trim(json_encode(array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'send','board' => $eqLogic->getConfiguration('board'), 'address' => hexdec($eqLogic->getConfiguration('address')), 'output' => array('channel' => 'ALL' ,'value' => 'OFF'))));
			$socket = socket_create(AF_INET, SOCK_STREAM, 0);
			socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'i2cExt'));
			socket_write($socket, trim($message), strlen(trim($message)));
			socket_close($socket);
		}
		else
			return false;
    }
}
?>
