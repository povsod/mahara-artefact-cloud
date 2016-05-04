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

$owner = null;
if ($viewid > 0) {
    $view = new View($viewid);
    $owner = $view->get('owner');
    if (!can_view_view($viewid)) {
        throw new AccessDeniedException();
    }
}

// Get informatin/data about the file...
$file = PluginBlocktypeGoogledrive::get_file_info($id, $owner);

// Get/construct export file format options...
$exportoptions = array();
foreach ($file['export'] as $mimeType => $exportUrl) {
    $exportoptions = array_merge($exportoptions, array($mimeType => get_string($mimeType, 'blocktype.cloud/googledrive')));
}
asort($exportoptions);


if ($save) {
    // Save file to Mahara
    $saveform = pieform(array(
        'name'       => 'saveform',
        'plugintype' => 'artefact',
        'pluginname' => 'cloud',
        'template'   => 'exportsaveform.php',
        'templatedir' => pieform_template_dir('exportsaveform.php', 'artefact/cloud'),
        'elements'   => array(
            'fileid' => array(
                'type'  => 'hidden',
                'value' => $id,
            ),
            'fileformat' => array(
                'type' => 'radio', 
                'title' => get_string('selectfileformat', 'artefact.cloud'),
                'value' => null,
                'defaultvalue' => null,
                'options' => $exportoptions,
                'separator' => '<br />',
                'rules'   => array(
                    'required' => true
                )
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
                'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/googledrive/manage.php',
            )
        ),
    ));
    
    $smarty = smarty();
    $smarty->assign('PAGEHEADING', get_string('exporttomahara', 'artefact.cloud'));
    $smarty->assign('form', $saveform);
    $smarty->display('form.tpl');
}
elseif (!empty($exportoptions)) {
    // Export native GoogleDocs file to selected format
    // and than download it...
    $exportform = pieform(array(
        'name'       => 'exportform',
        'plugintype' => 'artefact',
        'pluginname' => 'cloud',
        'template'   => 'exportform.php',
        'templatedir' => pieform_template_dir('exportform.php', 'artefact/cloud'),
        'elements'   => array(
            'fileid' => array(
                'type'  => 'hidden',
                'value' => $id,
            ),
            'fileformat' => array(
                'type' => 'radio', 
                'title' => get_string('selectfileformat', 'artefact.cloud'),
                'value' => null,
                'defaultvalue' => null,
                'options' => $exportoptions,
                'separator' => '<br />',
                'rules'   => array(
                    'required' => true
                )
            ),
            'submit' => array(
                'type' => 'submitcancel',
                'value' => array(get_string('export', 'artefact.cloud'), get_string('cancel')),
                'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/googledrive/manage.php',
            )
        ),
    ));
    
    $smarty = smarty();
    $smarty->assign('PAGEHEADING', get_string('export', 'artefact.cloud'));
    $smarty->assign('exportform', $exportform);
    $smarty->display('artefact:cloud:export.tpl');
}
else {
    // No export options for native GoogleDocs file...
    $exportform = pieform(array(
        'name'       => 'exportform',
        'plugintype' => 'artefact',
        'pluginname' => 'cloud',
        'elements'   => array(
            'notice' => array(
                'type'  => 'html',
                'value' => get_string('exportnotpossible', 'blocktype.cloud/googledrive'),
            ),
            'cancel' => array(
                'type' => 'cancel',
                'value' => get_string('back'), //get_string('cancel'),
                'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/googledrive/manage.php',
            )
        ),
    ));
    
    $smarty = smarty();
    $smarty->assign('PAGEHEADING', get_string('export', 'artefact.cloud'));
    $smarty->assign('exportform', $exportform);
    $smarty->display('artefact:cloud:export.tpl');
}


