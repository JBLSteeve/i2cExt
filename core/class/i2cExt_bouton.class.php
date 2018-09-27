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

class i2cExt_bouton extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */
	public function postInsert()
	{
        $state = $this->getCmd(null, 'state');
        if ( ! is_object($state) ) {
            $state = new i2cExt_boutonCmd();
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
		$nbimpulsion = $this->getCmd(null, 'nbimpulsion');
		if ( ! is_object($nbimpulsion) ) {
            $nbimpulsion = new i2cExt_boutonCmd();
			$nbimpulsion->setName('Nombre d impulsion');
			$nbimpulsion->setEqLogic_id($this->getId());
			$nbimpulsion->setType('info');
			$nbimpulsion->setSubType('numeric');
			$nbimpulsion->setLogicalId('info');
			$nbimpulsion->setEventOnly(1);
			$nbimpulsion->setDisplay('generic_type','GENERIC_INFO');
			$nbimpulsion->save();
		}
	}

	public function preUpdate()
	{
        $nbimpulsion = $this->getCmd(null, 'nbimpulsion');
        if ( ! is_object($nbimpulsion) ) {
            $nbimpulsion = new i2cExt_boutonCmd();
			$nbimpulsion->setName('Nombre d impulsion');
			$nbimpulsion->setEqLogic_id($this->getId());
			$nbimpulsion->setType('info');
			$nbimpulsion->setSubType('numeric');
			$nbimpulsion->setLogicalId('nbimpulsion');
			$nbimpulsion->setEventOnly(1);
			$nbimpulsion->setDisplay('generic_type','GENERIC_INFO');
			$nbimpulsion->save();
		}
		else
		{
			if ( $nbimpulsion->getDisplay('generic_type') == "" )
			{
				$nbimpulsion->setDisplay('generic_type','GENERIC_INFO');
				$nbimpulsion->save();
			}
		}
        $state = $this->getCmd(null, 'etat');
        if ( is_object($state) ) {
			$state->setLogicalId('state');
			$state->save();
		}
        $state = $this->getCmd(null, 'state');
		if ( $state->getDisplay('generic_type') == "" )
		{
			$state->setDisplay('generic_type','LIGHT_STATE');
			$state->save();
		}			
		if ( $state->getTemplate('dashboard') == "" )
		{
			$state->setTemplate('dashboard', 'light');
			$state->save();
		}			
		if ( $state->getTemplate('mobile') == "" )
		{
			$state->setTemplate('mobile', 'light');
			$state->save();
		}	
	}

	public function preInsert()
	{
		$gceid = substr($this->getLogicalId(), strpos($this->getLogicalId(),"_")+2);
		$this->setEqType_name('i2cExt_bouton');
		$this->setIsEnable(0);
		$this->setIsVisible(0);
	}

    public static function event() {
        $cmd = i2cExt_boutonCmd::byId(init('id'));
        if (!is_object($cmd)) {
            throw new Exception('Commande ID virtuel inconnu : ' . init('id'));
        }
		log::add('i2cExt','debug',"Receive notification for ".$cmd->getName()." (". init('id').") : value = ".init('state'));
		if ($cmd->execCmd() != $cmd->formatValue(init('state'))) {
			$cmd->setCollectDate('');
			$cmd->event(init('state'));
		}
    }
    
    public function getLinkToConfiguration() {
        return 'index.php?v=d&p=i2cExt&m=i2cExt&id=' . $this->getId();
    }

    /*     * **********************Getteur Setteur*************************** */
}

class i2cExt_boutonCmd extends cmd 
{
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    public function formatValue($_value, $_quote = false) {
        if (trim($_value) == '') {
            return '';
        }
        if ($this->getType() == 'info') {
            switch ($this->getSubType()) {
                case 'binary':
                    $_value = strtolower($_value);
                    if ($_value == 'dn') {
                        $_value = 1;
                    }
                    if ($_value == 'up') {
                        $_value = 0;
                    }
					if ((is_numeric(intval($_value)) && intval($_value) > 1) || $_value || $_value == 1) {
                        $_value = 1;
                    }
                    return $_value;
            }
        }
        return $_value;
    }
    /*     * **********************Getteur Setteur*************************** */
	public function imperihomeGenerate($ISSStructure) {
		$eqLogic = $this->getEqLogic(); // Récupération de l'équipement de la commande
		if ( $this->getLogicalId() == 'state' ) { // Sauf si on est entrain de traiter la commande "Mode", à ce moment là on indique un autre type
			$btn_on = $eqLogic->getCmd(null, 'btn_on');
			if ( $btn_on->getIsVisible() )
			{
				$type = 'DevSwitch'; // Le type Imperihome qui correspond le mieux à la commande
			}
			else
			{
				$type = 'DevDoor'; // Le type Imperihome qui correspond le mieux à la commande
			}
		}
		else {
			return $info_device;
		}
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

		if ( $btn_on->getIsVisible() )
		{
			array_push ($info_device['params'], array("value" =>  '#' . $eqLogic->getCmd(null, 'state')->getId() . '#', "key" => "status", "type" => "infoBinary", "Description" => "Current status : 1 = On / 0 = Off"));
			$info_device['actions']["setStatus"]["item"]["0"] = $eqLogic->getCmd(null, 'btn_off')->getId();
			$info_device['actions']["setStatus"]["item"]["1"] = $eqLogic->getCmd(null, 'btn_on')->getId();
		}
		else
		{
			array_push ($info_device['params'], array("value" =>  '#' . $eqLogic->getCmd(null, 'state')->getId() . '#', "key" => "tripped", "type" => "infoBinary", "Description" => "Is the sensor tripped ? (0 = No / 1 = Tripped)"));
			array_push ($info_device['params'], array("value" =>  '0', "key" => "armable", "type" => "infoBinary", "Description" => "Ability to arm the device : 1 = Yes / 0 = No"));
			array_push ($info_device['params'], array("value" =>  '0', "key" => "ackable", "type" => "infoBinary", "Description" => "Ability to acknowledge alerts : 1 = Yes / 0 = No"));
		}
		// Ici on traite les autres commandes (hors "Mode")
		return $info_device;
	}
}
?>
