<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-dropbox
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2015 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

define('INTERNAL', 1);
define('MENUITEM', 'content/clouds');
define('SECTION_PLUGINTYPE', 'artefact');
define('SECTION_PLUGINNAME', 'cloud');
define('SECTION_PAGE', 'index');

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
define('TITLE', get_string('servicename', 'blocktype.cloud/dropbox'));
require_once('lib.php');

$action = param_alpha('action', 'info');

switch ($action) {
    case 'login':
        PluginBlocktypeDropbox::request_token();
        break;
    case 'logout':
        PluginBlocktypeDropbox::revoke_access();
        PluginBlocktypeDropbox::delete_token();
        redirect(get_config('wwwroot').'artefact/cloud');
        break;
    default:
        $account = PluginBlocktypeDropbox::account_info();
        $smarty = smarty();
        //$smarty->assign('PAGEHEADING', TITLE);
        $smarty->assign('account', $account);
        $smarty->display('artefact:cloud:account.tpl');
}

?>