<?php
/**
 *
 * @package    mahara
 * @subpackage artefact-cloud
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2016 Gregor Anzelj, info@povsod.com
 *
 */

defined('INTERNAL') || die();

function xmldb_artefact_cloud_upgrade($oldversion=0) {

    // Check if 'htdocs/view/blocks.php' core Mahara file includes
    // 'jquery.dataTables.js' javascript library on that page.
    // This library is needed when users want to add blocks from 'cloud'
    // plugin. If the inclusion of library is not supported that change
    // above file to force inclusion.
    // This should be checked upon each upgrade!
    global $SESSION;
    $fname = get_config('docroot') . 'view/blocks.php';
    $info  = get_config('wwwroot') . 'artefact/cloud/INSTALL.txt';

    if (!$fhandle = fopen($fname, 'r')) {
        $SESSION->add_error_msg('Cannot open file "' . $fname . '". Please <a href="' . $info . '">update this file</a> manually.', false);
    }
    $content = fread($fhandle, filesize($fname));

    if (strpos($content, 'artefact/cloud/lib/datatables/js/jquery.dataTables.min.js') === false) {
        $newtext = 
            '
            $javascript = array_merge($javascript, $blocktype_js[\'jsfiles\']);

            if (class_exists(\'PluginArtefactCloud\')) {
                $blocktype_cloud_js = array(\'artefact/cloud/lib/datatables/js/jquery.dataTables.min.js\',
                                            \'artefact/cloud/lib/datatables/js/dataTables.bootstrap.min.js\');
                $javascript = array_merge($javascript, $blocktype_cloud_js);
            }';
        $replacetext = '$javascript = array_merge($javascript, $blocktype_js[\'jsfiles\']);';
        $content = str_replace($replacetext, $newtext, $content);
    }

    $fhandle = fopen($fname, 'w');
    if (fwrite($fhandle, $content) === FALSE) {
        $SESSION->add_error_msg('Cannot write to file "' . $fname . '". Please <a href="' . $info . '">update this file</a> manually.', false);
    }
    fclose($fhandle);


    if ($oldversion < 2013111500) {
        $installedtype = (object) array('name' => 'zotext', 'plugin' => 'cloud');
        ensure_record_exists('artefact_installed_type', $installedtype, $installedtype);
    }

    if ($oldversion < 2015122500) {
        // arnes MAPA is a special case of ownCloud, so change all 'arnesmapa' instances to 'owncloud' instances
        execute_sql("UPDATE {artefact} SET title = 'owncloud' WHERE artefacttype = 'cloud' AND title = 'arnesmapa'");
    }

    return true;
}
