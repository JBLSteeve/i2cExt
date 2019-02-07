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
log::add('i2cExt','debug','json :' . file_get_contents("php://input"));

$result = json_decode(file_get_contents("php://input"), true);
if (!is_array($result)) {
	die();
}
$eqLogics = eqLogic::byType('i2cExt');

//log::add('i2cExt','debug','device:' . print_r($result, true));

if (isset($result['devices'])) {
								
foreach ($result['devices'] as $key => $device)// decodage de l'entete

	foreach ($eqLogics as $eqLogic) {
		if (is_object($eqLogic)){
			if (hexdec($eqLogic->getConfiguration('address'))==$device['address']){
			
				if (isset($result['status'])) {
					foreach ($result['status'] as $key => $status) {
						if (isset($status['status'])) {
							$cmd = $eqLogic->getCmd(null, 'status');
							if ($status['status'] == "Alive") {
								//log::add('i2cExt','debug','board:' . $device['board'] . ' address:' . $device['address'] . ' Alive');
								$cmd->event(100);
								$cmd->setConfiguration('value',100);
							}
							else {
								//log::add('i2cExt','debug','board:' . $device['board'] . ' address:' . $device['address'] . ' LossCom');
								$cmd->event(0);
								$cmd->setConfiguration('value',0);
							}
						}
					}
				}
					
				// Récupération de l'état des outputs de la carte
				if (isset($result['output'])) {
					foreach ($result['output'] as $key => $output) 
					for ($compteurId = 0; $compteurId <= 7; $compteurId++) {
						if (isset($output['channel' . $compteurId])) {
							log::add('i2cExt','debug','board:' . $device['board'] . ' address:' . $device['address'] . ' channel' . $compteurId . ':' . $output['channel' . $compteurId]);
							$SubeqLogicOutput = eqLogic::byLogicalId($eqLogic->getId()."_O".$compteurId, 'i2cExt_output');
								if ( is_object($SubeqLogicOutput) ) {
									if ($eqLogic->getObject_id()!='') {
										$statuscmd = $SubeqLogicOutput->getCmd(null, 'state');
										#$statuscmd->setCollectDate('');
										$statuscmd->setCollectDate(date('Y-m-d H:i:s'));
										$statuscmd->event($output['channel' . $compteurId]);
										$statuscmd->setConfiguration('value',$output['channel' . $compteurId]);
										
										#$statuscmdd = $SubeqLogicOutput->getCmd(null, 'target');
										#$statuscmd->setCollectDate('');
										#$statuscmdd->setCollectDate(date('Y-m-d H:i:s'));
										#$statuscmdd->event($output['channel' . $compteurId]);
										#$statuscmdd->setConfiguration('value',$output['channel' . $compteurId]);
									}
								}
							}
						}
					}
				// récupération de l'état des entrée de la carte
				if (isset($result['input'])) {
					foreach ($result['input'] as $key => $input) 
					for ($compteurId = 0; $compteurId <= 7; $compteurId++) {
						if (isset($input['channel' . $compteurId])) {
						//log::add('i2cExt','debug','board:' . $device['board'] . ' address:' . $device['address'] . ' channel' . $compteurId . ':' . $input['channel' . $compteurId]);
						$SubeqLogicInput = eqLogic::byLogicalId($eqLogic->getId()."_I".$compteurId, 'i2cExt_input');
								if ( is_object($SubeqLogicInput) ) {
									if ($eqLogic->getObject_id()!='') {
										$statuscmd = $SubeqLogicInput->getCmd(null, 'state');
										#$statuscmd->setCollectDate('');
										//$statuscmd->setCollectDate(date('Y-m-d H:i:s'));
										#$cmd->event($recu);
										#$cmd->setConfiguration('value',$recu);
										if ( $input['channel' . $compteurId] == 'Pulse') {
											//log::add('i2cExt','debug',' channel' . $compteurId . ':Pulse');
											$statuscmd->event(100);
											$statuscmd->setConfiguration('value',100);
											sleep(0.5);
											$statuscmd->event(0);
											$statuscmd->setConfiguration('value',0);
											// Incrémentation a chaque pulse
											$nbimpulsion_cmd = $SubeqLogicInput->getCmd(null, 'nbimpulsion');
											$nbimpulsion_cmd->event($nbimpulsion_cmd->execCmd() + 1);
										}elseif ( $input['channel' . $compteurId] == 'On') {	
											//log::add('i2cExt','debug',' channel' . $compteurId . ':On');
											$statuscmd->event(100);
											$statuscmd->setConfiguration('value',100);
										}else{	
											//log::add('i2cExt','debug',' channel' . $compteurId . ':Off');
											$statuscmd->event(0);
											$statuscmd->setConfiguration('value',0);
										}
									}
								}
							}
						}
					}
				
			}
			else {
			// carte inconnue
			}
		}
	}
		 	
}
