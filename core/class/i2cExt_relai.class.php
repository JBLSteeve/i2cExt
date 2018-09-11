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

class i2cExt_relai extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */
	public function postInsert()
	{
        $state = $this->getCmd(null, 'state');
        if ( ! is_object($state) ) {
            $state = new i2cExt_relaiCmd();
			$state->setName('Etat');
			$state->setEqLogic_id($this->getId());
			$state->setType('info');
			$state->setSubType('binary');
			$state->setLogicalId('state');
			$state->setEventOnly(1);
			$state->setDisplay('generic_type','LIGHT_STATE');
			$state->setTemplate('dashboard', 'light');
			$state->setTemplate('mobile', 'light');      
			$state->save();
		}
        $btn_on = $this->getCmd(null, 'btn_on');
        if ( ! is_object($btn_on) ) {
            $btn_on = new i2cExt_relaiCmd();
			$btn_on->setName('On');
			$btn_on->setEqLogic_id($this->getId());
			$btn_on->setType('action');
			$btn_on->setSubType('other');
			$btn_on->setLogicalId('btn_on');
			$btn_on->setEventOnly(1);
			$btn_on->setDisplay('generic_type','LIGHT_ON');
			$btn_on->save();
		}
        $btn_off = $this->getCmd(null, 'btn_off');
        if ( ! is_object($btn_off) ) {
            $btn_off = new i2cExt_relaiCmd();
			$btn_off->setName('Off');
			$btn_off->setEqLogic_id($this->getId());
			$btn_off->setType('action');
			$btn_off->setSubType('other');
			$btn_off->setLogicalId('btn_off');
			$btn_off->setEventOnly(1);
			$btn_off->setDisplay('generic_type','LIGHT_OFF');
			$btn_off->save();
		}
	}

	public function preUpdate()
	{
        $switch = $this->getCmd(null, 'switch');
        if ( is_object($switch) ) {
			$switch->remove();
		}
        $state = $this->getCmd(null, 'etat');
        if ( is_object($state) ) {
			$state->setLogicalId('state');
			$state->save();
		}
        $state_old = $this->getCmd(null, 'state');
        if ( is_object($state_old) && get_class ($state_old) != "i2cExt_relaiCmd" ) {
            $state = new i2cExt_relaiCmd();
			$state->setName($state_old->getName());
			$state->setEqLogic_id($this->getId());
			$state->setType('info');
			$state->setSubType('binary');
			$state->setLogicalId('state');
			$state->setEventOnly(1);
			$state->setIsHistorized($state_old->getIsHistorized());
			$state->setIsVisible($state_old->getIsVisible());
			$state->setDisplay('generic_type','LIGHT_STATE');
			$state->setTemplate('dashboard', 'light');
			$state->setTemplate('mobile', 'light');      
			$state->save();
			$state_old->remove();
		}
		elseif ( is_object($state_old) )
		{
			if ( $state_old->getDisplay('generic_type') == "" )
			{
				$state_old->setDisplay('generic_type','LIGHT_STATE');
				$state_old->save();
			}			
			if ( $state_old->getTemplate('dashboard') == "" )
			{
				$state_old->setTemplate('dashboard', 'light');
				$state_old->save();
			}			
			if ( $state_old->getTemplate('mobile') == "" )
			{
				$state_old->setTemplate('mobile', 'light');
				$state_old->save();
			}			
		}
        $btn_on_old = $this->getCmd(null, 'btn_on');
        if ( is_object($btn_on_old) && get_class ($btn_on_old) != "i2cExt_relaiCmd" ) {
            $btn_on = new i2cExt_relaiCmd();
			$btn_on->setName($btn_on_old->getName());
			$btn_on->setEqLogic_id($this->getId());
			$btn_on->setType('action');
			$btn_on->setSubType('other');
			$btn_on->setLogicalId('btn_on');
			$btn_on->setEventOnly(1);
			$btn_on->setIsHistorized($btn_on_old->getIsHistorized());
			$btn_on->setIsVisible($btn_on_old->getIsVisible());
			$btn_on->setDisplay('generic_type','LIGHT_ON');
			$btn_on->save();
			$btn_on_old->remove();
		}
 		elseif ( is_object($btn_on_old) )
		{
			if ( $btn_on_old->getDisplay('generic_type') == "" )
			{
				$btn_on_old->setDisplay('generic_type','LIGHT_ON');
				$btn_on_old->save();
			}			
		}
        $btn_off_old = $this->getCmd(null, 'btn_off');
        if ( is_object($btn_off_old) && get_class ($btn_off_old) != "i2cExt_relaiCmd" ) {
            $btn_off = new i2cExt_relaiCmd();
			$btn_off->setName($btn_off_old->getName());
			$btn_off->setEqLogic_id($this->getId());
			$btn_off->setType('action');
			$btn_off->setSubType('other');
			$btn_off->setLogicalId('btn_off');
			$btn_off->setEventOnly(1);
			$btn_off->setIsHistorized($btn_off_old->getIsHistorized());
			$btn_off->setIsVisible($btn_off_old->getIsVisible());
			$btn_off->setDisplay('generic_type','LIGHT_OFF');
			$btn_off->save();
			$btn_off_old->remove();
		}
 		elseif ( is_object($btn_off_old) )
		{
			if ( $btn_off_old->getDisplay('generic_type') == "" )
			{
				$btn_off_old->setDisplay('generic_type','LIGHT_OFF');
				$btn_off_old->save();
			}			
		}
	}

	public function preInsert()
	{
		$gceid = substr($this->getLogicalId(), strpos($this->getLogicalId(),"_")+2);
		$this->setEqType_name('i2cExt_relai');
		$this->setIsEnable(0);
		$this->setIsVisible(0);
	}

    public static function event() {
        $cmd = i2cExt_relaiCmd::byId(init('id'));
        if (!is_object($cmd)) {
            throw new Exception('Commande ID virtuel inconnu : ' . init('id'));
        }
		log::add('i2cExt','debug',"Receive push notification for ".$cmd->getName()." (". init('id').") : value = ".init('state'));
		if ($cmd->execCmd() != $cmd->formatValue(init('value'))) {
			$cmd->setCollectDate('');
			$cmd->event(init('value'));
		}
    }


    /*     * **********************Getteur Setteur*************************** */
}

