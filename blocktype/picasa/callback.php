<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-picasa
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2017 Gregor Anzelj, info@povsod.com
 *
 */

define('INTERNAL', 1);
define('NOCHECKPASSWORDCHANGE', 1);
require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');

require_once(get_config('docroot') . 'blocktype/lib.php');
require_once(get_config('docroot') . 'artefact/cloud/lib.php');
require_once('lib.php');

global $USER, $SESSION;
$code = param_variable('code', null);

// If there is a code (typically when signing user in) than proccess that code
// else (typically when signing user out & there is no code returned) do nothing...
if (!is_null($code)) {
    $prefs = PluginBlocktypePicasa::access_token($code);
    ArtefactTypeCloud::set_user_preferences('google', $USER->get('id'), $prefs);
    $SESSION->add_ok_msg(get_string('accesstokensaved', 'artefact.cloud'));
}
else {
    $SESSION->add_error_msg(get_string('accesstokensavefailed', 'artefact.cloud'));
}

// If user edited a page, then return to that page
$viewid = $USER->get_account_preference('lasteditedview');
if (isset($viewid) && $viewid > 0) {
    $USER->set_account_preference('lasteditedview', 0);
    redirect(get_config('wwwroot').'view/blocks.php?id='.$viewid);
}

// Otherwise return to Cloud plugin dashboard
redirect(get_config('wwwroot').'artefact/cloud');
