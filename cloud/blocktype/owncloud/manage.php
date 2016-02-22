<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-owncloud
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2016 Gregor Anzelj, info@povsod.com
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

$sub = get_config_plugin('blocktype', 'owncloud', 'subservice');
$subservice = (isset($sub) && !empty($sub) ? $sub : 'images');


$manageform = pieform(array(
    'name'       => 'manageform',
    'plugintype' => 'artefact',
    'pluginname' => 'cloud',
    'configdirs' => array(get_config('libroot') . 'form/', get_config('docroot') . 'artefact/cloud/form/'),
    'elements'   => array(
        'manage' => array(
            'type'     => 'datatables',
            'title'    => '', //get_string('selectfiles','blocktype.cloud/owncloud'),
            'service'  => 'owncloud',
            'block'    => 0,
            'fullpath' => null,
            'options'  => array(
                'manageButtons'  => true,
                'showFolders'    => true,
                'showFiles'      => true,
                'selectFolders'  => false,
                'selectFiles'    => false,
                'selectMultiple' => false
            ),
        ),
    ),
));

$smarty = smarty();
$smarty->assign('SERVICE', 'owncloud');
$smarty->assign('SUBSERVICE', $subservice);
$smarty->assign('manageform', $manageform);
$webdavurl = get_config_plugin('blocktype', 'owncloud', 'webdavurl');
if (strpos($webdavurl, 'arnes') !== false) {
    $arnes = true;
} else {
    $arnes = false;
}
$smarty->assign('arnes', $arnes);
$smarty->display('artefact:cloud:manage.tpl');