class i2cExt_relaiCmd extends cmd 
{
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    public function execute($_options = null) {
		log::add('i2cExt','debug','execute');
		
		$eqLogic = $this->getEqLogic();
		if (!is_object($eqLogic) || $eqLogic->getIsEnable() != 1) {
            throw new Exception(__('Equipement desactivé impossible d\éxecuter la commande : ' . $this->getHumanName(), __FILE__));
        }
		$IPXeqLogic = eqLogic::byId(substr ($eqLogic->getLogicalId(), 0, strpos($eqLogic->getLogicalId(),"_")));
		$gceid = substr($eqLogic->getLogicalId(), strpos($eqLogic->getLogicalId(),"_")+2);

		if ( $this->getLogicalId() == 'btn_on' ) {
			log::add('i2cExt','debug',"execute - channel on");
			$message = trim(json_encode(array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'send','board' => $IPXeqLogic->getConfiguration('board'), 'address' => $IPXeqLogic->getConfiguration('address'), 'output::{','channel' .$gceid => '100')));
			$socket = socket_create(AF_INET, SOCK_STREAM, 0);
			socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'i2cExt'));
			socket_write($socket, trim($message), strlen(trim($message)));
			socket_close($socket);

		}
		else if ( $this->getLogicalId() == 'btn_off' ) {
			log::add('i2cExt','debug',"execute - channel off");
			$message = trim(json_encode(array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'send','board' => $IPXeqLogic->getConfiguration('board'), 'address' => $IPXeqLogic->getConfiguration('address'), 'output::{','channel' .$gceid => '0')));
			$socket = socket_create(AF_INET, SOCK_STREAM, 0);
			socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'i2cExt'));
			socket_write($socket, trim($message), strlen(trim($message)));
			socket_close($socket);

		}
		else
			return false;
	
    }
