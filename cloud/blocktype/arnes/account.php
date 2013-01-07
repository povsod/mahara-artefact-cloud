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
 * @subpackage blocktype-arnes
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
define('TITLE', get_string('servicename', 'blocktype.cloud/arnes'));
require_once('lib.php');
require_once('lib/crypt.php');

$action = param_alpha('action', 'info');

switch ($action) {
    case 'login':
        $consentform = pieform(array(
            'name'   => 'consent',
            'autofocus'  => false,
            'elements' => array(
                'username' => array(
                    'type' => 'text',
                    'title' => get_string('username'),
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
        $smarty->assign('service', 'arnes');
        $smarty->assign('sitename', get_config('sitename'));
        $smarty->assign('servicename', get_string('servicename', 'blocktype.cloud/arnes'));
        $smarty->assign('form', $consentform);
        $smarty->display('artefact:cloud:consent.tpl');
        break;
    case 'logout':
        PluginBlocktypeArnes::revoke_access();
        PluginBlocktypeArnes::delete_token();
        redirect(get_config('wwwroot').'artefact/cloud');
        break;
    case 'test':
        //PluginBlocktypeArnes::test('www');
        //$info = PluginBlocktypeArnes::get_file_info('www/freemindbrowser.jar');
        //$info = PluginBlocktypeArnes::get_file_info('./www/Informacijska_znanja.html_files/icons/bell.png');
        //$info = PluginBlocktypeArnes::get_folder_info('./www');
        $info = PluginBlocktypeArnes::download_file('www/Informacijska_znanja.html_files/icons/bell.png');
        print_r($info);
        //redirect(get_config('wwwroot').'artefact/cloud');
        break;
    case 'disk':
        $info = PluginBlocktypeArnes::get_filelist();
        print_r($info);
        //redirect(get_config('wwwroot').'artefact/cloud');
        break;
    default:
        $account = PluginBlocktypeArnes::account_info();
        $smarty = smarty();
        //$smarty->assign('PAGEHEADING', TITLE);
        $smarty->assign('account', $account);
        $smarty->display('artefact:cloud:account.tpl');
}

function consent_submit(Pieform $form, $values) {
    global $USER, $SESSION;
    $username = $values['username'];
    $password = encrypt($values['password']);

    ArtefactTypeCloud::set_user_preferences('arnes', $USER->get('id'), array('user_name' => $username, 'user_pass' => $password));
    redirect(get_config('wwwroot') . 'artefact/cloud/');
}

?>