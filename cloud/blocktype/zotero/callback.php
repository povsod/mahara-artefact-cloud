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
define('PUBLIC', 1);
define('NOCHECKPASSWORDCHANGE', 1);
require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');

require_once(get_config('docroot') . 'blocktype/lib.php');
require_once(get_config('docroot') . 'artefact/cloud/lib.php');
require_once('lib.php');

global $USER;
$oauth_token = param_alphanum('oauth_token', null);
$oauth_verifier = param_alphanum('oauth_verifier', null);

// Get previously stored request_token (oauth_token) and request_token_secret (outh_token_secret)
$token = ArtefactTypeCloud::get_user_preferences('zotero', $USER->get('id'));
// Zotero uses oauth_verifier, so...
$token = array_merge($token, array('oauth_verifier' => $oauth_verifier));

if (isset($oauth_token) && $oauth_token == $token['oauth_token']) {
    PluginBlocktypeZotero::access_token($token);
    $SESSION->add_ok_msg(get_string('accesstokensaved', 'artefact.cloud'));
}
else {
    $SESSION->add_error_msg(get_string('accesstokensavefailed', 'artefact.cloud'));
}

// If user edited a page, then return to that page
$viewid = $USER->get_account_preference('lasteditedview');
if (isset($viewid) && !empty($viewid)) {
    $USER->set_account_preference('lasteditedview', null);
    redirect(get_config('wwwroot').'view/blocks.php?id='.$viewid);
}

// Otherwise return to Cloud plugin dashboard
redirect(get_config('wwwroot').'artefact/cloud');

