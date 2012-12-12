<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2012 Catalyst IT Ltd and others; see:
 *                         http://wiki.mahara.org/Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mahara
 * @subpackage blocktype-dropbox
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
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
//$user_id     = param_integer('uid', 0);
$oauth_token = param_alphanum('oauth_token', null);

// Get previously stored request_token (oauth_token) and request_token_secret (outh_token_secret)
$token = ArtefactTypeCloud::get_user_preferences('dropbox', $USER->get('id'));
// Dropbox doesn't use oauth_verifier, but still...
$token = array_merge($token, array('oauth_verifier' => null));

if (isset($oauth_token) && $oauth_token == $token['oauth_token']) {
    PluginBlocktypeDropbox::access_token($token);
    $SESSION->add_ok_msg(get_string('accesstokensaved', 'blocktype.cloud/dropbox'));
} else {
    $SESSION->add_error_msg(get_string('accesstokensavefailed', 'blocktype.cloud/dropbox'));
}

redirect(get_config('wwwroot').'artefact/cloud');

?>
