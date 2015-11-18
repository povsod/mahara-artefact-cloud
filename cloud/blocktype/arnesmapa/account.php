<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-arnesmapa
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2014 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

define('INTERNAL', 1);
define('MENUITEM', 'content/clouds');
define('SECTION_PLUGINTYPE', 'artefact');
define('SECTION_PLUGINNAME', 'cloud');
define('SECTION_PAGE', 'index');

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
define('TITLE', get_string('servicename', 'blocktype.cloud/arnesmapa'));
require_once('lib.php');

$action = param_alpha('action', 'info');


switch ($action) {
    case 'login':
        $link = 'https://mapa.arnes.si/index.php/settings/personal';
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
        $smarty->assign('service', 'arnesmapa');
        $smarty->assign('sitename', get_config('sitename'));
        $smarty->assign('servicename', get_string('servicename', 'blocktype.cloud/arnesmapa'));
        $smarty->assign('instructions', get_string('AAIlogin', 'blocktype.cloud/arnesmapa', '<a href="'.$link.'" target="_blank">', '</a>'));
        $smarty->assign('form', $consentform);
        $smarty->display('artefact:cloud:consent.tpl');
        break;
    case 'logout':
        PluginBlocktypeArnesmapa::revoke_access();
        PluginBlocktypeArnesmapa::delete_token();
        redirect(get_config('wwwroot').'artefact/cloud');
        break;
    default:
        $account = PluginBlocktypeArnesmapa::account_info();
        $smarty = smarty();
        //$smarty->assign('PAGEHEADING', TITLE);
        $smarty->assign('account', $account);
        $webdavurl = get_config_plugin('blocktype', 'arnesmapa', 'webdavurl');
        if (strpos($webdavurl, 'arnes') !== false) {
            $arnes = true;
        } else {
            $arnes = false;
        }
        $smarty->assign('arnes', $arnes);
        $smarty->display('artefact:cloud:account.tpl');
}

function consent_submit(Pieform $form, $values) {
    global $USER;
    $token = base64_encode($values['username'].':'.$values['password']);
    ArtefactTypeCloud::set_user_preferences('arnesmapa', $USER->get('id'), array('token' => $token));
    redirect(get_config('wwwroot') . 'artefact/cloud/');
}


?>