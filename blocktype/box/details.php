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
define('PUBLIC', 1);

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
require_once(get_config('libroot') . 'view.php');
require_once('lib.php');

$id     = param_variable('id', 0);
$type   = param_alpha('type', null); // Possible values: file, folder, album?
$viewid = param_integer('view', 0);

$ownerid = null;
if ($viewid > 0) {
    $view = new View($viewid);
    $ownerid = $view->get('owner');
    if (!can_view_view($viewid)) {
        throw new AccessDeniedException();
    }
}
else {
    // If no view id was provided, we'll use the current user's token.
    // If you're logged out, obviously you have no token, so therefore
    // we shouldn't even try.
    if (!$USER->get('id')) {
        throw new AccessDeniedException();
    }
}

$data = array();
if ($type == 'folder') {
    $data = PluginBlocktypeBox::get_folder_info($id, $ownerid);
} else {
    $data = PluginBlocktypeBox::get_file_info($id, $ownerid);
    // If file has no description, than Box API returns
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


$smarty = smarty(
    array(),
    array(),
    array(),
    array('sidebars' => false)
);

$smarty->assign('SERVICE', 'box');
$smarty->assign('id', $id);
$smarty->assign('type', $type);
$smarty->assign('viewid', $viewid);

if ($viewid > 0) {
    $viewtitle = $view->get('title');
}
else {
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

$smarty->display('artefact:cloud:details.tpl');
