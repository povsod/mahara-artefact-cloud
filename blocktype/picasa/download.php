<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-picasa
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2016 Gregor Anzelj, info@povsod.com
 *
 */

define('INTERNAL', 1);
define('PUBLIC', 1);

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
safe_require('artefact', 'cloud');
safe_require('blocktype', 'cloud/picasa');

$id   = param_variable('id', 0); // Possible values: numerical (= folder id), 0 (= root folder), parent (= get parent folder id from path)
$save = param_integer('save', 0); // Indicate to download file or save it (save=1) to local Mahara file repository...
$viewid = param_integer('view', null);

$owner = null;
if ($viewid > 0) {
    $view = new View($viewid);
    $owner = $view->get('owner');
    if (!can_view_view($viewid)) {
        throw new AccessDeniedException();
    }
}


if ($save) {
    // Save file to Mahara
    $saveform = pieform(array(
        'name'       => 'saveform',
        'plugintype' => 'artefact',
        'pluginname' => 'cloud',
        'template'   => 'saveform.php',
        'templatedir' => pieform_template_dir('saveform.php', 'artefact/cloud'),
        'elements'   => array(
            'fileid' => array(
                'type'  => 'hidden',
                'value' => $id,
            ),
            'folderid' => array(
                'type'    => 'select',
                'title'   => get_string('savetofolder', 'artefact.cloud'),
                'options' => get_foldertree_options(),
                //'size'    => 8,                
                'rules'   => array(
                    'required' => true
                ),
                'collapseifoneoption' => false,
            ),
            'submit' => array(
                'type' => 'submitcancel',
                'value' => array(get_string('save'), get_string('cancel')),
                'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/picasa/manage.php',
            )
        ),
    ));
    
    $smarty = smarty();
    $smarty->assign('PAGEHEADING', get_string('savetomahara', 'artefact.cloud'));
    $smarty->assign('form', $saveform);
    $smarty->display('form.tpl');
}
else {
    // Download file
    $ownerid = null;
    if ($viewid > 0) {
        $view = new View($viewid);
        $ownerid = $view->get('owner');
    }
    else {
        $ownerid = null;
    }
    $file = PluginBlocktypePicasa::get_file_info($id, $ownerid);
    $content = PluginBlocktypePicasa::download_file($id, $ownerid);
    
    header('Pragma: no-cache');
    header('Content-disposition: attachment; filename="' . str_replace('"', '\"', $file['name']) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-type: application/octet-stream');
    echo $content;
}

function saveform_submit(Pieform $form, $values) {
    PluginBlocktypePicasa::download_to_artefact(
        $values['fileid'],
        $values['folderid']
    );

    redirect(get_config('wwwroot') . 'artefact/cloud/blocktype/picasa/manage.php');
}
