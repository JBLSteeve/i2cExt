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
require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

if (!jeedom::apiAccess(init('apikey'), 'i2cExt')) {
	echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
	die();
}

if (isset($_GET['test'])) {
	echo 'OK';
	die();
}
$result = json_decode(file_get_contents("php://input"), true);
if (!is_array($result)) {
	die();
}
$eqLogics = eqLogic::byType('i2cExt');

if (isset($result['devices'])) {
								
	foreach ($result['devices'] as $key => $device) 
	
	
	log::add('i2cExt','debug','device:' . print_r($device, true));
	
	
	
	// pour les relais
	foreach ($eqLogics as $eqLogic) {
		if (is_object($eqLogic)){
			if ($eqLogic->getConfiguration('address')==$device['address']){
				for ($compteurId = 0; $compteurId <= 7; $compteurId++) {
					if (isset($result['output'])) {
						foreach ($result['output'] as $key => $output) 
						//log::add('i2cExt','debug','output:' . print_r($output, true));
						$SubeqLogicOutput = eqLogic::byLogicalId($eqLogic->getId()."_R".$compteurId, 'i2cExt_relai');
						if ( is_object($SubeqLogicOutput) ) {
							if ($eqLogic->getObject_id()!='') {
								log::add('i2cExt','debug','mise à jour du relai : '.$SubeqLogicOutput->getObject_id() . ' avec la valeur:' . $output['channel' . (($SubeqLogicOutput->getObject_id()) - 1) ]);
								$statuscmd = $SubeqLogicOutput->getCmd(null, 'state');
								$statuscmd->setCollectDate('');
								$statuscmd->event($output['channel' . (($SubeqLogicOutput->getObject_id()) - 1) ]);	
							}
							if (compteurId ==7) {
								log::add('i2cExt','debug',"send change of output receive to dameon");
								$message = trim(json_encode(array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'receive','board' => $eqLogic->getConfiguration('board'), 'address' => $eqLogic->getConfiguration('address'), 'type' => 'output')));
								$socket = socket_create(AF_INET, SOCK_STREAM, 0);
								socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'i2cExt'));
								socket_write($socket, trim($message), strlen(trim($message)));
								socket_close($socket);
							}	
						}
					}
					if (isset($result['input'])) {
						foreach ($result['input'] as $key => $input) 
						log::add('i2cExt','debug','input:' . print_r($input, true));
						$SubeqLogicInput = eqLogic::byLogicalId($eqLogic->getId()."_B".$compteurId, 'i2cExt_bouton');
						if ( is_object($SubeqLogicInput) ) {
							if ($eqLogic->getObject_id()!='') {
								log::add('i2cExt','debug','mise à jour du bouton : '.$SubeqLogicInput->getObject_id() . ' avec la valeur:' . $input['channel' . (($SubeqLogicInput->getObject_id()) - 1) ]);
								$statuscmd = $SubeqLogicInput->getCmd(null, 'state');
								$statuscmd->setCollectDate('');
								$statuscmd->event($input['channel' . (($SubeqLogicInput->getObject_id()) - 1) ]);	
														
							}
							if (compteurId ==7) {
								log::add('i2cExt','debug',"send change of input receive to dameon");
								$message = trim(json_encode(array('apikey' => jeedom::getApiKey('i2cExt'), 'cmd' => 'receive','board' => $eqLogic->getConfiguration('board'), 'address' => $eqLogic->getConfiguration('address'), 'type' => 'input')));
								$socket = socket_create(AF_INET, SOCK_STREAM, 0);
								socket_connect($socket, '127.0.0.1', config::byKey('socketport', 'i2cExt'));
								socket_write($socket, trim($message), strlen(trim($message)));
								socket_close($socket);
							}
						}
					}
				}
			}
		}
	}
}