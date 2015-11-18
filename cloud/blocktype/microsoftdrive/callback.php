<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-microsoftdrive
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2015 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

define('INTERNAL', 1);
define('PUBLIC', 1);
define('NOCHECKPASSWORDCHANGE', 1);
require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');

require_once(get_config('docroot') . 'blocktype/lib.php');
require_once(get_config('docroot') . 'artefact/cloud/lib.php');
require_once('lib.php');

global $USER;
$code = param_variable('code', null);
//print_r($code);

// If there is a code (typically when signing user in) than proccess that code
// else (typically when signing user out & there is no code returned) do nothing...
if (!is_null($code)) {
    $prefs = PluginBlocktypeMicrosoftdrive::access_token($code);
    ArtefactTypeCloud::set_user_preferences('microsoftdrive', $USER->get('id'), $prefs);
    $SESSION->add_ok_msg(get_string('accesstokensaved', 'blocktype.cloud/microsoftdrive'));
} else {
    $SESSION->add_error_msg(get_string('accesstokensavefailed', 'blocktype.cloud/microsoftdrive'));
}

redirect(get_config('wwwroot').'artefact/cloud');

?>
