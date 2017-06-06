<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-dropbox
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2017 Gregor Anzelj, info@povsod.com
 *
 */

define('INTERNAL', 1);
define('PUBLIC', 1);

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
require_once(get_config('libroot') . 'view.php');
safe_require('artefact', 'cloud');
safe_require('blocktype', 'cloud/dropbox');

$id = param_variable('id', 0); // Possible values: numerical (= folder id), 0 (= root folder), parent (= get parent folder id from path)
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
                'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/dropbox/manage.php',
            )
        ),
    ));
    
    $smarty = smarty();
    //$smarty->assign('SERVICE', 'dropbox');
    $smarty->assign('PAGEHEADING', get_string('savetomahara', 'artefact.cloud'));
    $smarty->assign('form', $saveform);
    $smarty->display('form.tpl');
}
else {
    // Download file
    $file = PluginBlocktypeDropbox::get_file_info($id, $owner);
    $content = PluginBlocktypeDropbox::download_file($id, $owner);
    
    header('Pragma: no-cache');
    header('Content-disposition: attachment; filename="' . str_replace('"', '\"', $file['name']) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-type: application/octet-stream');
    echo $content;
}

function saveform_submit(Pieform $form, $values) {
    PluginBlocktypeDropbox::download_to_artefact(
        $values['fileid'],
        $values['folderid']
    );

    redirect(get_config('wwwroot') . 'artefact/cloud/blocktype/dropbox/manage.php');
}
