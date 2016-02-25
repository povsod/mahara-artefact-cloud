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
define('PUBLIC', 1);
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
    $prefs = PluginBlocktypeDropbox::access_token($code);
    ArtefactTypeCloud::set_user_preferences('dropbox', $USER->get('id'), $prefs);
    $SESSION->add_ok_msg(get_string('accesstokensaved', 'blocktype.cloud/dropbox'));
} else {
    $SESSION->add_error_msg(get_string('accesstokensavefailed', 'blocktype.cloud/dropbox'));
}

redirect(get_config('wwwroot').'artefact/cloud');
