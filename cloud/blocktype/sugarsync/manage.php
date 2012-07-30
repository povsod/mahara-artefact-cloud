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
 * @subpackage blocktype-sugarsync
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


$manageform = pieform(array(
	'name'       => 'manageform',
	'renderer'   => 'maharatable',
    'plugintype' => 'artefact',
    'pluginname' => 'cloud',
    'configdirs' => array(get_config('libroot') . 'form/', get_config('docroot') . 'artefact/cloud/form/'),
	'elements'   => array(
		'manage' => array(
			'type'     => 'datatables',
			'title'    => '', //get_string('selectfiles','blocktype.cloud/sugarsync'),
			'service'  => 'sugarsync',
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
$smarty->assign('SERVICE', 'sugarsync');
$smarty->assign('manageform', $manageform);
$smarty->display('blocktype:sugarsync:manage.tpl');

?>