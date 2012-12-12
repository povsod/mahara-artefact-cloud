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
 * @subpackage blocktype-box
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
$ticket     = param_alphanum('ticket', null);
$auth_token = param_alphanum('auth_token', null);


// Store access_token (auth_token). We'll need it later...
if (!empty($auth_token)) {
    $prefs = array(
        'ticket'     => $ticket,
        'auth_token' => $auth_token,
    );
    ArtefactTypeCloud::set_user_preferences('box', $USER->get('id'), $prefs);
    $SESSION->add_ok_msg(get_string('accesstokensaved', 'blocktype.cloud/box'));
} else {
    $SESSION->add_error_msg(get_string('accesstokensavefailed', 'blocktype.cloud/box'));
}

redirect(get_config('wwwroot').'artefact/cloud');

?>
