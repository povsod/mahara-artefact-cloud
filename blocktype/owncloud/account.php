<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-owncloud
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2016 Gregor Anzelj, info@povsod.com
 *
 */

define('INTERNAL', 1);
define('MENUITEM', 'content/clouds');
define('SECTION_PLUGINTYPE', 'artefact');
define('SECTION_PLUGINNAME', 'cloud');
define('SECTION_PAGE', 'index');

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
define('TITLE', get_string('servicename', 'blocktype.cloud/owncloud'));
require_once('lib.php');

$action = param_alpha('action', 'info');
$viewid = param_integer('view', 0);

$sub = get_config_plugin('blocktype', 'owncloud', 'subservice');
$subservice = (isset($sub) && !empty($sub) ? $sub : false);


switch ($action) {
    case 'login':
        $url = parse_url(get_config_plugin('blocktype', 'owncloud', 'webdavurl'));
        $link = $url['scheme'].'://'.$url['host'].'/index.php/settings/personal';
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
                'viewid' => array(
                    'type' => 'hidden',
                    'value' => $viewid,
                ),
                'submitcancel' => array(
                    'type' => 'submitcancel',
                    'value' => array(get_string('allow', 'artefact.cloud'), get_string('deny', 'artefact.cloud')),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/',
                ),
            ),
        ));
        $smarty = smarty();
        $smarty->assign('SERVICE', 'owncloud');
        $smarty->assign('SUBSERVICE', $subservice);
        $smarty->assign('sitename', get_config('sitename'));
        $smarty->assign('servicename', get_string('servicename', 'blocktype.cloud/owncloud'));
        $smarty->assign('instructions', get_string('AAIlogin', 'blocktype.cloud/owncloud', '<a href="'.$link.'" target="_blank">', '</a>'));
        $smarty->assign('form', $consentform);
        $smarty->display('artefact:cloud:consent.tpl');
        break;
    case 'logout':
        PluginBlocktypeOwncloud::revoke_access();
        PluginBlocktypeOwncloud::delete_token();
        redirect(get_config('wwwroot').'artefact/cloud');
        break;
    default:
        throw new ParameterException("Parameter for login to or logout from ownCloud is missing.");

}

function consent_submit(Pieform $form, $values) {
    global $USER;
    $token = base64_encode($values['username'].':'.$values['password']);
    $viewid = $values['viewid'];
    ArtefactTypeCloud::set_user_preferences('owncloud', $USER->get('id'), array('token' => $token));

    if ($viewid > 0) {
        redirect(get_config('wwwroot').'view/blocks.php?id='.$viewid);
    }
    else {
        redirect(get_config('wwwroot') . 'artefact/cloud/');
    }
}
