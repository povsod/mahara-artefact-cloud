<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-zotero
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
define('TITLE', get_string('servicename', 'blocktype.cloud/zotero'));
require_once('lib.php');

$action = param_alpha('action', 'info');
$viewid = param_integer('view', 0);

if ($viewid > 0) {
    $USER->set_account_preference('lasteditedview', $viewid);
}
else {
    $USER->set_account_preference('lasteditedview', 0);
}

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
        throw new ParameterException("Parameter for login to or logout from Zotero is missing.");
}
