<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2012 Catalyst IT Ltd and others; see:
 *                         http://wiki.mahara.org/Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mahara
 * @subpackage blocktype-sugarsync
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

define('INTERNAL', 1);
define('MENUITEM', 'content/clouds');
define('SECTION_PLUGINTYPE', 'artefact');
define('SECTION_PLUGINNAME', 'cloud');
define('SECTION_PAGE', 'index');

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
define('TITLE', get_string('servicename', 'blocktype.cloud/sugarsync'));
require_once('lib.php');

$action = param_alpha('action', 'info');


switch ($action) {
	case 'login':
		$consentform = pieform(array(
			'name'   => 'consent',
			'autofocus'  => false,
			'elements' => array(
				'username' => array(
					'type' => 'text',
					'title' => get_string('email'),
				),
				'password' => array(
					'type' => 'password',
					'title' => get_string('password'),
				),
				'submitcancel' => array(
					'type' => 'submitcancel',
					'value' => array(get_string('allow', 'artefact.cloud'), get_string('deny', 'artefact.cloud')),
					'goto' => get_config('wwwroot') . 'artefact/cloud/',
				),
			),
		));
		$smarty = smarty();
		$smarty->assign('service', 'sugarsync');
		$smarty->assign('sitename', get_config('sitename'));
		$smarty->assign('servicename', get_string('servicename', 'blocktype.cloud/sugarsync'));
		$smarty->assign('form', $consentform);
		$smarty->display('artefact:cloud:consent.tpl');
		break;
	case 'logout':
		PluginBlocktypeSugarsync::revoke_access();
		PluginBlocktypeSugarsync::delete_token();
		redirect(get_config('wwwroot').'artefact/cloud');
		break;
	default:
		$account = PluginBlocktypeSugarsync::account_info();
		$smarty = smarty();
		//$smarty->assign('PAGEHEADING', TITLE);
		$smarty->assign('account', $account);
		$smarty->display('artefact:cloud:account.tpl');
}

function consent_submit(Pieform $form, $values) {
	global $USER, $SESSION;
	$username = $values['username'];
	$password = $values['password'];

	$cloud    = PluginBlocktypeSugarsync::cloud_info();
	$consumer = PluginBlocktypeSugarsync::consumer_tokens();
	$appid  = $consumer['appid'];
	$key    = $consumer['key'];
	$secret = $consumer['secret'];
	
	$request_body = <<< XML
<?xml version="1.0" encoding="UTF-8" ?>
<appAuthorization>
 <username>$username</username>
 <password>$password</password>
 <application>$appid</application>
 <accessKeyId>$key</accessKeyId>
 <privateAccessKey>$secret</privateAccessKey>
</appAuthorization>
XML;

	if (!empty($consumer['key']) && !empty($consumer['secret'])) {
		$request = SugarSyncAPI::get_refresh_token('POST', $cloud, $consumer, $request_body);
		if (!empty($request)) {
			list($info, $headers, $body, $refresh_token) = $request;
			if ($info['http_code'] == 201 /* HTTP/1.1 201 Created */ && !empty($refresh_token)) {
				// Store refresh_token, we'll need it later to get access_token...
				ArtefactTypeCloud::set_user_preferences('sugarsync', $USER->get('id'), array('refresh_token' => $refresh_token));
				redirect(get_config('wwwroot') . 'artefact/cloud/');
			} else {
				$SESSION->add_error_msg(get_string('refreshtokennotreturned', 'blocktype.cloud/sugarsync'));
			}
		}
	} else {
		throw new ConfigException('Can\'t find SugarSync consumer key and/or consumer secret.');
	}
}


?>