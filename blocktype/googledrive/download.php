<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-googledrive
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2016 Gregor Anzelj, info@povsod.com
 *
 */

define('INTERNAL', 1);
define('PUBLIC', 1);

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
safe_require('artefact', 'cloud');
safe_require('blocktype', 'cloud/googledrive');

$id   = param_variable('id', 0); // Possible values: numerical (= folder id), 0 (= root folder), parent (= get parent folder id from path)
$save = param_integer('save', 0); // Indicate to download file or save it (save=1) to local Mahara file repository...
$viewid = param_integer('view', null);

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
                )
            ),
            'submit' => array(
                'type' => 'submitcancel',
                'value' => array(get_string('save'), get_string('cancel')),
                'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/googledrive/manage.php',
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
    $file = PluginBlocktypeGoogledrive::get_file_info($id, $ownerid);
    $content = PluginBlocktypeGoogledrive::download_file($id, $ownerid);
    
    header('Pragma: no-cache');
    header('Content-disposition: attachment; filename="' . str_replace('"', '\"', $file['name']) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-type: application/octet-stream');
    echo $content;
}

function saveform_submit(Pieform $form, $values) {
    global $USER;
    
    $file = PluginBlocktypeGoogledrive::get_file_info($values['fileid']);
    // Determine (by file extension) if file is an image file or not
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (in_array($extension, array('bmp', 'gif', 'jpg', 'jpeg', 'png'))) {
        $image = true;
    }
    else {
        $image = false;
    }
    
    // Insert file data into 'artefact' table...
    $time = db_format_timestamp(time());
    $artefact = (object) array(
        'artefacttype' => ($image ? 'image' : 'file'),
        'parent'       => ($values['folderid'] > 0 ? $values['folderid'] : null),
        'owner'        => $USER->get('id'),
        'ctime'        => $time,
        'mtime'        => $time,
        'atime'        => $time,
        'title'        => $file['name'],
        'author'       => $USER->get('id')
    );
    $artefactid = insert_record('artefact', $artefact, 'id', true);
    
    // Insert file data into 'artefact_file_files' table...
    $mimetypes = get_records_sql_assoc('SELECT m.description, m.mimetype FROM {artefact_file_mime_types} m ORDER BY description', array());
    $filetype = 'application/octet-stream';
    if (isset($mimetypes[$extension])) {
        $filetype = $mimetypes[$extension]->mimetype;
    }
    elseif ($extension == 'docx') {
        $filetype = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }
    elseif ($extension == 'jpg') {
        $filetype = 'image/jpeg';
    }
    elseif ($extension == 'pps') {
        $filetype = 'application/vnd.ms-powerpoint';
    }
    elseif ($extension == 'ppsx') {
        $filetype = 'application/vnd.openxmlformats-officedocument.presentationml.slideshow';
    }
    elseif ($extension == 'pptx') {
        $filetype = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
    }
    elseif ($extension == 'xlsx') {
        $filetype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }
    
    $fileartefact = (object) array(
        'artefact'     => $artefactid,
        'size'         => $file['bytes'],
        'oldextension' => $extension,
        'fileid'       => $artefactid,
        'filetype'     => $filetype,
    );
    insert_record('artefact_file_files', $fileartefact);
    
    // Write file content to local Mahara file repository
    $content = PluginBlocktypeGoogledrive::download_file($file['id']); 
    if (!file_exists(get_config('dataroot') . 'artefact/file/originals/' . $artefactid)) {
        mkdir(get_config('dataroot') . 'artefact/file/originals/' . $artefactid, 0777);
    }
    $localfile = get_config('dataroot') . 'artefact/file/originals/' . $artefactid . '/' . $artefactid;
    file_put_contents($localfile, $content);
    
    // If file is an image file, than
    // insert image data into 'artefact_file_image' table...
    if ($image) {
        list($width, $height, $type, $attr) = getimagesize($localfile);
        $imgartefact = (object) array(
            'artefact' => $artefactid,
            'width'    => $width,
            'height'   => $height,
        );
        insert_record('artefact_file_image', $imgartefact);
    }

    // Redirect
    redirect(get_config('wwwroot') . 'artefact/cloud/blocktype/googledrive/manage.php');
}