function exportform_submit(Pieform $form, $values) {
    $file = PluginBlocktypeGoogledrive::get_file_info($values['fileid']);
    $content = PluginBlocktypeGoogledrive::export_file($file['export'][$values['fileformat']]);
    // Set correct extension...
    $extension = mime2extension($values['fileformat']);

    header('Pragma: no-cache');
    header('Content-disposition: attachment; filename="' . str_replace('"', '\"', $file['name']) . '.' . $extension . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-type: application/octet-stream');
    header('Refresh:0;url=' . get_config('wwwroot') . 'artefact/cloud/blocktype/googledrive/manage.php');
    echo $content;

    exit;
    // Redirect
    //redirect(get_config('wwwroot') . 'artefact/cloud/blocktype/googledrive/manage.php');
}


function saveform_submit(Pieform $form, $values) {
    global $USER;
    
    $file = PluginBlocktypeGoogledrive::get_file_info($values['fileid']);
    $content = PluginBlocktypeGoogledrive::export_file($file['export'][$values['fileformat']]);
    // Set correct extension...
    $extension = mime2extension($values['fileformat']);
    // Determine (by file extension) if file is an image file or not
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
        'title'        => $file['name'] . '.' . $extension,
        'author'       => $USER->get('id')
    );
    $artefactid = insert_record('artefact', $artefact, 'id', true);
    
    // Insert file data into 'artefact_file_files' table...
    $mimetypes = get_records_sql_assoc('SELECT m.description, m.mimetype FROM {artefact_file_mime_types} m ORDER BY description', array());
    $filetype = 'application/octet-stream';
    if (isset($mimetypes[$extension])) {
        $filetype = $mimetypes[$extension]->mimetype;
    }
    elseif ($extension == 'doc') {
        $filetype = 'application/msword';
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
    elseif ($extension == 'ppt') {
        $filetype = 'application/vnd.ms-powerpoint';
    }
    elseif ($extension == 'ppsx') {
        $filetype = 'application/vnd.openxmlformats-officedocument.presentationml.slideshow';
    }
    elseif ($extension == 'pptx') {
        $filetype = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
    }
    elseif ($extension == 'xls') {
        $filetype = 'application/vnd.ms-excel';
    }
    elseif ($extension == 'xlsx') {
        $filetype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }
    elseif ($extension == 'odt') {
        $filetype = 'application/vnd.oasis.opendocument.text';
    }
    elseif ($extension == 'ods') {
        $filetype = 'application/vnd.oasis.opendocument.spreadsheet';
    }
    elseif ($extension == 'odp') {
        $filetype = 'application/vnd.oasis.opendocument.presentation';
    }
    
    $fileartefact = (object) array(
        'artefact'     => $artefactid,
        'size'         => strlen($content), //$file['bytes'],
        'oldextension' => $extension,
        'fileid'       => $artefactid,
        'filetype'     => $filetype,
    );
    insert_record('artefact_file_files', $fileartefact);
    
    // Write file content to local Mahara file repository
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


function mime2extension($mimeType) {
    $extension = '';
    switch ($mimeType) {
        case 'application/msword':
            $extension = 'doc';
            break;
        case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            $extension = 'docx';
            break;
        case 'application/pdf':
            $extension = 'pdf';
            break;
        case 'application/rtf':
            $extension = 'rtf';
            break;
        case 'application/vnd.ms-excel':
            $extension = 'xls';
            break;
        case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
            $extension = 'xlsx';
            break;
        case 'application/vnd.ms-powerpoint':
            $extension = 'ppt';
            break;
        case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
            $extension = 'pptx';
            break;
        case 'application/vnd.oasis.opendocument.text':
        case 'application/x-vnd.oasis.opendocument.text':
            $extension = 'odt';
            break;
        case 'application/vnd.oasis.opendocument.spreadsheet':
        case 'application/x-vnd.oasis.opendocument.spreadsheet':
            $extension = 'ods';
            break;
        case 'application/vnd.oasis.opendocument.presentation':
        case 'application/x-vnd.oasis.opendocument.presentation':
            $extension = 'odp';
            break;
        case 'image/jpeg':
        case 'image/jpg':
        case 'application/jpg':
        case 'application/x-jpg':
        case 'image/vnd.swiftview-jpeg':
        case 'image/x-xbitmap':
            $extension = 'jpg';
            break;
        case 'image/png':
        case 'application/png':
        case 'application/x-png':
            $extension = 'png';
            break;
        case 'image/svg':
        case 'image/svg+xml':
        case 'image/svg-xml':
        case 'image/vnd.adobe.svg+xml':
        case 'text/xml-svg':
            $extension = 'svg';
            break;
        case 'text/html':
            $extension = 'html';
            break;
        case 'text/plain':
        case 'application/txt':
            $extension = 'txt';
            break;
    }
    return $extension;
}
