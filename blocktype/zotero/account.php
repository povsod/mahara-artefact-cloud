<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-zotero
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
        $SESSION->add_ok_msg(get_string('accessrevoked', 'artefact.cloud'));
        redirect(get_config('wwwroot').'artefact/cloud');
        break;
    default:
        $account = PluginBlocktypeZotero::account_info();
        $smarty = smarty();
        $smarty->assign('account', $account);
        $smarty->display('artefact:cloud:account.tpl');
}
