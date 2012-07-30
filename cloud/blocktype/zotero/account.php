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
 * @subpackage blocktype-zotero
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
define('TITLE', get_string('servicename', 'blocktype.cloud/zotero'));
require_once('lib.php');

$action = param_alpha('action', 'info');

switch ($action) {
	case 'login':
		PluginBlocktypeZotero::request_token();
		break;
	case 'logout':
		PluginBlocktypeZotero::revoke_access();
		PluginBlocktypeZotero::delete_token();
		redirect(get_config('wwwroot').'artefact/cloud');
		break;
	case 'test':
		//PluginBlocktypeZotero::test();
		//$test = PluginBlocktypeZotero::get_filelist('II7IVJ23');
		$test = PluginBlocktypeZotero::get_folder_content('0', '10100');
		log_debug($test);
		break;
	default:
		$account = PluginBlocktypeZotero::account_info();
		$smarty = smarty();
		//$smarty->assign('PAGEHEADING', TITLE);
		$smarty->assign('account', $account);
		$smarty->display('artefact:cloud:account.tpl');
}

?>