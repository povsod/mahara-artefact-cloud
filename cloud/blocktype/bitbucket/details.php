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
 * @subpackage blocktype-bitbucket
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

define('INTERNAL', 1);
define('MENUITEM', 'content/clouds');
define('SECTION_PLUGINTYPE', 'artefact');
define('SECTION_PLUGINNAME', 'cloud');
define('SECTION_PAGE', 'index');

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
require_once(get_config('libroot') . 'view.php');
require_once('lib.php');

$id     = param_variable('id', 0);
$type   = param_alpha('type', null); // Possible values: file, folder, album?
$viewid = param_integer('view', 0);

if ($viewid > 0) {
    $view = new View($viewid);
    if (!can_view_view($viewid)) {
        throw new AccessDeniedException();
    }
}

$data = array();
if ($type == 'folder') {
    $data = PluginBlocktypeBitbucket::get_folder_info($id);
} else {
    $data = PluginBlocktypeBitbucket::get_file_info($id);
    // If file has no description, than Bitbucket API returns
    // empty array instead of empty string. Fix that...
    if (is_array($data['description'])) {
        $data['description'] = '';
    }
}

if ($viewid > 0) {
    define('TITLE', $data['name'] . ' ' . get_string('in', 'view') . ' ' . $view->get('title'));
} else {
    define('TITLE', get_string('filedetails', 'artefact.cloud', $data['name']));
}


$smarty = smarty();

if (get_config('viewmicroheaders')) {
    $smarty->assign('microheaders', true);
    $smarty->assign('microheadertitle', $view->display_title(true, false));
}

$smarty->assign('id', $id);
$smarty->assign('type', $type);
$smarty->assign('viewid', $viewid);
if ($viewid > 0) {
    $viewtitle = $view->get('title');
} else {
    $viewtitle = get_string('filedetails', 'artefact.cloud', $data['name']);
}
$smarty->assign('viewtitle', $viewtitle);
$smarty->assign('data', $data);

if ($viewid > 0) {
    $viewowner = $view->get('owner');
    if ($viewowner) {
        $smarty->assign('ownerlink', 'user/view.php?id=' . $viewowner);
    }
    $smarty->assign('ownername', $view->formatted_owner());
}

$smarty->display('blocktype:bitbucket:details.tpl');

?>