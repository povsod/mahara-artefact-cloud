<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-box
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
define('TITLE', get_string('servicename', 'blocktype.cloud/box'));
require_once('lib.php');

$action = param_alpha('action', null);
$viewid = param_integer('view', 0);

if ($viewid > 0) {
    $USER->set_account_preference('lasteditedview', $viewid);
}
else {
    $USER->set_account_preference('lasteditedview', 0);
}

switch ($action) {
    case 'login':
        PluginBlocktypeBox::request_token();
        break;
    case 'logout':
        PluginBlocktypeBox::revoke_access();
        PluginBlocktypeBox::delete_token();
        redirect(get_config('wwwroot').'artefact/cloud');
        break;
    default:
        throw new ParameterException("Parameter for login to or logout from Box is missing.");
}
