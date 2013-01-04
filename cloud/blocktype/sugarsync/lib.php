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

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/oauth.php');


class PluginBlocktypeSugarsync extends PluginBlocktypeCloud {

    const servicepath = 'sugarsyncpath';
    
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/sugarsync');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/sugarsync');
    }

    public static function get_categories() {
        return array('cloud');
    }

    public static function render_instance(BlockInstance $instance, $editing=false) {
        $configdata = $instance->get('configdata');
        $viewid     = $instance->get('view');
        
        $fullpath = (!empty($configdata['fullpath']) ? $configdata['fullpath'] : '0|@');
        list($folder, $path) = explode('|', $fullpath, 2);
        $selected = (!empty($configdata['artefacts']) ? $configdata['artefacts'] : array());
        
        $smarty = smarty_core();
        $data = self::get_filelist($folder, $selected);
        $smarty->assign('folders', $data['folders']);
        $smarty->assign('files', $data['files']);
        $smarty->assign('viewid', $viewid);
        return $smarty->fetch('blocktype:sugarsync:list.tpl');
    }

    public static function has_instance_config() {
        return true;
    }

    public static function instance_config_form($instance) {
        $instanceid = $instance->get('id');
        $configdata = $instance->get('configdata');
        $allowed = (!empty($configdata['allowed']) ? $configdata['allowed'] : array());
        safe_require('artefact', 'cloud');
        $instance->set('artefactplugin', 'cloud');
        
        return array(
            'sugarsyncfiles' => array(
                'type'     => 'datatables',
                'title'    => get_string('selectfiles','blocktype.cloud/sugarsync'),
                'service'  => 'sugarsync',
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
        parse_str(urldecode($values['sugarsyncfiles']));
        if (!isset($artefacts) || empty($artefacts)) {
            $artefacts = array();
        }
        
        $values = array(
            'title'     => $values['title'],
            'fullpath'  => $_SESSION[self::servicepath],
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

    public static function has_config() {
        return true;
    }

    public static function get_config_options() {
        $elements = array();
        $elements['applicationdesc'] = array(
            'type'  => 'html',
            'value' => get_string('applicationdesc', 'blocktype.cloud/sugarsync', '<a href="http://www.sugarsync.com/developer/login" target="_blank">', '</a>'),
        );
        $elements['applicationgeneral'] = array(
            'type' => 'fieldset',
            'legend' => get_string('applicationgeneral', 'blocktype.cloud/sugarsync'),
            'elements' => array(
                'applicationname' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationname', 'blocktype.cloud/sugarsync'),
                    'defaultvalue' => get_config('sitename'),
                    'description'  => get_string('applicationnamedesc', 'blocktype.cloud/sugarsync'),
                    'readonly'     => true,
                ),
                'applicationid' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationid', 'blocktype.cloud/sugarsync'),
                    'defaultvalue' => get_config_plugin('blocktype', 'sugarsync', 'applicationid'),
                    'description'  => get_string('applicationiddesc', 'blocktype.cloud/sugarsync'),
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'consumerkey' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumerkey', 'blocktype.cloud/sugarsync'),
                    'defaultvalue' => get_config_plugin('blocktype', 'sugarsync', 'consumerkey'),
                    'description'  => get_string('consumerkeydesc', 'blocktype.cloud/sugarsync'),
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'consumersecret' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumersecret', 'blocktype.cloud/sugarsync'),
                    'defaultvalue' => get_config_plugin('blocktype', 'sugarsync', 'consumersecret'),
                    'description'  => get_string('consumersecretdesc', 'blocktype.cloud/sugarsync'),
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'applicationicon' => array(
                    'type'         => 'html',
                    'title'        => get_string('applicationicon', 'blocktype.cloud/sugarsync'),
                    'value'        => '<table border="0"><tr style="text-align:center">
                                       <td style="vertical-align:bottom"><img src="'.get_config('wwwroot').'artefact/cloud/icons/016x016.png" border="0" style="border:1px solid #ccc"><br>16x16</td>
                                       <td style="vertical-align:bottom"><img src="'.get_config('wwwroot').'artefact/cloud/icons/064x064.png" border="0" style="border:1px solid #ccc"><br>64x64</td>
                                       <td style="vertical-align:bottom"><img src="'.get_config('wwwroot').'artefact/cloud/icons/128x128.png" border="0" style="border:1px solid #ccc"><br>128x128</td>
                                       </table>',
                    'description'  => get_string('applicationicondesc', 'blocktype.cloud/sugarsync'),
                ),
            )
        );
        return array(
            'elements' => $elements,
        );

    }

    public static function save_config_options($values) {
        set_config_plugin('blocktype', 'sugarsync', 'applicationid', $values['applicationid']);
        set_config_plugin('blocktype', 'sugarsync', 'consumerkey', $values['consumerkey']);
        set_config_plugin('blocktype', 'sugarsync', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /***********************************************
     * Methods & stuff for accessing SugarSync API *
     ***********************************************/
    
    public function cloud_info() {
        return array(
            'ssl'        => true,
            'version'    => '',
            'baseurl'    => 'https://api.sugarsync.com/',
            'authurl'    => 'https://api.sugarsync.com/authorization/',
            'appauthurl' => 'https://api.sugarsync.com/app-authorization/',
        );
    }
    
    public function consumer_tokens() {
        return array(
            'appid'    => get_config_plugin('blocktype', 'sugarsync', 'applicationid'),
            'key'      => get_config_plugin('blocktype', 'sugarsync', 'consumerkey'),
            'secret'   => get_config_plugin('blocktype', 'sugarsync', 'consumersecret'),
            'callback' => get_config('wwwroot') . 'artefact/cloud/blocktype/sugarsync/callback.php'
        );
    }
    
    public function user_tokens($userid) {
        return ArtefactTypeCloud::get_user_preferences('sugarsync', $userid);
    }
    
    public function service_list() {
        global $USER;
        $consumer    = self::consumer_tokens();
        $usertoken   = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            if (isset($usertoken['refresh_token']) && !empty($usertoken['refresh_token'])) {
                return array(
                    'service_name'   => 'sugarsync',
                    'service_url'    => 'http://www.sugarsync.com',
                    'service_auth'   => true,
                    'service_manage' => true,
                    //'revoke_access'  => true,
                );
            } else {
                return array(
                    'service_name'   => 'sugarsync',
                    'service_url'    => 'http://www.sugarsync.com',
                    'service_auth'   => false,
                    'service_manage' => false,
                    //'revoke_access'  => false,
                );
            }
        } else {
            throw new ConfigException('Can\'t find SugarSync consumer key and/or consumer secret.');
        }
    }
    
    public function request_token() {
        // SugarSync doesn't use request token, instead it uses refresh token to acquire access token later...
        // Get and store refresh token implemented in account.php file, because we need to simulate "User consent page"...
    }

    public function access_token($refresh_token) {
        global $USER, $SESSION;

        $cloud    = PluginBlocktypeSugarsync::cloud_info();
        $consumer = PluginBlocktypeSugarsync::consumer_tokens();
        $key    = $consumer['key'];
        $secret = $consumer['secret'];
        $token  = $cloud['appauthurl'] . $refresh_token;
    
        $request_body = <<< XML
<?xml version="1.0" encoding="UTF-8" ?>
<tokenAuthRequest>
 <accessKeyId>$key</accessKeyId>
 <privateAccessKey>$secret</privateAccessKey>
 <refreshToken>$token</refreshToken>
</tokenAuthRequest>
XML;

        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            // SugarSync doesn't have API version yet, so...
            //$url = $cloud['baseurl'].$cloud['version'].'/authorization';
            $url = $cloud['authurl'];
            $method = 'POST';
            $port = $cloud['ssl'] ? '443' : '80';
            $header = array();
            $header[] = 'User-Agent: SugarSync API PHP Client';
            $header[] = 'Host: api.sugarsync.com';
            $header[] = 'Content-Length: ' . strlen($request_body);
            $header[] = 'Content-Type: application/xml; charset=UTF-8';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $request_body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 201 /* HTTP/1.1 201 Created */ && !empty($result->data)) {
                // Get user ID...
                $data = oauth_parse_xml(substr($result->data, $result->info['header_size']));
                $data['userid'] = basename($data['user']); // Extract user ID part from the URL
                // Get access token...
                $matches = array();
                preg_match('#authorization\/([A-Za-z0-9\-\_\.]+)#', $result->data, $matches);
                $data['access_token'] = $matches[1];
                return $data;
            } else {
                $SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/sugarsync'));
            }
        } else {
            throw new ConfigException('Can\'t find SugarSync consumer key and/or consumer secret.');
        }
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('sugarsync', $USER->get('id'), null);
    }
    
    public function revoke_access() {
        // SugarSync API doesn't allow programmatical access revoking, so:
        // Nothing to do!
    }
    
    /*
     * SEE: http://www.sugarsync.com/dev/api/method/get-user-info.html
     */
    public function account_info() {
        global $USER;
        $cloud       = self::cloud_info();
        $consumer    = self::consumer_tokens();
        $usertoken   = self::user_tokens($USER->get('id'));
        $accesstoken = self::access_token($usertoken['refresh_token']);
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $accesstoken['user'];
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $header = array();
            $header[] = 'User-Agent: SugarSync API PHP Client';
            $header[] = 'Authorization: ' . $cloud['authurl'] . $accesstoken['access_token'];
            $header[] = 'Host: api.sugarsync.com';
            $header[] = 'Content-Type: application/xml; charset=UTF-8';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = oauth_parse_xml(substr($result->data, $result->info['header_size']));
                return array(
                    'service_name' => 'sugarsync',
                    'service_auth' => true,
                    'user_id'      => $accesstoken['userid'],
                    'user_name'    => $data['nickname'],
                    'user_email'   => $data['username'],
                    'space_used'   => bytes_to_size1024(floatval($data['quota']['usage'])),
                    'space_amount' => bytes_to_size1024(floatval($data['quota']['limit'])),
                    'space_ratio'  => number_format((floatval($data['quota']['usage'])/floatval($data['quota']['limit']))*100, 2),
                );
            } else {
                return array(
                    'service_name' => 'sugarsync',
                    'service_auth' => false,
                    'user_id'      => null,
                    'user_name'    => null,
                    'user_email'   => null,
                    'space_used'   => null,
                    'space_amount' => null,
                    'space_ratio'  => null,
                );
            }
         } else {
            throw new ConfigException('Can\'t find SugarSync consumer key and/or consumer secret.');
        }
    }
    
    
    /*
     * This function returns list of selected files/folders which will be displayed in a view/page.
     *
     * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
     * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
     *
     * SEE: http://www.sugarsync.com/dev/api/method/get-folders.html
     *
     */
    public function get_filelist($folder_id=0, $selected=array()) {
        global $USER, $THEME;

        // Get folder contents...
        $cloud       = self::cloud_info();
        $consumer    = self::consumer_tokens();
        $usertoken   = self::user_tokens($USER->get('id'));
        $accesstoken = self::access_token($usertoken['refresh_token']);
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            if (strlen($folder_id) > 1) {
                $url = $cloud['baseurl'].'folder/'.$folder_id.'/contents';
            } else {
                $url = $accesstoken['user'].'/folders/contents';
            }
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $header = array();
            $header[] = 'User-Agent: SugarSync API PHP Client';
            $header[] = 'Authorization: ' . $cloud['authurl'] . $accesstoken['access_token'];
            $header[] = 'Host: api.sugarsync.com';
            $header[] = 'Content-Type: application/xml; charset=UTF-8';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (!empty($result)) {
                if ($result->info['http_code'] == 200 && !empty($result->data)) {
                    $data = oauth_parse_xml(substr($result->data, $result->info['header_size']));
                    $output = array(
                        'folders' => array(),
                        'files'   => array()
                    );
                    if (isset($data['collection']) && !empty($data['collection'])) {
                        $folders = $data['collection'];
                        foreach ($folders as $folder) {
                            $id = basename($folder['ref']);
                            if (in_array('folder'.$id, $selected)) {
                                //$type        = 'folder';
                                $icon        = $THEME->get_url('images/folder.gif');
                                $title       = $folder['displayName'];
                                $description = '';
                                $size        = bytes_to_size1024($folder['size']);
                                $created     = format_date(strtotime($data['timeCreated']), 'strfdaymonthyearshort');
                                $output['folders'][] = array('iconsrc' => $icon, 'id' => $id, 'title' => $title, 'description' => $description, 'size' => $size, 'ctime' => $created);
                            }
                        }
                    }
                    if (isset($data['file']) && !empty($data['file'])) {
                        $files = $data['file'];
                        foreach ($files as $file) {
                            $id = basename($file['ref']);
                            if (in_array('file'.$id, $selected)) {
                                //$type        = 'file';
                                $icon        = $THEME->get_url('images/file.gif');
                                $title       = $file['displayName'];
                                $description = '';
                                $size        = bytes_to_size1024($file['size']);
                                if (isset($file['timeCreated'])) {
                                    $created     = format_date(strtotime($file['timeCreated']), 'strftimedaydate');
                                } else {
                                    $created     = format_date(strtotime($file['lastModified']), 'strftimedaydate');
                                }
                                $output['files'][] = array('iconsrc' => $icon, 'id' => $id, 'title' => $title, 'description' => $description, 'size' => $size, 'ctime' => $created);
                            }
                        }
                    }
                    
                    return $output;
                }
            } else {
                return array();
            }
         } else {
            throw new ConfigException('Can\'t find SugarSync consumer key and/or consumer secret.');
        }
    }

    /*
     * This function gets folder contents and formats it, so it can be used in blocktype config form
     * (Pieform element) and in manage page.
     *
     * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
     * $options     integer   List of 6 integers (booleans) to indicate (for all 6 options) if option is used or not
     * $block       integer   ID of the block in given Mahara view/page
     * $fullpath    string    Fullpath to the folder (on Cloud Service), last opened by user
     *
     * $output      array     Function returns JSON encoded array of values that is suitable to feed jQuery Datatables with.
                              jQuery Datatable than draw an enriched HTML table according to values, contained in $output.
     * PLEASE NOTE: For jQuery Datatable to work, the $output array must be properly formatted and JSON encoded.
     *              Please see: http://datatables.net/usage/server-side (Reply from the server)!
     *
     * SEE: http://www.sugarsync.com/dev/api/method/get-syncfolders.html
     * SEE: http://www.sugarsync.com/dev/api/method/get-folders.html
     *
     */
    public function get_folder_content($folder_id=0, $options, $block=0, $fullpath='0|@') {
        global $USER, $THEME;
        
        // Get selected artefacts (folders and/or files)
        if ($block > 0) {
            $data = unserialize(get_field('block_instance', 'configdata', 'id', $block));
            if (!empty($data) && isset($data['artefacts'])) {
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
                $_SESSION[self::servicepath] = '0|@';
                $folder_id = 0;
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
                    $_SESSION[self::servicepath] = '0|@';
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
        $cloud       = self::cloud_info();
        $consumer    = self::consumer_tokens();
        $usertoken   = self::user_tokens($USER->get('id'));
        $accesstoken = self::access_token($usertoken['refresh_token']);
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            if (strlen($folder_id) > 1) {
                $url = $cloud['baseurl'].'folder/'.$folder_id.'/contents';
            } else {
                $url = $accesstoken['user'].'/folders/contents';
            }
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $header = array();
            $header[] = 'User-Agent: SugarSync API PHP Client';
            $header[] = 'Authorization: ' . $cloud['authurl'] . $accesstoken['access_token'];
            $header[] = 'Host: api.sugarsync.com';
            $header[] = 'Content-Type: application/xml; charset=UTF-8';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (!empty($result)) {
                if ($result->info['http_code'] == 200 && !empty($result->data)) {
                    $data = oauth_parse_xml(substr($result->data, $result->info['header_size']));
                    $output = array();
                    $count = 0;
                    // Add 'parent' row entry to jQuery Datatable...
                    if (strlen($_SESSION[self::servicepath]) > 3) {
                        $type        = 'parentfolder';
                        $foldername  = get_string('parentfolder', 'artefact.file');
                        $title       = '<a class="changefolder" href="javascript:void(0)" id="parent" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '"><img src="' . get_config('wwwroot') . 'artefact/cloud/theme/raw/static/images/parentfolder.png"></a>';
                        $output['aaData'][] = array('', $title, '', $type);
                    }
                    if ($showFolders && isset($data['collection']) && !empty($data['collection'])) {
                        $folders = $data['collection'];
                        foreach ($folders as $folder) {
                            $id          = basename($folder['ref']);
                            $type        = 'folder';
                            $icon        = '<img src="' . $THEME->get_url('images/folder.gif') . '">';
                            $title       = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $folder['displayName']) . '">' . $folder['displayName'] . '</a>';
                            if ($selectFolders) {
                                $selected = (in_array('folder'.$id, $artefacts) ? ' checked' : '');
                                $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="folder' . $id . '"' . $selected . '>';
                            } else {
                                $controls = '';
                            }
                            $output['aaData'][] = array($icon, $title, $controls, $type);
                            $count++;
                        }
                    }
                    if ($showFiles && isset($data['file']) && !empty($data['file'])) {
                        $files = $data['file'];
                        foreach ($files as $file) {
                            $id          = basename($file['ref']);
                            $type        = 'file';
                            $icon        = '<img src="' . $THEME->get_url('images/file.gif') . '">';
                            $title       = '<a class="filedetails" href="details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $file['displayName']) . '">' . $file['displayName'] . '</a>';
                            if ($selectFiles && !$manageButtons) {
                                $selected = (in_array('file'.$id, $artefacts) ? ' checked' : '');
                                $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="file' . $id . '"' . $selected . '>';
                            } elseif ($manageButtons) {
                                $controls  = '<a class="btn" href="download.php?id=' . $id . '&save=1">' . get_string('save', 'artefact.cloud') . '</a>';
                                $controls .= '<a class="btn" href="download.php?id=' . $id . '">' . get_string('download', 'artefact.cloud') . '</a>';
                            } else {
                                $controls = '';
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
                return array();
            }
         } else {
            throw new ConfigException('Can\'t find SugarSync consumer key and/or consumer secret.');
        }
    }

    /*
     * SEE: http://www.sugarsync.com/dev/api/method/get-folder-info.html
     */
    public function get_folder_info($folder_id=0) {
        global $USER;
        $cloud       = self::cloud_info();
        $consumer    = self::consumer_tokens();
        $usertoken   = self::user_tokens($USER->get('id'));
        $accesstoken = self::access_token($usertoken['refresh_token']);
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].'folder/'.$folder_id;
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $header = array();
            $header[] = 'User-Agent: SugarSync API PHP Client';
            $header[] = 'Authorization: ' . $cloud['authurl'] . $accesstoken['access_token'];
            $header[] = 'Host: api.sugarsync.com';
            $header[] = 'Content-Type: application/xml; charset=UTF-8';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = oauth_parse_xml(substr($result->data, $result->info['header_size']));
                $info = array(
                    'id'          => str_replace('/', ':', $data['dsid']),
                    'name'        => $data['displayName'],
                    'shared'      => $data['sharing']['@attributes']['enabled'],
                    'description' => '', // SugarSync doesn't support file/folder descriptions...
                    'created'     => format_date(strtotime($data['timeCreated']), 'strfdaymonthyearshort'),
                    'updated'     => '',
                );
                return $info;
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find SugarSync consumer key and/or consumer secret.');
        }
    }

    /*
     * SEE: http://www.sugarsync.com/dev/api/method/get-file-info.html
     */
    public function get_file_info($file_id=0) {
        global $USER;
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        $accesstoken = self::access_token($usertoken['refresh_token']);
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].'file/'.$file_id;
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $header = array();
            $header[] = 'User-Agent: SugarSync API PHP Client';
            $header[] = 'Authorization: ' . $cloud['authurl'] . $accesstoken['access_token'];
            $header[] = 'Host: api.sugarsync.com';
            $header[] = 'Content-Type: application/xml; charset=UTF-8';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = oauth_parse_xml(substr($result->data, $result->info['header_size']));
                $info = array(
                    'id'          => str_replace('/', ':', $data['dsid']),
                    'name'        => $data['displayName'],
                    'bytes'       => $data['size'],
                    'size'        => bytes_to_size1024($data['size']),
                    'shared'      => $data['publicLink'], // Not just true/false... 
                    'description' => '', // SugarSync doesn't support file/folder descriptions...
                    'created'     => format_date(strtotime($data['timeCreated']), 'strfdaymonthyearshort'),
                    'updated'     => format_date(strtotime($data['lastModified']), 'strfdaymonthyearshort'),
                    'mimetype'    => $data['mediaType'],
                    'parent'      => basename($data['parent']),
                );
                return $info;
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find SugarSync consumer key and/or consumer secret.');
        }
    }

    /*
     * SEE: http://www.sugarsync.com/dev/api/method/get-file-data.html
     * SEE: http://www.sugarsync.com/dev/download-file-example.html
     */
    public function download_file($file_id=0) {
        global $USER;
        $cloud       = self::cloud_info();
        $consumer    = self::consumer_tokens();
        $usertoken   = self::user_tokens($USER->get('id'));
        $accesstoken = self::access_token($usertoken['refresh_token']);

        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            // Construct download, to download file...
            // e.g.: https://api.sugarsync.com/file/<file_id>/data
            $download_url = $cloud['baseurl'] . 'file/' . $file_id . '/data';
            
            $result = '';
            $header[] = 'User-Agent: SugarSync API PHP Client';
            $header[] = 'Authorization: ' . $cloud['authurl'] . $accesstoken['access_token'];
            $header[] = 'Host: api.sugarsync.com';
            $port = $cloud['ssl'] ? '443' : '80';
            
            $ch = curl_init($download_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_PORT, $port);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
         } else {
            throw new ConfigException('Can\'t find SugarSync consumer key and/or consumer secret.');
        }
    }

    public function embed_file($file_id=0, $options=array()) {
        // SugarSync API doesn't support embedding of files, so:
        // Nothing to do!
    }

}

?>
