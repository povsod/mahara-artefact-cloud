<?php
/**
 *
 * @package    mahara
 * @subpackage artefact-cloud
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2017 Gregor Anzelj, info@povsod.com
 *
 */

define('INTERNAL', true);
define('MENUITEM', 'content/clouds');
define('SECTION_PLUGINTYPE', 'artefact');
define('SECTION_PLUGINNAME', 'cloud');
define('SECTION_PAGE', 'index');

require_once(dirname(dirname(dirname(__FILE__))) . '/init.php');
define('TITLE', get_string('clouds', 'artefact.cloud'));
safe_require('artefact', 'cloud');

global $USER;

$data = array();
$clouds = ArtefactTypeCloud::get_user_services($USER->get('id'));
foreach ($clouds as $cloud) {
    // Usually this file should exist (but if it happens to be deleted, don't try opening it)...
    if (file_exists(get_config('docroot') . 'artefact/cloud/blocktype/' . $cloud->title . '/lib.php')) {
        require_once('blocktype/' . $cloud->title . '/lib.php');
        $data[] = call_static_method(generate_class_name('blocktype', $cloud->title), 'service_list');
    }
}

$smarty = smarty();
setpageicon($smarty, 'icon icon-cloud');
$smarty->assign('PAGEHEADING', TITLE);
$smarty->assign('data', $data);
$smarty->display('artefact:cloud:index.tpl');
