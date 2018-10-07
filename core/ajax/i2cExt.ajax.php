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
        $return['IN8R8_Address'] = ['0x53','0x54'];
        $return['IN4DIM4_Address'] = ['0x20','0x21'];
        foreach (eqLogic::byType('i2cExt') as $eqLogic) {
            if (!is_object($eqLogic)) {
                continue;
            }
           switch ($eqLogic->getConfiguration('board')) {
                case 'IN8R8':
                    $search = array_search($eqLogic->getConfiguration('adress'), $return['IN8R8_Address'] );
                                    if ($search !== false) {
                                        unset($return['IN8R8_Address'][$search]);
                                    }
                     break;
                case 'IN4DIM4':
                    $search = array_search($eqLogic->getConfiguration('adress'), $return['IN4DIM4_Address'] );
                                    if ($search !== false) {
                                        unset($return['IN4DIM4_Address'][$search]);
                                    }
                     break;
            }
        }
        ajax::success($return);
    }

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /***********Catch exception***************/
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
?>
