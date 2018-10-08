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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');
    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    if (init('action') === 'getCardAddress') {
        $return['IN8R8_Address'] = ['0x53','0x54','0x55','0x56','0x57','0x58','0x59','0x5A','0x5B','0x5C','0x5D','0x5E','0x5F','0x60','0x61','0x62'];
        $return['IN4DIM4_Address'] = ['0x20','0x21','0x22','0x23','0x24','0x25','0x26','0x27','0x28','0x29','0x2A','0x2B','0x2C','0x2D','0x2E','0x2F'];
        foreach (eqLogic::byType('i2cExt') as $eqLogic) {
            if (!is_object($eqLogic)) {
                continue;
            }
           		switch ($eqLogic->getConfiguration('board')) {
                	case 'IN8R8':
                    	$search = array_search($eqLogic->getConfiguration('address'), $return['IN8R8_Address'] );
                                    if ($search !== false) {
                                        array_splice($return['IN8R8_Address'],$search,1);
                                    }
                     	break;
                	case 'IN4DIM4':
                    	$search = array_search($eqLogic->getConfiguration('address'), $return['IN4DIM4_Address'] );
                                    if ($search !== false) {
                                        array_splice($return['IN4DIM4_Address'],$search,1);
                                    }
                     break;
            	}
            //}
        }
        ajax::success($return);
    }

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /***********Catch exception***************/
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
?>
