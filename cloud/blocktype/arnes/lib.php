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
 * @subpackage blocktype-arnes
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once('lib/crypt.php');


class PluginBlocktypeArnes extends PluginBlocktypeCloud {
    
    const servicepath = 'arnespath';
    
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/arnes');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/arnes');
    }

    public static function get_categories() {
        return array('cloud');
    }

    public static function render_instance(BlockInstance $instance, $editing=false) {
        $configdata = $instance->get('configdata');
        $viewid     = $instance->get('view');
        
        $fullpath = (!empty($configdata['fullpath']) ? $configdata['fullpath'] : '.|@');
        $selected = (!empty($configdata['artefacts']) ? $configdata['artefacts'] : array());

        $smarty = smarty_core();
        list($folder, $path) = explode('|', $fullpath, 2);
        $data = self::get_filelist($folder, $selected);
        $smarty->assign('folders', $data['folders']);
        $smarty->assign('files', $data['files']);
        $smarty->assign('viewid', $viewid);
        return $smarty->fetch('blocktype:arnes:list.tpl');
    }

    public static function has_instance_config() {
        return true;
    }

    public static function instance_config_form($instance) {
        $instanceid = $instance->get('id');
        $configdata = $instance->get('configdata');
        safe_require('artefact', 'cloud');
        $instance->set('artefactplugin', 'cloud');
        
        return array(
            'arnesfiles' => array(
                'type'     => 'datatables',
                'title'    => get_string('selectfiles','blocktype.cloud/arnes'),
                'service'  => 'arnes',
                'block'    => $instanceid,
                'fullpath' => (isset($configdata['fullpath']) ? $configdata['fullpath'] : null),
                'options'  => array(
                    'showFolders'    => true,
                    'showFiles'      => true,
                    'selectFolders'  => false,
                    'selectFiles'    => true,
                    'selectMultiple' => true
                ),
            ),
        );
    }

    public static function instance_config_save($values) {
        global $_SESSION;
        // Folder and file IDs (and other values) are returned as JSON/jQuery serialized string.
        // We have to parse that string and urldecode it (to correctly convert square brackets)
        // in order to get cloud folder and file IDs - they are stored in $artefacts array.
        parse_str(urldecode($values['arnesfiles']));
        if (!isset($artefacts) || empty($artefacts)) {
            $artefacts = array();
        }
        
        $values = array(
            'title'       => $values['title'],
            'fullpath'    => $_SESSION[self::servicepath],
            'artefacts' => $artefacts,
        );
        return $values;
    }

    public static function get_artefacts(BlockInstance $instance) {
        // Not needed, but must be implemented.
    }

    public static function artefactchooser_element($default=null) {
        // Not needed, but must be implemented.
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /*********************************************
     * Methods & stuff for accessing Arnes API *
     *********************************************/
    
    public function cloud_info() {
        return array(
            'ssl'        => false,
            'version'    => '',
            'wwwurl'     => 'http://www.arnes.si/',
            'ftpurl'     => 'ftp://www2.arnes.si/',
            'ftpserver'  => 'www2.arnes.si',
        );
    }
    
    public function consumer_tokens() {
        return array(
            'key'      => get_config_plugin('blocktype', 'arnes', 'consumerkey'),
            'secret'   => get_config_plugin('blocktype', 'arnes', 'consumersecret'),
            'callback' => get_config('wwwroot') . 'artefact/cloud/blocktype/arnes/callback.php'
        );
    }
    
    public function user_tokens($userid) {
        return ArtefactTypeCloud::get_user_preferences('arnes', $userid);
    }
    
    public function service_list() {
        global $USER;
        $usertoken = self::user_tokens($USER->get('id'));
        if (isset($usertoken['user_name']) && !empty($usertoken['user_name']) &&
            isset($usertoken['user_pass']) && !empty($usertoken['user_pass'])) {
            return array(
                'service_name'   => 'arnes',
                'service_url'    => 'http://www.arnes.si',
                'service_auth'   => true,
                'service_manage' => true,
                //'revoke_access'  => false,
            );
        } else {
            return array(
                'service_name'   => 'arnes',
                'service_url'    => 'http://www.arnes.si',
                'service_auth'   => false,
                'service_manage' => false,
                //'revoke_access'  => false,
            );
        }
    }
    
    public function request_token() {
        // Not used...
    }

    public function access_token($params) {
        // Not used...
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('arnes', $USER->get('id'), null);
    }
    
    public function revoke_access() {
        // Not used...
    }
    
    public function account_info() {
        global $USER;
        $usertoken = self::user_tokens($USER->get('id'));
        $space_used = self::get_disk_usage();
        // Currently Arnes gives 5GB space to each user
        $space_amount = 5*1024*1024*1024;
        return array(
            'service_name' => 'arnes',
            'service_auth' => true,
            'user_name'    => $usertoken['user_name'],
            'user_email'   => $usertoken['user_name'].'@guest.arnes.si',
            'user_profile' => 'http://www2.arnes.si/~'.$usertoken['user_name'],
            'space_used'   => bytes_to_size1024($space_used),
            'space_amount' => bytes_to_size1024($space_amount),
            'space_ratio'  => number_format(($space_used/$space_amount)*100, 2),
        );
    }
    
    /*
     * This function returns list of selected files/folders which will be displayed in a view/page.
     *
     * $folder_id   integer   ID of the folder (on FTP server), which contents we wish to retrieve
     * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
     */
    public function get_filelist($folder_id='.', $selected=array()) {
        global $USER, $THEME;
        
        $cloud     = self::cloud_info();
        $usertoken = self::user_tokens($USER->get('id'));
        $conn_id   = ftp_connect($cloud['ftpserver']);
        if ($conn_id) {
            @ftp_login($conn_id, $usertoken['user_name'], decrypt($usertoken['user_pass']));
            $contents = ftp_rawlist($conn_id, $folder_id);
            //log_debug($contents);
            ftp_close($conn_id);
            
            if(isset($contents) && !empty($contents)) {
                $output = array(
                    'folders' => array(),
                    'files'   => array()
                );
                foreach($contents as $line) {
                    preg_match("#([drwx\-]+)([\s]+)([a-zA-Z0-9]+)([\s]+)([a-zA-Z0-9]+)([\s]+)([a-zA-Z0-9\.]+)([\s]+)([0-9]+)([\s]+)([a-zA-Z]+)([\s]+)([0-9]+)([\s]+)([0-9\:]+)([\s]+)([a-zA-Z0-9\.\-\_\+\& ]+)#si", $line, $artefact);
                    if (in_array($artefact[17], $selected)) {
                        $id          = ($folder_id != '.' ? $folder_id.'/'.$artefact[17] : $artefact[17]);
                        $type        = ($artefact[3] == 1 ? 'file' : 'folder');
                        $icon        = $THEME->get_url('images/' . ($artefact[3] == 1 ? 'file' : 'folder') . '.gif');
                        // Get artefactname by removing parent path from beginning...
                        $title       = mb_convert_encoding($artefact[17], 'utf-8', 'utf7-imap');
                        $description = ''; // FTP doesn't support file/folder descriptions
                        $created = format_date(strtotime($artefact[11].' '.$artefact[13].' '.(strpos($artefact[15], ':') != false ? date('Y') : $artefact[15])), 'strftimedaydate');
                        if ($artefact[3] == 1) {
                            $size    = bytes_to_size1024($artefact[9]);
                            $output['files'][] = array('iconsrc' => $icon, 'id' => $id, 'title' => $title, 'description' => $description, 'size' => $size, 'ctime' => $created);
                        } else {
                            $size    = '';
                            $output['folders'][] = array('iconsrc' => $icon, 'id' => $id, 'title' => $title, 'description' => $description, 'size' => $size, 'ctime' => $created);
                        }
                    }
                }
                
                return $output;
            } else {
                return array();
            }
        } else {
            throw new AccessDeniedException('Couldn\'t connect to '.$cloud['ftpserver']);
        }
    }
    
    /*
     * This function gets folder contents and formats it, so it can be used in blocktype config form
     * (Pieform element) and in manage page.
     *
     * $folder_id   integer   ID of the folder (on FTP server), which contents we wish to retrieve
     * $options     integer   List of 6 integers (booleans) to indicate (for all 6 options) if option is used or not
     * $block       integer   ID of the block in given Mahara view/page
     * $fullpath    string    Fullpath to the folder (on FTP server), last opened by user
     *
     * $output      array     Function returns JSON encoded array of values that is suitable to feed jQuery Datatables with.
                              jQuery Datatable than draw an enriched HTML table according to values, contained in $output.
     * PLEASE NOTE: For jQuery Datatable to work, the $output array must be properly formatted and JSON encoded.
     *              Please see: http://datatables.net/usage/server-side (Reply from the server)!
     */
    public function get_folder_content($folder_id='.', $options, $block=0, $fullpath='.|@') {
        global $USER, $THEME;

        // Get selected artefacts (folders and/or files)
        if ($block > 0) {
            $data = unserialize(get_field('block_instance', 'configdata', 'id', $block));
            if (!empty($data)) {
                $artefacts = $data['artefacts'];
            } else {
                $artefacts = array();
            }
        } else {
            $artefacts = array();
        }
        
        // Get pieform element display options...
        $manageButtons  = (boolean) $options[0];
        $showFolders    = (boolean) $options[1];
        $showFiles      = (boolean) $options[2];
        $selectFolders  = (boolean) $options[3];
        $selectFiles    = (boolean) $options[4];
        $selectMultiple = (boolean) $options[5];

        // Set/get return path...
        if ($folder_id == 'init') {
            if (strlen($fullpath) > 3) {
                list($current, $path) = explode('|', $fullpath, 2);
                $_SESSION[self::servicepath] = $current . '|' . $path;
                $folder_id = $current;
            } else {
                // Full path equals path to root folder
                $_SESSION[self::servicepath] = '.|@';
                $folder_id = '.';
            }
        } else {
            if ($folder_id != 'parent') {
                // Go to child folder...
                if (strlen($folder_id) > 1) {
                    list($current, $path) = explode('|', $_SESSION[self::servicepath], 2);
                    if ($current != $folder_id) {
                        $_SESSION[self::servicepath] = $folder_id . '|' . $_SESSION[self::servicepath];
                    }
                }
                // Go to root folder...
                else {
                    $_SESSION[self::servicepath] = '.|@';
                }
            } else {
                // Go to parent folder...
                if (strlen($_SESSION[self::servicepath]) > 3) {
                    list($current, $parent, $path) = explode('|', $_SESSION[self::servicepath], 3);
                    $_SESSION[self::servicepath] = $parent . '|' . $path;
                    $folder_id = $parent;
                }
            }
        }
        
        list($parent_id, $path) = explode('|', $_SESSION[self::servicepath], 2);

        // Get folder contents...
        $cloud     = self::cloud_info();
        $usertoken = self::user_tokens($USER->get('id'));
        $conn_id   = ftp_connect($cloud['ftpserver']);
        if ($conn_id) {
            @ftp_login($conn_id, $usertoken['user_name'], decrypt($usertoken['user_pass']));
            $contents = ftp_rawlist($conn_id, $folder_id);
            ftp_close($conn_id);
            
            $output = array();
            $count = 0;
            // Add 'parent' row entry to jQuery Datatable...
            if ($folder_id != '.') {
                $type        = 'parentfolder';
                $foldername  = get_string('parentfolder', 'artefact.file');
                $title       = '<a class="changefolder" href="javascript:void(0)" id="parent" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '"><img src="' . get_config('wwwroot') . 'artefact/cloud/theme/raw/static/images/parentfolder.png"></a>';
                $output['aaData'][] = array('', $title, '', $type);
            }
            if(isset($contents) && !empty($contents)) {
                foreach($contents as $line) {
                    preg_match("#([drwx\-]+)([\s]+)([a-zA-Z0-9]+)([\s]+)([a-zA-Z0-9]+)([\s]+)([a-zA-Z0-9\.]+)([\s]+)([0-9]+)([\s]+)([a-zA-Z]+)([\s]+)([0-9]+)([\s]+)([0-9\:]+)([\s]+)([a-zA-Z0-9\.\-\_\+\& ]+)#si", $line, $artefact);
                    if ($artefact[17] != '.' && $artefact[17] != '..') {
                        $id           = ($folder_id != '.' ? $folder_id.'/'.$artefact[17] : $artefact[17]);
                        $type         = ($artefact[3] == 1 ? 'file' : 'folder');
                        $icon         = '<img src="' . $THEME->get_url('images/' . ($artefact[3] == 1 ? 'file' : 'folder') . '.gif') . '">';
                        // Get artefactname by removing parent path from beginning...
                        $artefactname = mb_convert_encoding($artefact[17], 'utf-8', 'utf7-imap'); // basename($artefact[17]) ???
                        if ($artefact[3] == 1) {
                            $title    = '<a class="filedetails" href="details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $artefactname) . '">' . $artefactname . '</a>';
                        } else {
                            $title    = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $artefactname) . '">' . $artefactname . '</a>';
                        }
                        $controls = '';
                        $selected = (in_array(''.$id, $artefacts) ? ' checked' : '');
                        if ($artefact[3] == 1) {
                            if ($selectFiles && !$manageButtons) {
                                $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                            } elseif ($manageButtons) {
                                $controls .= '<a class="btn" href="download.php?id=' . $id . '&save=1">' . get_string('save', 'artefact.cloud') . '</a>';
                                $controls .= '<a class="btn" href="download.php?id=' . $id . '">' . get_string('download', 'artefact.cloud') . '</a>';
                            }
                        } else {
                            if ($selectFolders) {
                                $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                            }
                        }
                        $output['aaData'][] = array($icon, $title, $controls, $type);
                        $count++;
                    }
                }
                $output['iTotalRecords'] = $count;
                $output['iTotalDisplayRecords'] = $count;
                    
                return json_encode($output);
            }
        } else {
            throw new AccessDeniedException('Couldn\'t connect to '.$cloud['ftpserver']);
        }
    }
    
    public function get_folder_info($folder_id='.') {
        global $USER, $THEME;
        $folderpath = dirname($folder_id);
        $foldername = basename($folder_id);
        
        $cloud     = self::cloud_info();
        $usertoken = self::user_tokens($USER->get('id'));
        $conn_id = ftp_connect($cloud['ftpserver']);
        if ($conn_id) {
            @ftp_login($conn_id, $usertoken['user_name'], decrypt($usertoken['user_pass']));
            $contents = ftp_rawlist($conn_id, $folderpath);
            ftp_close($conn_id);
            if(isset($contents) && !empty($contents)) {
                foreach($contents as $line) {
                    // if $line contains $foldername that we want,
                    // than extract the data about that folder...
                    if (strpos($line, $foldername) != false) {
                        preg_match("#([drwx\-]+)([\s]+)([a-zA-Z0-9]+)([\s]+)([a-zA-Z0-9]+)([\s]+)([a-zA-Z0-9\.]+)([\s]+)([0-9]+)([\s]+)([a-zA-Z]+)([\s]+)([0-9]+)([\s]+)([0-9\:]+)([\s]+)([a-zA-Z0-9\.\-\_\+\& ]+)#si", $line, $out);
                        $modified = $out[11].' '.$out[13].' '.(strpos($out[15], ':') != false ? date('Y') : $out[15]);
                        $info = array(
                            'rights'  => $out[1],
                            'type'    => ($out[3] == 1 ? 'file' : 'folder'),
                            //'owner'   => $out[5],
                            //'size'    => $out[9], // always returns 4096 for folders!!!
                            'updated' => format_date(strtotime($modified), 'strfdaymonthyearshort'),
                            'name'    => mb_convert_encoding($out[17], 'utf-8', 'utf7-imap'),
                            'id'      => $out[17], // filename in 'utf7-imap' format
                        );
                    }
                }
            }
            return $info;
        } else {
            throw new AccessDeniedException('Couldn\'t connect to '.$cloud['ftpserver']);
        }
    }
    
    public function get_file_info($file_id='.') {
        global $USER, $THEME;
        $filepath = dirname($file_id);
        $filename = basename($file_id);
        
        $cloud     = self::cloud_info();
        $usertoken = self::user_tokens($USER->get('id'));
        $conn_id   = ftp_connect($cloud['ftpserver']);
        if ($conn_id) {
            @ftp_login($conn_id, $usertoken['user_name'], decrypt($usertoken['user_pass']));
            $contents = ftp_rawlist($conn_id, $filepath);
            ftp_close($conn_id);
            if(isset($contents) && !empty($contents)) {
                foreach($contents as $line) {
                    // if $line contains $filename that we want,
                    // than extract the data about that file...
                    if (strpos($line, $filename) != false) {
                        preg_match("#([drwx\-]+)([\s]+)([a-zA-Z0-9]+)([\s]+)([a-zA-Z0-9]+)([\s]+)([a-zA-Z0-9\.]+)([\s]+)([0-9]+)([\s]+)([a-zA-Z]+)([\s]+)([0-9]+)([\s]+)([0-9\:]+)([\s]+)([a-zA-Z0-9\.\-\_\+\& ]+)#si", $line, $out);
                        $modified = $out[11].' '.$out[13].' '.(strpos($out[15], ':') != false ? date('Y') : $out[15]);
                        $info = array(
                            'rights'  => $out[1],
                            'type'    => ($out[3] == 1 ? 'file' : 'folder'),
                            //'owner'   => $out[5],
                            'size'    => bytes_to_size1024($out[9]),
                            'bytes'   => $out[9],
                            'updated' => format_date(strtotime($modified), 'strfdaymonthyearshort'),
                            'name'    => mb_convert_encoding($out[17], 'utf-8', 'utf7-imap'),
                            'id'      => $out[17], // filename in 'utf7-imap' format
                        );
                    }
                }
            }
            return $info;
        } else {
            throw new AccessDeniedException('Couldn\'t connect to '.$cloud['ftpserver']);
        }
    }
    
    public function download_file($file_id='.') {
        global $USER;

        $cloud     = self::cloud_info();
        $usertoken = self::user_tokens($USER->get('id'));

        // Construct download, to download file...
        $download_url = 'ftp://' . $cloud['ftpserver'] . '/' . $file_id;
        $credentials  = $usertoken['user_name'] . ':' . decrypt($usertoken['user_pass']);
            
        $result = '';           
        $ch = curl_init($download_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_USERPWD, $credentials);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
    
    public function embed_file($file_id='.', $options=array()) {
        // Not used...
    }
    
    function get_disk_usage($folder_id='.') {
        global $USER;
        $size = 0;
        $cloud     = self::cloud_info();
        $usertoken = self::user_tokens($USER->get('id'));
        $conn_id   = ftp_connect($cloud['ftpserver']);
        @ftp_login($conn_id, $usertoken['user_name'], decrypt($usertoken['user_pass']));
        $contents = ftp_rawlist($conn_id, $folder_id);
        ftp_close($conn_id);
        if(count($contents)) {
            foreach($contents as $line) {
                preg_match("#([drwx\-]+)([\s]+)([a-zA-Z0-9]+)([\s]+)([a-zA-Z0-9]+)([\s]+)([a-zA-Z0-9\.]+)([\s]+)([0-9]+)([\s]+)([a-zA-Z]+)([\s]+)([0-9]+)([\s]+)([0-9\:]+)([\s]+)([a-zA-Z0-9\.\-\_\+\& ]+)#si", $line, $out);
                // If subfolder and not . or ..
                if ($out[3] != 1 && $out[17] != "." && $out[17] != "..") {
                    // Calculate and add the size of subfolder
                    $size += self::get_disk_usage($folder_id.'/'.$out[17]);
                }
                // If file
                else {
                    // Use floatval instead of intval just in case...
                    $size += floatval($out[9]);
                }
            }
        }
        return $size;
    }

}

?>
