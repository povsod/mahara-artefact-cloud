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

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
require_once(get_config('libroot') . 'view.php');
require_once('lib.php');

$id     = param_variable('id', 0);
$type   = param_alpha('type', null); // Possible values: item (file), collection (folder)
$viewid = param_integer('view', 0);

$ownerid = null;
if ($viewid > 0) {
    $view = new View($viewid);
    $ownerid = $view->get('owner');
    if (!can_view_view($viewid)) {
        throw new AccessDeniedException();
    }
}

$data = array();
if ($type == 'collection') {
    // It doesn't return correct data. Do we actually need this?
    $data = PluginBlocktypeZotero::get_folder_info($id, $ownerid);
} else {
    $data = PluginBlocktypeZotero::get_file_info($id, $ownerid);
}

if ($viewid > 0) {
    define('TITLE', $data['name'] . ' ' . get_string('in', 'view') . ' ' . $view->get('title'));
} else {
    define('TITLE', get_string('filedetails', 'artefact.cloud', $data['name']));
}


$smarty = smarty();

$smarty->assign('id', $id);
$smarty->assign('type', $type);
$smarty->assign('viewid', $viewid);
if ($viewid > 0) {
    $viewtitle = $view->get('title');
} else {
    $viewtitle = get_string('filedetails', 'artefact.cloud', $data['name']);
}
$smarty->assign('data', $data);

if ($viewid > 0) {
    $viewowner = $view->get('owner');
    if ($viewowner) {
        $smarty->assign('ownerlink', 'user/view.php?id=' . $viewowner);
    }
    $smarty->assign('ownername', $view->formatted_owner());
}

$smarty->display('blocktype:zotero:details.tpl');

?>