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
 * @subpackage blocktype-dropbox
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

define('INTERNAL', 1);
//define('JSON', 1);

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
safe_require('artefact', 'cloud');
safe_require('blocktype', 'cloud/dropbox');

$id = param_variable('id', 0); // Possible values: numerical (= folder id), 0 (= root folder), parent (= get parent folder id from path)
$save = param_integer('save', 0); // Indicate to download file or save it (save=1) to local Mahara file repository...


if ($save) {
    // Save file to Mahara
    $saveform = pieform(array(
        'name'       => 'saveform',
        'renderer'   => 'maharatable',
        'plugintype' => 'artefact',
        'pluginname' => 'cloud',
        'configdirs' => array(get_config('libroot') . 'form/', get_config('docroot') . 'artefact/cloud/form/'),
        'elements'   => array(
            'fileid' => array(
                'type'  => 'hidden',
                'value' => $id,
            ),
            'folderid' => array(
                'type'    => 'css_select',
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
                'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/dropbox/manage.php',
            )
        ),
    ));
    
    $smarty = smarty();
    //$smarty->assign('SERVICE', 'dropbox');
    $smarty->assign('PAGEHEADING', get_string('savetomahara', 'artefact.cloud'));
    $smarty->assign('saveform', $saveform);
    $smarty->display('blocktype:dropbox:save.tpl');
} else {
    // Download file
    $file = PluginBlocktypeDropbox::get_file_info($id);
    $content = PluginBlocktypeDropbox::download_file($id);
    
    header('Pragma: no-cache');
    header('Content-disposition: attachment; filename="' . $file['name'] . '"');
    header('Content-Transfer-Encoding: binary'); 
    header('Content-type: application/octet-stream');
    echo $content;
}

function saveform_submit(Pieform $form, $values) {
    global $USER;
    
    $file = PluginBlocktypeDropbox::get_file_info($values['fileid']);
    // Determine (by file extension) if file is an image file or not
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (in_array($extension, array('bmp', 'gif', 'jpg', 'jpeg', 'png'))) {
        $image = true;
    } else {
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
    elseif ($extension == 'jpg') {
        $filetype = 'image/jpeg';
    }
    elseif ($extension == 'pps') {
        $filetype = 'application/vnd.ms-powerpoint';
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
    $content = PluginBlocktypeDropbox::download_file($file['id']); 
    // $content[0] = content metadata
    // $content[1] = HTTP request header
    // $content[2] = file contents
    if (!file_exists(get_config('dataroot') . 'artefact/file/originals/' . $artefactid)) {
        mkdir(get_config('dataroot') . 'artefact/file/originals/' . $artefactid, 0777);
    }
    $localfile = get_config('dataroot') . 'artefact/file/originals/' . $artefactid . '/' . $artefactid;
    file_put_contents($localfile, $content[2]);
    
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
    redirect(get_config('wwwroot') . 'artefact/cloud/blocktype/dropbox/manage.php');
}

?>
