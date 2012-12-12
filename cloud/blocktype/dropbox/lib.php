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

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/oauth.php');


class PluginBlocktypeDropbox extends PluginBlocktypeCloud {
    
    const servicepath = 'dropboxpath';
    
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/dropbox');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/dropbox');
    }

    public static function get_categories() {
        return array('cloud');
    }

    public static function render_instance(BlockInstance $instance, $editing=false) {
        $configdata = $instance->get('configdata');
        $viewid     = $instance->get('view');
        
        $fullpath = (!empty($configdata['fullpath']) ? $configdata['fullpath'] : '0|@');
        $selected = (!empty($configdata['artefacts']) ? $configdata['artefacts'] : array());

        $smarty = smarty_core();
        $data = self::get_filelist($fullpath, $selected);
        $smarty->assign('folders', $data['folders']);
        $smarty->assign('files', $data['files']);
        $smarty->assign('viewid', $viewid);
        return $smarty->fetch('blocktype:dropbox:list.tpl');
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
            'dropboxfiles' => array(
                'type'     => 'datatables',
                'title'    => get_string('selectfiles','blocktype.cloud/dropbox'),
                'service'  => 'dropbox',
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
        parse_str(urldecode($values['dropboxfiles']));
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

    public static function has_config() {
        return true;
    }

    public static function get_config_options() {
        $elements = array();
        $elements['applicationdesc'] = array(
            'type'  => 'html',
            'value' => get_string('applicationdesc', 'blocktype.cloud/dropbox', '<a href="https://www.dropbox.com/developers/apps" target="_blank">', '</a>'),
        );
        $elements['applicationgeneral'] = array(
            'type' => 'fieldset',
            'legend' => get_string('applicationgeneral', 'blocktype.cloud/dropbox'),
            'elements' => array(
                'applicationname' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationname', 'blocktype.cloud/dropbox'),
                    'defaultvalue' => get_config('sitename'),
                    'description'  => get_string('applicationnamedesc', 'blocktype.cloud/dropbox'),
                    'readonly'     => true,
                ),
                'consumerkey' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumerkey', 'blocktype.cloud/dropbox'),
                    'defaultvalue' => get_config_plugin('blocktype', 'dropbox', 'consumerkey'),
                    'description'  => get_string('consumerkeydesc', 'blocktype.cloud/dropbox'),
                    'rules' => array('required' => true),
                ),
                'consumersecret' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumersecret', 'blocktype.cloud/dropbox'),
                    'defaultvalue' => get_config_plugin('blocktype', 'dropbox', 'consumersecret'),
                    'description'  => get_string('consumersecretdesc', 'blocktype.cloud/dropbox'),
                    'rules' => array('required' => true),
                ),
            )
        );
        $elements['applicationadditional'] = array(
            'type' => 'fieldset',
            'legend' => get_string('applicationadditional', 'blocktype.cloud/dropbox'),
            'elements' => array(
                'applicationweb' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationweb', 'blocktype.cloud/dropbox'),
                    'defaultvalue' => get_config('wwwroot'),
                    'description'  => get_string('applicationwebdesc', 'blocktype.cloud/dropbox'),
                    'size'         => 60,
                    'readonly'     => true,
                ),
                'applicationicon' => array(
                    'type'         => 'html',
                    'title'        => get_string('applicationicon', 'blocktype.cloud/dropbox'),
                    'value'        => '<table border="0"><tr style="text-align:center">
                                       <td style="vertical-align:bottom"><img src="'.get_config('wwwroot').'artefact/cloud/icons/016x016.png" border="0" style="border:1px solid #ccc"><br>16x16</td>
                                       <td style="vertical-align:bottom"><img src="'.get_config('wwwroot').'artefact/cloud/icons/064x064.png" border="0" style="border:1px solid #ccc"><br>64x64</td>
                                       <td style="vertical-align:bottom"><img src="'.get_config('wwwroot').'artefact/cloud/icons/128x128.png" border="0" style="border:1px solid #ccc"><br>128x128</td>
                                       </table>',
                    'description'  => get_string('applicationicondesc', 'blocktype.cloud/dropbox'),
                ),
            )
        );
        return array(
            'elements' => $elements,
        );

    }

    public static function save_config_options($values) {
        set_config_plugin('blocktype', 'dropbox', 'consumerkey', $values['consumerkey']);
        set_config_plugin('blocktype', 'dropbox', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /*********************************************
     * Methods & stuff for accessing Dropbox API *
     *********************************************/
    
    public function cloud_info() {
        return array(
            'ssl'        => true,
            'version'    => '1',
            'baseurl'    => 'https://api.dropbox.com/',
            'contenturl' => 'https://api-content.dropbox.com/',
            'wwwurl'     => 'https://www.dropbox.com/',
        );
    }
    
    public function consumer_tokens() {
        return array(
            'key'      => get_config_plugin('blocktype', 'dropbox', 'consumerkey'),
            'secret'   => get_config_plugin('blocktype', 'dropbox', 'consumersecret'),
            'callback' => get_config('wwwroot') . 'artefact/cloud/blocktype/dropbox/callback.php'
        );
    }
    
    public function user_tokens($userid) {
        return ArtefactTypeCloud::get_user_preferences('dropbox', $userid);
    }
    
    public function service_list() {
        global $USER;
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            if (isset($usertoken['oauth_token']) && !empty($usertoken['oauth_token']) &&
                isset($usertoken['oauth_token_secret']) && !empty($usertoken['oauth_token_secret'])) {
                return array(
                    'service_name'   => 'dropbox',
                    'service_url'    => 'http://www.dropbox.com',
                    'service_auth'   => true,
                    'service_manage' => true,
                    //'revoke_access'  => false,
                );
            } else {
                return array(
                    'service_name'   => 'dropbox',
                    'service_url'    => 'http://www.dropbox.com',
                    'service_auth'   => false,
                    'service_manage' => false,
                    //'revoke_access'  => false,
                );
            }
        } else {
            throw new ConfigException('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }
    
    public function request_token() {
        global $USER, $SESSION;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/oauth/request_token';
            $method = 'POST';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array(
                'oauth_version' => '1.0',
                'oauth_nonce' => mt_rand(),
                'oauth_timestamp' => time(),
                'oauth_consumer_key' => $consumer['key'],
                'oauth_callback' => $consumer['callback'],
                'oauth_signature_method' => 'HMAC-SHA1',
            );
            $params['oauth_signature'] = oauth_compute_hmac_sig($method, $url, $params, $consumer['secret'], null);
            $header = array();
            $header[] = build_oauth_header($params, "Dropbox API PHP Client");
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => oauth_http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                // Store request_token (oauth_token) and request_token_secret (outh_token_secret)
                // We'll need it later...
                $prefs = oauth_parse_str(substr($result->data, $result->info['header_size']));
                ArtefactTypeCloud::set_user_preferences('dropbox', $USER->get('id'), $prefs);
                redirect($cloud['wwwurl'].$cloud['version'].'/oauth/authorize?'.rfc3986_decode($body).'&oauth_callback='.$consumer['callback']);
            } else {
                $SESSION->add_error_msg(get_string('requesttokennotreturned', 'blocktype.cloud/dropbox'));
            }
        } else {
            throw new ConfigException('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }

    public function access_token($usertoken) {
        global $USER, $SESSION;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/oauth/access_token';
            $method = 'POST';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array(
                'oauth_version' => '1.0',
                'oauth_nonce' => mt_rand(),
                'oauth_timestamp' => time(),
                'oauth_consumer_key' => $consumer['key'],
                'oauth_token' => $usertoken['oauth_token'],
                'oauth_verifier' => $usertoken['oauth_verifier'],
                'oauth_signature_method' => 'HMAC-SHA1',
            );
            $params['oauth_signature'] = oauth_compute_hmac_sig($method, $url, $params, $consumer['secret'], $usertoken['oauth_token_secret']);
            $header = array();
            $header[] = build_oauth_header($params, "Dropbox API PHP Client");
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => oauth_http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                // Store request_token (oauth_token) and request_token_secret (outh_token_secret)
                // We'll need it later...
                $prefs = oauth_parse_str(substr($result->data, $result->info['header_size']));
                ArtefactTypeCloud::set_user_preferences('dropbox', $USER->get('id'), $prefs);
            } else {
                $SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/dropbox'));
            }
        } else {
            throw new ConfigException('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('dropbox', $USER->get('id'), null);
    }
    
    public function revoke_access() {
        // Dropbox API doesn't allow programmatical access revoking, so:
        // Nothing to do!
    }
    
    /*
     * SEE: https://www.dropbox.com/developers/reference/api#account-info
     */
    public function account_info() {
        global $USER;
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/account/info';
            $method = 'POST';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array(
                'oauth_version' => '1.0',
                'oauth_nonce' => mt_rand(),
                'oauth_timestamp' => time(),
                'oauth_consumer_key' => $consumer['key'],
                'oauth_token' => $usertoken['oauth_token'],
                'oauth_signature_method' => 'HMAC-SHA1',
            );
            $params['oauth_signature'] = oauth_compute_hmac_sig($method, $url, $params, $consumer['secret'], $usertoken['oauth_token_secret']);
            $header = array();
            $header[] = build_oauth_header($params, "Dropbox API PHP Client");
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => oauth_http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode(substr($result->data, $result->info['header_size']));
                return array(
                    'service_name' => 'dropbox',
                    'service_auth' => true,
                    'user_id'      => $data->uid,
                    'user_name'    => $data->display_name,
                    'user_email'   => $data->email,
                    'space_used'   => bytes_to_size1024(floatval($data->quota_info->normal)+floatval($data->quota_info->shared)),
                    'space_amount' => bytes_to_size1024(floatval($data->quota_info->quota)),
                    'space_ratio'  => number_format(((floatval($data->quota_info->normal)+floatval($data->quota_info->shared))/floatval($data->quota_info->quota))*100, 2),
                );
            } else {
                return array(
                    'service_name' => 'dropbox',
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
            throw new ConfigException('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }
    
    /*
     * This function returns list of selected files/folders which will be displayed in a view/page.
     *
     * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
     * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
     *
     * SEE: https://www.dropbox.com/developers/reference/api#metadata
     */
    public function get_filelist($folder_id='/', $selected=array()) {
        global $USER, $THEME;

        // Get folder contents...
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/metadata/dropbox';
            $parts = explode('/', ltrim($folder_id,'/'));
            foreach($parts as $part) {
                $url .= '/'.rawurlencode($part);
            }
            $method = 'POST';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array(
                'oauth_version' => '1.0',
                'oauth_nonce' => mt_rand(),
                'oauth_timestamp' => time(),
                'oauth_consumer_key' => $consumer['key'],
                'oauth_token' => $usertoken['oauth_token'],
                'oauth_signature_method' => 'HMAC-SHA1',
            );
            $params['oauth_signature'] = oauth_compute_hmac_sig($method, $url, $params, $consumer['secret'], $usertoken['oauth_token_secret']);
            $header = array();
            $header[] = build_oauth_header($params, "Dropbox API PHP Client");
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => oauth_http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode(substr($result->data, $result->info['header_size']));
                $output = array(
                    'folders' => array(),
                    'files'   => array()
                );
                if (!empty($data->contents)) {
                    foreach($data->contents as $artefact) {
                        if (in_array($artefact->path, $selected)) {
                            // In Dropbox id basically means path...
                            $id          = $artefact->path;
                            $type        = ($artefact->is_dir ? 'folder' : 'file');
                            $icon        = $THEME->get_url('images/' . ($artefact->is_dir ? 'folder' : 'file') . '.gif');
                            // Get artefactname by removing parent path from beginning...
                            $title       = basename($artefact->path);
                            $description = ''; // Dropbox doesn't support file/folder descriptions
                            $size        = bytes_to_size1024($artefact->bytes);
                            if ($artefact->is_dir) {
                                $created = format_date(strtotime($artefact->modified), 'strftimedaydate');
                                $output['folders'][] = array('iconsrc' => $icon, 'id' => $id, 'title' => $title, 'description' => $description, 'size' => $size, 'ctime' => $created);
                            } else {
                                $created = format_date(strtotime($artefact->client_mtime), 'strftimedaydate');
                                $output['files'][] = array('iconsrc' => $icon, 'id' => $id, 'title' => $title, 'description' => $description, 'size' => $size, 'ctime' => $created);
                            }
                        }
                    }
                }
                return $output;
            }
         } else {
            throw new ConfigException('Can\'t find Dropbox consumer key and/or consumer secret.');
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
     * SEE: https://www.dropbox.com/developers/reference/api#metadata
     */
    public function get_folder_content($folder_id='/', $options, $block=0, $fullpath='0|@') {
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
                $_SESSION[self::servicepath] = $fullpath;
                $folder_id = $fullpath;
            } else {
                // Full path equals path to root folder
                $_SESSION[self::servicepath] = '/';
                $folder_id = '/';
            }
        }
        
        // Get folder contents...
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/metadata/dropbox';
            $parts = explode('/', ltrim($folder_id,'/'));
            foreach($parts as $part) {
                $url .= '/'.rawurlencode($part);
            }
            $method = 'POST';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array(
                'oauth_version' => '1.0',
                'oauth_nonce' => mt_rand(),
                'oauth_timestamp' => time(),
                'oauth_consumer_key' => $consumer['key'],
                'oauth_token' => $usertoken['oauth_token'],
                'oauth_signature_method' => 'HMAC-SHA1',
            );
            $params['oauth_signature'] = oauth_compute_hmac_sig($method, $url, $params, $consumer['secret'], $usertoken['oauth_token_secret']);
            $header = array();
            $header[] = build_oauth_header($params, "Dropbox API PHP Client");
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => oauth_http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode(substr($result->data, $result->info['header_size']));
                $output = array();
                $count = 0;
                // Set/get return path...
                $_SESSION[self::servicepath] = $data->path;
                // Add 'parent' row entry to jQuery Datatable...
                if (strlen($data->path) > 1) {
                    $parentpath  = str_replace('\\', '/', dirname($data->path));
                    $type        = 'parentfolder';
                    $foldername  = get_string('parentfolder', 'artefact.file');
                    $title       = '<a class="changefolder" href="javascript:void(0)" id="' . $parentpath . '" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '"><img src="' . get_config('wwwroot') . 'artefact/cloud/theme/raw/static/images/parentfolder.png"></a>';
                    $output['aaData'][] = array('', $title, '', $type);
                }
                if (!empty($data->contents)) {
                    foreach($data->contents as $artefact) {
                        // In Dropbox id basically means path...
                        $id           = $artefact->path;
                        $type         = ($artefact->is_dir ? 'folder' : 'file');
                        $icon         = '<img src="' . $THEME->get_url('images/' . ($artefact->is_dir ? 'folder' : 'file') . '.gif') . '">';
                        // Get artefactname by removing parent path from beginning...
                        $artefactname = basename($artefact->path);
                        if ($artefact->is_dir) {
                            $title    = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $artefactname) . '">' . $artefactname . '</a>';
                        } else {
                            $title    = '<a class="filedetails" href="details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $artefactname) . '">' . $artefactname . '</a>';
                        }
                        $controls = '';
                        $selected = (in_array(''.$id, $artefacts) ? ' checked' : '');
                        if ($artefact->is_dir) {
                            if ($selectFolders) {
                                $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                            }
                        } else {
                            if ($selectFiles && !$manageButtons) {
                                $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                            } elseif ($manageButtons) {
                                $controls  = '<a class="btn" href="preview.php?id=' . $id . '" target="_blank">' . get_string('preview', 'artefact.cloud') . '</a>';
                                $controls .= '<a class="btn" href="download.php?id=' . $id . '&save=1">' . get_string('save', 'artefact.cloud') . '</a>';
                                $controls .= '<a class="btn" href="download.php?id=' . $id . '">' . get_string('download', 'artefact.cloud') . '</a>';
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
            throw new ConfigException('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }
    
    /*
     * SEE: https://www.dropbox.com/developers/reference/api#metadata
     */
    public function get_folder_info($folder_id='/') {
        global $USER;
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/metadata/dropbox';
            $parts = explode('/', ltrim($folder_id,'/'));
            foreach($parts as $part) {
                $url .= '/'.rawurlencode($part);
            }
            $method = 'POST';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array(
                'oauth_version' => '1.0',
                'oauth_nonce' => mt_rand(),
                'oauth_timestamp' => time(),
                'oauth_consumer_key' => $consumer['key'],
                'oauth_token' => $usertoken['oauth_token'],
                'oauth_signature_method' => 'HMAC-SHA1',
                // Method specific parameters...
                'list' => false
            );
            $params['oauth_signature'] = oauth_compute_hmac_sig($method, $url, $params, $consumer['secret'], $usertoken['oauth_token_secret']);
            $header = array();
            $header[] = build_oauth_header($params, "Dropbox API PHP Client");
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => oauth_http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode(substr($result->data, $result->info['header_size']));
                $info = array(
                    'id'          => $data->path,
                    'name'        => basename($data->path),
                    'size'        => $data->bytes,
                    'updated'     => format_date(strtotime($data->modified), 'strfdaymonthyearshort'),
                    'rev'         => $data->rev,
                    'root'        => $data->root,
                );
                return $info;
            }
         } else {
            throw new ConfigException('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }
    
    /*
     * SEE: https://www.dropbox.com/developers/reference/api#metadata
     */
    public function get_file_info($file_id='/') {
        global $USER;
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/metadata/dropbox';
            $parts = explode('/', ltrim($file_id,'/'));
            foreach($parts as $part) {
                $url .= '/'.rawurlencode($part);
            }
            $method = 'POST';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array(
                'oauth_version' => '1.0',
                'oauth_nonce' => mt_rand(),
                'oauth_timestamp' => time(),
                'oauth_consumer_key' => $consumer['key'],
                'oauth_token' => $usertoken['oauth_token'],
                'oauth_signature_method' => 'HMAC-SHA1',
                // Method specific parameters...
                'list' => false
            );
            $params['oauth_signature'] = oauth_compute_hmac_sig($method, $url, $params, $consumer['secret'], $usertoken['oauth_token_secret']);
            $header = array();
            $header[] = build_oauth_header($params, "Dropbox API PHP Client");
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => oauth_http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode(substr($result->data, $result->info['header_size']));
                $info = array(
                    'id'       => $data->path,
                    'name'     => basename($data->path),
                    'size'     => bytes_to_size1024($data->bytes),
                    'bytes'    => $data->bytes,
                    'updated'  => format_date(strtotime($data->modified), 'strfdaymonthyearshort'),
                    'created'  => format_date(strtotime($data->client_mtime), 'strfdaymonthyearshort'),
                    'mimetype' => $data->mime_type,
                    'rev'      => $data->rev,
                    'root'     => $data->root,
                );
                return $info;
            }
         } else {
            throw new ConfigException('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }
    
    /*
     * SEE: https://www.dropbox.com/developers/reference/api#files-GET
     */
    public function download_file($file_id='/') {
        global $USER;
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        $params    = array();
        $options   = array('root' => 'dropbox', 'path' => $file_id);
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['contenturl'].$cloud['version'].'/files/dropbox';
            $parts = explode('/', ltrim($file_id,'/'));
            foreach($parts as $part) {
                $url .= '/'.rawurlencode($part);
            }
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array(
                'oauth_version' => '1.0',
                'oauth_nonce' => mt_rand(),
                'oauth_timestamp' => time(),
                'oauth_consumer_key' => $consumer['key'],
                'oauth_token' => $usertoken['oauth_token'],
                'oauth_signature_method' => 'HMAC-SHA1',
            );
            $params['oauth_signature'] = oauth_compute_hmac_sig($method, $url, $params, $consumer['secret'], $usertoken['oauth_token_secret']);
            $config = array(
                CURLOPT_URL => $url.'?'.oauth_http_build_query($params),
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => false,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            return $result->data;
         } else {
            throw new ConfigException('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }
    
    public function embed_file($file_id='/', $options=array()) {
        // Dropbox API doesn't support embedding of files, so:
        // Nothing to do!
    }

    /*
     * SEE: https://www.dropbox.com/developers/reference/api#shares
     */
    public function public_url($file_id='/') {
        global $USER, $THEME;
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/shares/dropbox';
            $parts = explode('/', ltrim($file_id,'/'));
            foreach($parts as $part) {
                $url .= '/'.rawurlencode($part);
            }
            $method = 'POST';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array(
                'oauth_version' => '1.0',
                'oauth_nonce' => mt_rand(),
                'oauth_timestamp' => time(),
                'oauth_consumer_key' => $consumer['key'],
                'oauth_token' => $usertoken['oauth_token'],
                'oauth_signature_method' => 'HMAC-SHA1',
                // Method specific parameters...
                'short_url' => false
            );
            $params['oauth_signature'] = oauth_compute_hmac_sig($method, $url, $params, $consumer['secret'], $usertoken['oauth_token_secret']);
            $header = array();
            $header[] = build_oauth_header($params, "Dropbox API PHP Client");
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => oauth_http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode(substr($result->data, $result->info['header_size']));
                return $data->url;
            }
         } else {
            throw new ConfigException('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }

}

?>