// utile ?
    public function imperihomeCmd() {
 		if ( $this->getLogicalId() == 'state' ) {
			return true;
		}
		elseif ( $this->getLogicalId() == 'impulsion' ) {
			return true;
		}
		elseif ( $this->getLogicalId() == 'commute' ) {
			return true;
		}
		else {
			return false;
		}
    }

	public function imperihomeGenerate($ISSStructure) {
		if ( $this->getLogicalId() == 'state' ) { // Sauf si on est entrain de traiter la commande "Mode", à ce moment là on indique un autre type
			$type = 'DevSwitch'; // Le type Imperihome qui correspond le mieux à la commande
		}
		elseif ( $this->getLogicalId() == 'impulsion' ) { // Sauf si on est entrain de traiter la commande "Mode", à ce moment là on indique un autre type
			$type = 'DevScene'; // Le type Imperihome qui correspond le mieux à la commande
			$type = 'DevSwitch'; // Le type Imperihome qui correspond le mieux à la commande
		}
		elseif ( $this->getLogicalId() == 'commute' ) { // Sauf si on est entrain de traiter la commande "Mode", à ce moment là on indique un autre type
			$type = 'DevScene'; // Le type Imperihome qui correspond le mieux à la commande
			$type = 'DevSwitch'; // Le type Imperihome qui correspond le mieux à la commande
		}
		else {
			return $info_device;
		}
		$eqLogic = $this->getEqLogic(); // Récupération de l'équipement de la commande
		$object = $eqLogic->getObject(); // Récupération de l'objet de l'équipement

		// Construction de la structure de base
		$info_device = array(
		'id' => $this->getId(), // ID de la commande, ne pas mettre autre chose!
		'name' => $eqLogic->getName()." - ".$this->getName(), // Nom de l'équipement que sera affiché par Imperihome: mettre quelque chose de parlant...
		'room' => (is_object($object)) ? $object->getId() : 99999, // Numéro de la pièce: ne pas mettre autre chose que ce code
		'type' => $type, // Type de l'équipement à retourner (cf ci-dessus)
		'params' => array(), // Le tableau des paramètres liés à ce type (qui sera complété aprés.
		);
		#$info_device['params'] = $ISSStructure[$info_device['type']]['params']; // Ici on vient copier la structure type: laisser ce code

		array_push ($info_device['params'], array("value" =>  '#' . $eqLogic->getCmd(null, 'state')->getId() . '#', "key" => "status", "type" => "infoBinary", "Description" => "Current status : 1 = On / 0 = Off"));
		if ( $this->getLogicalId() == 'state' ) { // Sauf si on est entrain de traiter la commande "Mode", à ce moment là on indique un autre type
			$info_device['actions']["setStatus"]["item"]["0"] = $eqLogic->getCmd(null, 'btn_off')->getId();
			$info_device['actions']["setStatus"]["item"]["1"] = $eqLogic->getCmd(null, 'btn_on')->getId();
		}
		elseif ( $this->getLogicalId() == 'impulsion' ) { // Sauf si on est entrain de traiter la commande "Mode", à ce moment là on indique un autre type
			$info_device['actions']["launchScene"] = $eqLogic->getCmd(null, 'impulsion')->getId();
		}
		elseif ( $this->getLogicalId() == 'commute' ) { // Sauf si on est entrain de traiter la commande "Mode", à ce moment là on indique un autre type
			$info_device['actions']["launchScene"] = $eqLogic->getCmd(null, 'commute')->getId();
		}
		// Ici on traite les autres commandes (hors "Mode")
		return $info_device;
	}
	
}
?>
