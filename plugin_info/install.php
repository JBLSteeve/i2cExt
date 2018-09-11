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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function i2cExt_install() {
    $cron = cron::byClassAndFunction('i2cExt', 'pull');
	if ( ! is_object($cron)) {
        $cron = new cron();
        $cron->setClass('i2cExt');
        $cron->setFunction('pull');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule('* * * * *');
        $cron->save();
	}
	config::remove('listChildren', 'i2cExt');
	config::save('subClass', 'i2cExt_bouton;i2cExt_relai', 'i2cExt');
	jeedom::getApiKey('i2cExt');
	if (config::byKey('api::i2cExt::mode') == '') {
		config::save('api::i2cExt::mode', 'enable');
	}
}

function i2cExt_update() {
	config::remove('listChildren', 'i2cExt');
	config::save('subClass', 'i2cExt_bouton;i2cExt_relai', 'i2cExt');
    $cron = cron::byClassAndFunction('i2cExt', 'pull');
	if ( ! is_object($cron)) {
        $cron = new cron();
        $cron->setClass('i2cExt');
        $cron->setFunction('pull');
        $cron->setEnable(1);
        $cron->setDeamon(0);
        $cron->setSchedule('* * * * *');
        $cron->save();
	}
    $cron = cron::byClassAndFunction('i2cExt', 'cron');
	if (is_object($cron)) {
		$cron->stop();
		$cron->remove();
	}
	foreach (eqLogic::byType('i2cExt_bouton') as $SubeqLogic) {
		$SubeqLogic->save();
	}
	foreach (eqLogic::byType('i2cExt_relai') as $SubeqLogic) {
		$SubeqLogic->save();
	}
	foreach (eqLogic::byType('i2cExt') as $eqLogic) {
		$eqLogic->save();
	}
	if ( config::byKey('api', 'i2cExt', '') == "" )
	{
		log::add('i2cExt', 'alert', __('Une clef API "i2cExt" a été configurée. Pensez à reconfigurer le push de chaque carte i2cExt', __FILE__));
	}
	jeedom::getApiKey('i2cExt');
	if (config::byKey('api::i2cExt::mode') == '') {
		config::save('api::i2cExt::mode', 'enable');
	}
}

function i2cExt_remove() {
    $cron = cron::byClassAndFunction('i2cExt', 'pull');
    if (is_object($cron)) {
		$cron->stop();
        $cron->remove();
    }
    $cron = cron::byClassAndFunction('i2cExt', 'cron');
    if (is_object($cron)) {
		$cron->stop();
        $cron->remove();
    }
	config::remove('listChildren', 'i2cExt');
	config::remove('subClass', 'i2cExt');
}
?>
