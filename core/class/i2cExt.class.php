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

include_file('core', 'i2cExt_relai', 'class', 'i2cExt');
include_file('core', 'i2cExt_bouton', 'class', 'i2cExt');


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
		/*$port = config::byKey('port', 'i2cExt');
		if ($port != 'auto') {
			$port = jeedom::getUsbMapping($port);
			if (is_string($port)) {
				if (@!file_exists($port)) {
					$return['launchable'] = 'nok';
					$return['launchable_message'] = __('Le port n\'est pas configuré', __FILE__);
				}
				exec(system::getCmdSudo() . 'chmod 777 ' . $port . ' > /dev/null 2>&1');
			}
		}*/
		return $return;
	}

	public static function deamon_start() {
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$port ='auto';
		/* Todo detection port I2C
		$port = config::byKey('port', 'i2cExt');
		if ($port != 'auto') {
			$port = jeedom::getUsbMapping($port);
		}*/
		$i2cExt_path = realpath(dirname(__FILE__) . '/../../resources/i2cExt');
		$cmd = '/usr/bin/python ' . $i2cExt_path . '/i2cExt.py';
		$cmd .= ' --device ' . $port;
		$cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel('i2cExt'));
		$cmd .= ' --socketport ' . config::byKey('socketport', 'i2cExt');
		$cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/i2cExt/core/php/i2cExt.php';
		$cmd .= ' --apikey ' . jeedom::getApiKey('i2cExt');
		$cmd .= ' --cycle ' . config::byKey('cycle', 'i2cExt');
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
		system::kill('JeePlcBusd.py');
		system::fuserk(config::byKey('socketport', 'i2cExt'));
		$port = config::byKey('port', 'i2cExt');
		/*if ($port != 'auto') {
			system::fuserk(jeedom::getUsbMapping($port));
		}*/
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
		$value = array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'remove','address' => $this->getConfiguration('address'),'board' => $this->getConfiguration('board'));
		$value = json_encode($value);
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'i2cExt'));
		socket_write($socket, $value, strlen($value));
		socket_close($socket);
	}

	public function allowDevice() {
		//OK
		$value = array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'add','address' => $this->getConfiguration('address'),'board' => $this->getConfiguration('board'));
		$value = json_encode($value);
		$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'i2cExt'));
		socket_write($socket, $value, strlen($value));
		socket_close($socket);
	}
	
	public static function pull() {
	//Appele toute les secondes scan ou verification status
	//$cmd="i2cget -y 1 ";
	//log::add('i2cExt','debug','pull $this->getLogicalId(): '.$this->getLogicalId());
	//$this->checkAndUpdateCmd($this->getLogicalId(), true);
	//$cmd.=$eqLogic->getConfiguration('address');
	//$cmd.="83 0x50";
	//log::add('i2cExt','debug',$cmd);
	//$result = shell_exec($cmd);  
	//log::add('i2cExt','debug',$result);  
	
		//$message = trim(json_encode(array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'send', 'address' => '83', 'registre' => '66', 'data' => '00')));
		//$message = trim(json_encode(array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'add','board' => 'IN8R8', 'address' => '83')));
		//$message = trim(json_encode(array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'send', 'address' => '83', 'output' => '0', 'value' => '1')));

		//$socket = socket_create(AF_INET, SOCK_STREAM, 0);
		//socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'i2cExt'));
		//socket_write($socket, trim($message), strlen(trim($message)));
		//socket_close($socket);
		
		
		
		//$message = trim(json_encode(array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'send', 'address' => '83', 'output' => '1', 'value' => '1')));
	}

	
	public function preInsert(){
		$this->setIsVisible(0);
	}

	public function postInsert(){
		log::add('i2cExt','debug',"function post");
		$cmd = $this->getCmd(null, 'updatetime');
		if ( ! is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName('Dernier refresh');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('updatetime');
			$cmd->setUnite('');
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setIsHistorized(0);
			$cmd->setEventOnly(1);
			$cmd->setDisplay('generic_type','GENERIC_INFO');
			$cmd->save();		
		}
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
			$all_on->setName('All On');
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
			$all_off->setName('All Off');
			$all_off->setEqLogic_id($this->getId());
			$all_off->setType('action');
			$all_off->setSubType('other');
			$all_off->setLogicalId('all_off');
			$all_off->setEventOnly(1);
			$all_off->setDisplay('generic_type','GENERIC_ACTION');
			$all_off->save();
		}
		for ($compteurId = 0; $compteurId <= 7; $compteurId++) {
			if ( ! is_object(self::byLogicalId($this->getId()."_R".$compteurId, 'i2cExt_relai')) ) {
				log::add('i2cExt','debug','Creation relai : '.$this->getId().'_R'.$compteurId);
				$eqLogic = new i2cExt_relai();
				$eqLogic->setLogicalId($this->getId().'_R'.$compteurId);
				$eqLogic->setName('Relai ' . ($compteurId+1));
				$eqLogic->setObject_id($compteurId+1);
				$eqLogic->save();
			}
		}
		for ($compteurId = 0; $compteurId <= 7; $compteurId++) {
			if ( ! is_object(self::byLogicalId($this->getId()."_B".$compteurId, 'i2cExt_bouton')) ) {
				log::add('i2cExt','debug','Creation bouton : '.$this->getId().'_B'.$compteurId);
				$eqLogic = new i2cExt_bouton();
				$eqLogic->setLogicalId($this->getId().'_B'.$compteurId);
				$eqLogic->setName('Bouton ' . ($compteurId+1));
				$eqLogic->setObject_id($compteurId+1);
				$eqLogic->save();
			}
		}
	}
		
	public function preUpdate(){
		log::add('i2cExt','debug',"function preUp");
		if ( $this->getIsEnable() )
		{
			//log::add('i2cExt','debug','Carte @',$this->getConfiguration('address'));
			//log::add('i2cExt','debug','get '.preg_replace("/:[^:]*@/", ":XXXX@", $this->getUrl()). 'status.xml');
			// faire le test de présence réseau I2C
			
			/*$this->xmlstatus = true; 
			if ( $this->xmlstatus === false )
				throw new Exception(__('La carte n\'est correctement connectée au réseau I2C',__FILE__));*/
		}
	}

	public function postUpdate(){
		for ($compteurId = 0; $compteurId <= 7; $compteurId++) {
			if ( ! is_object(self::byLogicalId($this->getId()."_R".$compteurId, 'i2cExt_relai')) ) {
				log::add('i2cExt','debug','Creation relai : '.$this->getId().'_R'.$compteurId);
				$eqLogic = new i2cExt_relai();
				$eqLogic->setLogicalId($this->getId().'_R'.$compteurId);
				$eqLogic->setName('Relai ' . ($compteurId+1));
				$eqLogic->setObject_id($compteurId+1);
				$eqLogic->save();
			}
		}
		for ($compteurId = 0; $compteurId <= 7; $compteurId++) {
			if ( ! is_object(self::byLogicalId($this->getId()."_B".$compteurId, 'i2cExt_bouton')) ) {
				log::add('i2cExt','debug','Creation bouton : '.$this->getId().'_B'.$compteurId);
				$eqLogic = new i2cExt_bouton();
				$eqLogic->setLogicalId($this->getId().'_B'.$compteurId);
				$eqLogic->setName('Bouton ' . ($compteurId+1));
				$eqLogic->setObject_id($compteurId+1);
				$eqLogic->save();
			}
		}
		$cmd = $this->getCmd(null, 'status');
		if ( ! is_object($cmd) ) {
			$cmd = new cmd();
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
		$cmd = $this->getCmd(null, 'updatetime');
		if ( ! is_object($cmd)) {
			$cmd = new cmd();
			$cmd->setName('Dernier refresh');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('updatetime');
			$cmd->setUnite('');
			$cmd->setType('info');
			$cmd->setSubType('string');
			$cmd->setIsHistorized(0);
			$cmd->setEventOnly(1);
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
			$all_on->setName('All On');
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
		}

		$all_off = $this->getCmd(null, 'all_off');
		if ( ! is_object($all_off)) {
            $all_off = new i2cExtCmd();
			$all_off->setName('All Off');
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
		}
	}

	public function getChildEq(){
		$ChildList = array();
		foreach (self::byType('i2cExt_relai') as $eqLogic) {
			if ( substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
				array_push($ChildList, $eqLogic->getId());
			}
		}
		foreach (self::byType('i2cExt_bouton') as $eqLogic) {
			if ( substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
				array_push($ChildList, $eqLogic->getId());
			}
		}
		return $ChildList;
	}

	public function preRemove(){
		foreach (self::byType('i2cExt_relai') as $eqLogic) {
			if ( substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
				log::add('i2cExt','debug','Suppression relai : '.$eqLogic->getName());
				$eqLogic->remove();
			}
		}
		foreach (self::byType('i2cExt_bouton') as $eqLogic) {
			if ( substr($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")) == $this->getId() ) {
				log::add('i2cExt','debug','Suppression bouton : '.$eqLogic->getName());
				$eqLogic->remove();
			}
		}
	$this->disallowDevice();	// Suppression dans le module python
	}

	public function configPush() {
		log::add('i2cExt','debug',"function config push");
		if ( $this->getIsEnable() ) {
			log::add('i2cExt','debug',"get ".preg_replace("/:[^:]*@/", ":XXXX@", $this->getUrl()));
			$liste_seuil_bas = explode(',', init('seuil_bas'));
			$liste_seuil_haut = explode(',', init('seuil_haut'));
			
			foreach (explode(',', init('eqLogicPush_id')) as $_eqLogic_id) {
				$eqLogic = eqLogic::byId($_eqLogic_id);
				if (!is_object($eqLogic)) {
					throw new Exception(__('Impossible de trouver l\'équipement : ', __FILE__) . $_eqLogic_id);
				}
				if ( method_exists($eqLogic, "configPush" ) ) {
					if ( get_class ($eqLogic) == "i2cExt_analogique" )
					{
						$eqLogic->configPush($this->getUrl(), $pathjeedom, config::byKey("internalAddr"), config::byKey("internalPort"), array_shift($liste_seuil_bas), array_shift($liste_seuil_haut));
					}
					else
					{
						$eqLogic->configPush($this->getUrl(), $pathjeedom, config::byKey("internalAddr"), config::byKey("internalPort"));
					}
				}
			}
		}
	}

	public function event() {
		log::add('i2cExt','debug',"function event");
		foreach (eqLogic::byType('i2cExt') as $eqLogic) {
			log::add('i2cExt','debug',"+");
			if ( $eqLogic->getId() == init('id') ) {
				$eqLogic->scan();
			}
		}
	}
		
	public function getImage() {
			// A faire
			return 'plugin/i2cExt/core/config/device/' . $this->getConfiguration('board') . '.jpg';
	}
    
	public function scan() {
		if ( $this->getIsEnable() ) {
			log::add('i2cExt','debug','scan '.$this->getName());
		}
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
			$message = trim(json_encode(array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'send','board' => $eqLogic->getConfiguration('board'), 'address' => $eqLogic->getConfiguration('address'), 'output' => '100')));
			$socket = socket_create(AF_INET, SOCK_STREAM, 0);
			socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'i2cExt'));
			socket_write($socket, trim($message), strlen(trim($message)));
			socket_close($socket);

		}
		elseif ( $this->getLogicalId() == 'all_off' )
		{
			log::add('i2cExt','debug',"execute - all off");
			$message = trim(json_encode(array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'send','board' => $eqLogic->getConfiguration('board'), 'address' => $eqLogic->getConfiguration('address'), 'output' => '0')));
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
