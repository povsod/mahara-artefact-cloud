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
 * @subpackage blocktype-googledrive
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/oauth.php');


class PluginBlocktypeGoogledrive extends PluginBlocktypeCloud {

    const servicepath = 'googledrivepath';
    
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/googledrive');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/googledrive');
    }

    public static function get_categories() {
        return array('cloud');
    }

    public static function get_instance_config_javascript() {
        return array('js/configform.js');
    }

    public static function render_instance(BlockInstance $instance, $editing=false) {
        $configdata = $instance->get('configdata');
        $viewid     = $instance->get('view');
        
        $fullpath = (!empty($configdata['fullpath']) ? $configdata['fullpath'] : '0|@');
        $selected = (!empty($configdata['artefacts']) ? $configdata['artefacts'] : array());
        $display  = (!empty($configdata['display']) ? $configdata['display'] : 'list');
        $width    = (!empty($configdata['width']) ? $configdata['width'] : 480);
        $height   = (!empty($configdata['height']) ? $configdata['height'] : 360);
        
        $smarty = smarty_core();
        switch ($display) {
            case 'embed':
                $html = '';
                $size = array('width' => $width, 'height' => $height);
                if (!empty($selected)) {
                    foreach ($selected as $artefact) {
                        $html .= self::embed_file($artefact, $size);
                    }
                }
                $smarty->assign('embed', $html);
                break;
            case 'list':
            default:
                list($folder, $path) = explode('|', $fullpath, 2);
                $data = self::get_filelist($folder, $selected);
                $smarty->assign('folders', $data['folders']);
                $smarty->assign('files', $data['files']);
        }
        $smarty->assign('viewid', $viewid);
        return $smarty->fetch('blocktype:googledrive:' . $display . '.tpl');
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
            'googledrivefiles' => array(
                'type'     => 'datatables',
                'title'    => get_string('selectfiles','blocktype.cloud/googledrive'),
                'service'  => 'googledrive',
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
            'display' => array(
                'type' => 'radio',
                'title' => get_string('display','blocktype.cloud/googledrive'),
                //'description' => get_string('displaydesc','blocktype.cloud/googledrive') . '<br>' . get_string('displaydesc2','blocktype.cloud/googledrive'),
                'defaultvalue' => (!empty($configdata['display']) ? hsc($configdata['display']) : 'list'),
                'options' => array(
                    'list'  => get_string('displaylist','blocktype.cloud/googledrive'),
                    //'icon'  => get_string('displayicon','blocktype.cloud/googledrive'),
                    'embed' => get_string('displayembed','blocktype.cloud/googledrive')
                ),
                'separator' => '<br />',
            ),
            'embedoptions' => array(
                'type'         => 'fieldset',
                'collapsible'  => true,
                'collapsed'    => true,
                'legend'       => get_string('embedoptions', 'blocktype.cloud/googledrive'),
                'elements'     => array(
                    'size' => array(
                        'type' => 'radio',
                        'labelhtml' => get_string('size','blocktype.cloud/googledrive'),
                        'defaultvalue' => (!empty($configdata['size']) ? hsc($configdata['size']) : 'S'),
                        'options' => array(
                            'S' => get_string('sizesmall','blocktype.cloud/googledrive'),
                            'M' => get_string('sizemedium','blocktype.cloud/googledrive'),
                            'L' => get_string('sizelarge','blocktype.cloud/googledrive'),
                            'C' => get_string('sizecustom','blocktype.cloud/googledrive'),
                        ),
                    ),
                    'width' => array(
                        'type'  => 'text',
                        'labelhtml' => get_string('width', 'blocktype.cloud/googledrive'),
                        'size' => 3,
                        'defaultvalue' => (!empty($configdata['width']) ? hsc($configdata['width']) : 480),
                        'rules' => array('minvalue' => 1, 'maxvalue' => 2000),
                    ),
                    'height' => array(
                        'type'  => 'text',
                        'labelhtml' => get_string('height', 'blocktype.cloud/googledrive'),
                        'size' => 3,
                        'defaultvalue' => (!empty($configdata['height']) ? hsc($configdata['height']) : 360),
                        'rules' => array('minvalue' => 1, 'maxvalue' => 2000),
                    ),
                ),
            ),
        );
    }

    public static function instance_config_save($values) {
        global $_SESSION;
        // Folder and file IDs (and other values) are returned as JSON/jQuery serialized string.
        // We have to parse that string and urldecode it (to correctly convert square brackets)
        // in order to get cloud folder and file IDs - they are stored in $artefacts array.
        parse_str(urldecode($values['googledrivefiles']));
        if (!isset($artefacts) || empty($artefacts)) {
            $artefacts = array();
        }
        
        $values = array(
            'title'     => $values['title'],
            'fullpath'  => $_SESSION[self::servicepath],
            'artefacts' => $artefacts,
            'display'   => $values['display'],
            'size'      => $values['size'],
            'width'     => $values['width'],
            'height'    => $values['height'],
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
            'value' => get_string('applicationdesc', 'blocktype.cloud/googledrive', '<a href="https://code.google.com/apis/console/" target="_blank">', '</a>'),
        );
        $elements['brandinginformation'] = array(
            'type' => 'fieldset',
            'legend' => get_string('brandinginformation', 'blocktype.cloud/googledrive'),
            'elements' => array(
                'applicationname' => array(
                    'type'         => 'text',
                    'title'        => get_string('productname', 'blocktype.cloud/googledrive'),
                    'defaultvalue' => get_config('sitename'),
                    'description'  => get_string('productnamedesc', 'blocktype.cloud/googledrive'),
                    'readonly'     => true,
                ),
                'applicationicon' => array(
                    'type'         => 'html',
                    'title'        => get_string('productlogo', 'blocktype.cloud/googledrive'),
                    'value'        => '<table border="0"><tr style="text-align:center">
                                       <td style="vertical-align:bottom"><img src="'.get_config('wwwroot').'artefact/cloud/icons/120x060.png" border="0" style="border:1px solid #ccc"><br>120x60</td>
                                       </table>',
                    'description'  => get_string('productlogodesc', 'blocktype.cloud/googledrive'),
                ),
            )
        );
        $elements['webappsclientid'] = array(
            'type' => 'fieldset',
            'legend' => get_string('webappsclientid', 'blocktype.cloud/googledrive'),
            'elements' => array(
                'consumerkey' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumerkey', 'blocktype.cloud/googledrive'),
                    'defaultvalue' => get_config_plugin('blocktype', 'googledrive', 'consumerkey'),
                    'description'  => get_string('consumerkeydesc', 'blocktype.cloud/googledrive'),
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'consumersecret' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumersecret', 'blocktype.cloud/googledrive'),
                    'defaultvalue' => get_config_plugin('blocktype', 'googledrive', 'consumersecret'),
                    'description'  => get_string('consumersecretdesc', 'blocktype.cloud/googledrive'),
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'redirecturl' => array(
                    'type'         => 'text',
                    'title'        => get_string('redirecturl', 'blocktype.cloud/googledrive'),
                    'defaultvalue' => get_config('wwwroot') . 'artefact/cloud/blocktype/googledrive/callback.php',
                    'description'  => get_string('redirecturldesc', 'blocktype.cloud/googledrive'),
                    'size' => 70,
                    'readonly' => true,
                    'rules' => array('required' => true),
                ),
            )
        );
        return array(
            'elements' => $elements,
        );

    }

    public static function save_config_options($values) {
        set_config_plugin('blocktype', 'googledrive', 'consumerkey', $values['consumerkey']);
        set_config_plugin('blocktype', 'googledrive', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /**************************************************
     * Methods & stuff for accessing Google Drive API *
     **************************************************/
    
    public function cloud_info() {
        return array(
            'ssl'        => true,
            'version'    => 'v2',
            'baseurl'    => 'https://www.googleapis.com/',
            'driveurl'   => 'https://www.googleapis.com/drive/',
            'authurl'    => 'https://accounts.google.com/o/oauth2/',
        );
    }
    
    public function consumer_tokens() {
        return array(
            'key'      => get_config_plugin('blocktype', 'googledrive', 'consumerkey'),
            'secret'   => get_config_plugin('blocktype', 'googledrive', 'consumersecret'),
            'callback' => get_config('wwwroot') . 'artefact/cloud/blocktype/googledrive/callback.php'
        );
    }
    
    public function user_tokens($userid) {
        return ArtefactTypeCloud::get_user_preferences('googledrive', $userid);
    }
    
    public function service_list() {
        global $USER;
        $consumer    = self::consumer_tokens();
        $usertoken   = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            if (isset($usertoken['refresh_token']) && !empty($usertoken['refresh_token'])) {
                return array(
                    'service_name'   => 'googledrive',
                    'service_url'    => 'http://drive.google.com',
                    'service_auth'   => true,
                    'service_manage' => true,
                    //'revoke_access'  => true,
                );
            } else {
                return array(
                    'service_name'   => 'googledrive',
                    'service_url'    => 'http://drive.google.com',
                    'service_auth'   => false,
                    'service_manage' => false,
                    //'revoke_access'  => false,
                );
            }
        } else {
            throw new ConfigException('Can\'t find Google Drive API consumer ID and/or consumer secret.');
        }
    }
    
    /*
     * SEE: https://developers.google.com/accounts/docs/OAuth2WebServer#formingtheurl
     */
    public function request_token() {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['authurl'].'auth';
            $scopes = 'https://www.googleapis.com/auth/drive '
                    . 'https://www.googleapis.com/auth/userinfo.profile '
                    . 'https://www.googleapis.com/auth/userinfo.email';
            $params = array(
                'client_id' => $consumer['key'],
                'scope' => $scopes,
                'response_type' => 'code',
                'access_type' => 'offline',
                'redirect_uri' => $consumer['callback']
            );
            $query = oauth_http_build_query($params);
            $request_url = $url . ($query ? ('?' . $query) : '' );
            redirect($request_url);
        } else {
            throw new ConfigException('Can\'t find Google Drive API consumer key and/or consumer secret.');
        }
    }

    /*
     * SEE: https://developers.google.com/accounts/docs/OAuth2WebServer#handlingtheresponse
     */
    public function access_token($oauth_code) {
        global $USER, $SESSION;
        $cloud    = PluginBlocktypeGoogledrive::cloud_info();
        $consumer = PluginBlocktypeGoogledrive::consumer_tokens();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['authurl'].'token';
            $method = 'POST';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array(
                'client_id' => $consumer['key'],
                'redirect_uri' => $consumer['callback'],
                'client_secret' => $consumer['secret'],
                'code' => $oauth_code,
                'grant_type' => 'authorization_code',
            );
            $query = oauth_http_build_query($params);
            $header = array();
            $header[] = build_oauth_header($params, "Google Drive API PHP Client");
            $header[] = 'Content-Length: ' . strlen($query);
            $header[] = 'Content-Type: application/x-www-form-urlencoded';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $query,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode(substr($result->data, $result->info['header_size']), true);
                return $data;
            } else {
                $SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/googledrive'));
            }
        } else {
            throw new ConfigException('Can\'t find Google Drive API consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: https://developers.google.com/accounts/docs/OAuth2WebServer#refresh
     */
    public function check_access_token() {
        global $USER, $SESSION;
        $cloud    = PluginBlocktypeGoogledrive::cloud_info();
        $consumer = PluginBlocktypeGoogledrive::consumer_tokens();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $usertoken = self::user_tokens($USER->get('id'));
            $tokendate = get_field('artefact', 'mtime', 'artefacttype', 'cloud', 'title', 'googledrive', 'owner', $USER->get('id'));
            // Find out when access token actually expires and take away 10 seconds
            // to avoid access token expiry problems between API calls... 
            $valid = strtotime($tokendate) + intval($usertoken['expires_in']) - 10;
            $now = time();
            // If access token is expired, than get new one using refresh token
            // save it and return it...
            if ($valid < $now) {
                $url = $cloud['authurl'].'token';
                $method = 'POST';
                $port = $cloud['ssl'] ? '443' : '80';
                $params = array(
                    'client_id' => $consumer['key'],
                    'client_secret' => $consumer['secret'],
                    'refresh_token' => $usertoken['refresh_token'],
                    'grant_type' => 'refresh_token',
                );
                $query = oauth_http_build_query($params);
                $header = array();
                $header[] = build_oauth_header($params, "Google Drive API PHP Client");
                $header[] = 'Content-Length: ' . strlen($query);
                $header[] = 'Content-Type: application/x-www-form-urlencoded';
                $config = array(
                    CURLOPT_URL => $url,
                    CURLOPT_PORT => $port,
                    CURLOPT_HEADER => true,
                    CURLOPT_HTTPHEADER => $header,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $query,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
                );
                $result = mahara_http_request($config);
                if ($result->info['http_code'] == 200 && !empty($result->data)) {
                    $prefs = json_decode(substr($result->data, $result->info['header_size']), true);
                    // Request for new access_token doesn't return refresh_token at all!
                    // Add refresh_token so we'll be able to get new access_token in the future...
                    $prefs = array_merge($prefs, array('refresh_token' => $usertoken['refresh_token']));
                    ArtefactTypeCloud::set_user_preferences('googledrive', $USER->get('id'), $prefs);
                    return $prefs['access_token'];
                } else {
                    $SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/googledrive'));
                    return null;
                }
            }
            // If access token is not expired, than return it...
            else {
                $usertoken = self::user_tokens($USER->get('id'));
                return $usertoken['access_token'];
            }
        } else {
            throw new ConfigException('Can\'t find Google Drive API consumer ID and/or consumer secret.');
        }
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('googledrive', $USER->get('id'), null);
    }
    
    /*
     * SEE: https://developers.google.com/accounts/docs/OAuth2WebServer#tokenrevoke
     */
    public function revoke_access() {
        global $USER;
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            // Construct revoke url, for revokin access...
            $revoke_url = $cloud['authurl'] . 'revoke?token=' . str_replace('%7E', '~', rawurlencode($usertoken['refresh_token']));
            $port = $cloud['ssl'] ? '443' : '80';
            $ch = curl_init($revoke_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_PORT, $port);
            curl_setopt($ch, CURLOPT_POST, false);
            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            throw new ConfigException('Can\'t find Google Drive API consumer key and/or consumer secret.');
        }
    }
    
    /*
     * SEE: https://developers.google.com/accounts/docs/OAuth2Login#userinfocall
     * SEE: https://developers.google.com/drive/v2/reference/about/get (quota and other user info)
     */
    public function account_info() {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].'oauth2/v1/userinfo';
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('access_token' => $accesstoken);
            $config = array(
                CURLOPT_URL => $url.'?'.oauth_http_build_query($params),
                CURLOPT_PORT => $port,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode($result->data, true);
                // Get user's quota information...
                $url2 = $cloud['driveurl'].$cloud['version'].'/about';
                $config2 = array(
                    CURLOPT_URL => $url2.'?'.oauth_http_build_query($params),
                    CURLOPT_PORT => $port,
                    CURLOPT_POST => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
                );
                $result2 = mahara_http_request($config2);
                $quota = json_decode($result2->data, true);
                return array(
                    'service_name' => 'googledrive',
                    'service_auth' => true,
                    'user_id'      => $data['id'],
                    'user_name'    => $data['name'],
                    'user_email'   => $data['email'],
                    'user_profile' => $data['link'],
                    'space_used'   => bytes_to_size1024(floatval($quota['quotaBytesUsed'])),
                    'space_amount' => bytes_to_size1024(floatval($quota['quotaBytesTotal'])),
                    'space_ratio'  => number_format((floatval($quota['quotaBytesUsed'])/floatval($quota['quotaBytesTotal']))*100, 2),
                );
            } else {
                return array(
                    'service_name' => 'googledrive',
                    'service_auth' => false,
                    'user_id'      => null,
                    'user_name'    => null,
                    'user_email'   => null,
                    'user_profile' => null,
                    'space_used'   => null,
                    'space_amount' => null,
                    'space_ratio'  => null,
                );
            }
         } else {
            throw new ConfigException('Can\'t find Google Drive API consumer ID and/or consumer secret.');
        }
    }
    
    
    /*
     * This function returns list of selected files/folders which will be displayed in a view/page.
     *
     * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
     * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
     *
     * SEE: https://developers.google.com/drive/v2/reference/files/list ???
     *
     */
    public function get_filelist($folder_id='root', $selected=array()) {
        global $USER, $THEME;

        // Get folder contents...
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['driveurl'].$cloud['version'].'/files';
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('access_token' => $accesstoken);
            $config = array(
                CURLOPT_URL => $url.'?'.oauth_http_build_query($params),
                CURLOPT_PORT => $port,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode($result->data, true);
                $output = array(
                    'folders' => array(),
                    'files'   => array()
                );
                if (isset($data['items']) && !empty($data['items'])) {
                    foreach($data['items'] as $artefact) {
                        if (in_array($artefact['id'], $selected)) {
                            $id           = $artefact['id'];
                            $type         = ($artefact['mimeType'] == 'application/vnd.google-apps.folder' ? 'folder' : 'file');
                            $icontype     = ($artefact['mimeType'] == 'application/vnd.google-apps.folder' ? 'folder' : 'file');
                            $icon         = $THEME->get_url('images/' . $icontype . '.gif');
                            $artefactname = $artefact['title'];
                            // Add extension from Google Docs file MIME Type...
                            if (($type == 'file') && substr($artefact['mimeType'], 0, 27) == 'application/vnd.google-apps') {
                                $artefactname .= '.' . strrev(strtok(strrev($artefact['mimeType']), '.'));
                            }
                            $title        = $artefactname;
                            $description  = (!empty($artefact['description']) ? $artefact['description'] : '');
                            $size         = ($artefact['quotaBytesUsed'] > 0 ? bytes_to_size1024($artefact['quotaBytesUsed']) : '-');
                            $created      = ($artefact['createdDate'] ? format_date(strtotime($artefact['createdDate']), 'strftimedaydate') : null);
                            if ($type == 'folder') {
                                $output['folders'][] = array('iconsrc' => $icon, 'id' => $id, 'type' => $type, 'title' => $title, 'description' => $description, 'size' => $size, 'ctime' => $created);
                            } else {
                                $output['files'][] = array('iconsrc' => $icon, 'id' => $id, 'type' => $type, 'title' => $title, 'description' => $description, 'size' => $size, 'ctime' => $created);
                            }
                        }
                    }
                }                    
                return $output;
            } else {
                return array();
            }
         } else {
            throw new ConfigException('Can\'t find Google Drive API consumer ID and/or consumer secret.');
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
     * SEE: https://developers.google.com/drive/v2/reference/files/list
     *
     */
    public function get_folder_content($folder_id='root', $options, $block=0, $fullpath='root|@') {
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
            if (strlen($fullpath) > 6) {
                list($current, $path) = explode('|', $fullpath, 2);
                $_SESSION[self::servicepath] = $current . '|' . $path;
                $folder_id = $current;
            } else {
                // Full path equals path to root folder
                $_SESSION[self::servicepath] = 'root|@';
                $folder_id = 'root';
            }
        } else {
            if ($folder_id != 'parent') {
                // Go to child folder...
                if (strlen($folder_id) > 4) {
                    list($current, $path) = explode('|', $_SESSION[self::servicepath], 2);
                    if ($current != $folder_id) {
                        $_SESSION[self::servicepath] = $folder_id . '|' . $_SESSION[self::servicepath];
                    }
                }
                // Go to root folder...
                else {
                    $_SESSION[self::servicepath] = 'root|@';
                }
            } else {
                // Go to parent folder...
                if (strlen($_SESSION[self::servicepath]) > 6) {
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
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['driveurl'].$cloud['version'].'/files';
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('access_token' => $accesstoken);
            $config = array(
                CURLOPT_URL => $url.'?'.oauth_http_build_query($params),
                CURLOPT_PORT => $port,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode($result->data, true);
                $output = array();
                $count = 0;
                // Add 'parent' row entry to jQuery Datatable...
                if (strlen($_SESSION[self::servicepath]) > 6) {
                    $type        = 'parentfolder';
                    $foldername  = get_string('parentfolder', 'artefact.file');
                    $title       = '<a class="changefolder" href="javascript:void(0)" id="parent" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '"><img src="' . get_config('wwwroot') . 'artefact/cloud/theme/raw/static/images/parentfolder.png"></a>';
                    $output['aaData'][] = array('', $title, '', $type);
                }
                if (!empty($data['items'])) {
                    foreach($data['items'] as $artefact) {
                        if ($folder_id == 'root') {
                            if (!array_key_exists('explicitlyTrashed', $artefact) && !empty($artefact['parents']) && $artefact['parents'][0]['isRoot'] == true) {
                                $id           = $artefact['id'];
                                $type         = ($artefact['mimeType'] == 'application/vnd.google-apps.folder' ? 'folder' : 'file');
                                $icon         = '<img src="' . $THEME->get_url('images/' . $type . '.gif') . '">';
                                // Get artefactname by removing parent path from beginning...
                                $artefactname = $artefact['title'];
                                // Add extension from Google Docs file MIME Type...
                                if (($type == 'file') && substr($artefact['mimeType'], 0, 27) == 'application/vnd.google-apps') {
                                    $artefactname .= '.' . strrev(strtok(strrev($artefact['mimeType']), '.'));
                                }
                                if ($artefact['mimeType'] == 'application/vnd.google-apps.folder') {
                                    $title    = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $artefactname) . '">' . $artefactname . '</a>';
                                } else {
                                    $title    = '<a class="filedetails" href="details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $artefactname) . '">' . $artefactname . '</a>';
                                }
                                $controls = '';
                                $selected = (in_array($id, $artefacts) ? ' checked' : '');
                                if ($artefact['mimeType'] == 'application/vnd.google-apps.folder') {
                                    if ($selectFolders) {
                                        $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                                    }
                                } else {
                                    if ($selectFiles && !$manageButtons) {
                                        $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                                    } elseif ($manageButtons) {
                                        $controls  = '<a class="btn" href="preview.php?id=' . $id . '" target="_blank">' . get_string('preview', 'artefact.cloud') . '</a>';
                                        if ($artefact['mimeType'] != 'application/vnd.google-apps.folder') {
                                            if (isset($artefact['downloadUrl'])) {
                                                $controls .= '<a class="btn" href="download.php?id=' . $id . '&save=1">' . get_string('save', 'artefact.cloud') . '</a>';
                                                $controls .= '<a class="btn" href="download.php?id=' . $id . '">' . get_string('download', 'artefact.cloud') . '</a>';
                                            } else {
                                                $controls .= '<a class="btn" href="export.php?id=' . $id . '&save=1">' . get_string('save', 'artefact.cloud') . '</a>';
                                                $controls .= '<a class="btn" href="export.php?id=' . $id . '">' . get_string('download', 'artefact.cloud') . '</a>';
                                            }
                                        }
                                    }
                                }
                                $output['aaData'][] = array($icon, $title, $controls, $type);
                                $count++;
                            }
                        } else {
                            if (!array_key_exists('explicitlyTrashed', $artefact) && !empty($artefact['parents']) && $artefact['parents'][0]['id'] == $folder_id) {
                                $id           = $artefact['id'];
                                $type         = ($artefact['mimeType'] == 'application/vnd.google-apps.folder' ? 'folder' : 'file');
                                $icon         = '<img src="' . $THEME->get_url('images/' . $type . '.gif') . '">';
                                // Get artefactname by removing parent path from beginning...
                                $artefactname = $artefact['title'];
                                // Add extension from Google Docs file MIME Type...
                                if (($type == 'file') && substr($artefact['mimeType'], 0, 27) == 'application/vnd.google-apps') {
                                    $artefactname .= '.' . strrev(strtok(strrev($artefact['mimeType']), '.'));
                                }
                                if ($artefact['mimeType'] == 'application/vnd.google-apps.folder') {
                                    $title    = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $artefactname) . '">' . $artefactname . '</a>';
                                } else {
                                    $title    = '<a class="filedetails" href="details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $artefactname) . '">' . $artefactname . '</a>';
                                }
                                $controls = '';
                                $selected = (in_array($id, $artefacts) ? ' checked' : '');
                                if ($artefact['mimeType'] == 'application/vnd.google-apps.folder') {
                                    if ($selectFolders) {
                                        $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                                    }
                                } else {
                                    if ($selectFiles && !$manageButtons) {
                                        $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                                    } elseif ($manageButtons) {
                                        $controls  = '<a class="btn" href="preview.php?id=' . $id . '" target="_blank">' . get_string('preview', 'artefact.cloud') . '</a>';
                                        if ($artefact['mimeType'] != 'application/vnd.google-apps.folder') {
                                            if (isset($artefact['downloadUrl'])) {
                                                $controls .= '<a class="btn" href="download.php?id=' . $id . '&save=1">' . get_string('save', 'artefact.cloud') . '</a>';
                                                $controls .= '<a class="btn" href="download.php?id=' . $id . '">' . get_string('download', 'artefact.cloud') . '</a>';
                                            } else {
                                                $controls .= '<a class="btn" href="export.php?id=' . $id . '&save=1">' . get_string('save', 'artefact.cloud') . '</a>';
                                                $controls .= '<a class="btn" href="export.php?id=' . $id . '">' . get_string('download', 'artefact.cloud') . '</a>';
                                            }
                                        }
                                    }
                                }
                                $output['aaData'][] = array($icon, $title, $controls, $type);
                                $count++;
                            }
                        }
                    }
                }
                $output['iTotalRecords'] = $count;
                $output['iTotalDisplayRecords'] = $count;
                return json_encode($output);
            } else {
                return array();
            }
         } else {
            throw new ConfigException('Can\'t find Google Drive API consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: https://developers.google.com/drive/v2/reference/files/get
     */
    public function get_folder_info($folder_id='root') {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['driveurl'].$cloud['version'].'/files/'.$folder_id;
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('access_token' => $accesstoken);
            $config = array(
                CURLOPT_URL => $url.'?'.oauth_http_build_query($params),
                CURLOPT_PORT => $port,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode($result->data, true);
                $info = array(
                    'id'          => $data['id'],
                    'name'        => $data['title'],
                    'type'        => $data['mimeType'],
                    'shared'      => $data['writersCanShare'],
                    'preview'     => null, //$data['link'],
                    'description' => ($data['description'] ? $data['description'] : null),
                    'created'     => ($data['createdDate'] ? format_date(strtotime($data['createdDate']), 'strfdaymonthyearshort') : null),
                    'updated'     => ($data['modifiedDate'] ? format_date(strtotime($data['modifiedDate']), 'strfdaymonthyearshort') : null),
                );
                return $info;
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find Google Drive API consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: https://developers.google.com/drive/v2/reference/files/get
     */
    public function get_file_info($file_id='root') {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['driveurl'].$cloud['version'].'/files/'.$file_id;
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('access_token' => $accesstoken);
            $config = array(
                CURLOPT_URL => $url.'?'.oauth_http_build_query($params),
                CURLOPT_PORT => $port,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode($result->data, true);
                $info = array(
                    'id'          => $data['id'],
                    'name'        => $data['title'],
                    'type'        => $data['mimeType'],
                    'bytes'       => ($data['quotaBytesUsed'] > 0 ? $data['quotaBytesUsed'] : '-'),
                    'size'        => ($data['quotaBytesUsed'] > 0 ? bytes_to_size1024($data['quotaBytesUsed']) : '-'),
                    'shared'      => $data['writersCanShare'], 
                    'preview'     => $data['alternateLink'],
                    'description' => (isset($data['description']) ? $data['description'] : null),
                    'created'     => (isset($data['createdDate']) ? format_date(strtotime($data['createdDate']), 'strfdaymonthyearshort') : null),
                    'updated'     => (isset($data['modifiedDate']) ? format_date(strtotime($data['modifiedDate']), 'strfdaymonthyearshort') : null),
                    'parent'      => $data['parents'][0]['id'],
                    'download'    => (isset($data['downloadUrl']) ? $data['downloadUrl'] : null),
                    'export'      => (isset($data['exportLinks']) ? $data['exportLinks'] : array()),
                );
                return $info;
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find Google Drive API consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: https://developers.google.com/drive/v2/reference/files
     */
    public function download_file($file_id=0) {
        global $USER;
        $cloud       = self::cloud_info();
        $consumer    = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['driveurl'].$cloud['version'].'/files/'.$file_id;
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('access_token' => $accesstoken);
            $config = array(
                CURLOPT_URL => $url.'?'.oauth_http_build_query($params),
                CURLOPT_PORT => $port,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode($result->data, true);
                $sign = (strpos($data['downloadUrl'], '?') == false ? '?' : '&');
                $download_url = $data['downloadUrl'] . $sign . 'access_token=' . str_replace('%7E', '~', rawurlencode($accesstoken));
                $result = '';
                $port = $cloud['ssl'] ? '443' : '80';
                $ch = curl_init($download_url);
                curl_setopt($ch, CURLOPT_PORT, $port);
                curl_setopt($ch, CURLOPT_POST, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_CAINFO, get_config('docroot').'artefact/cloud/cert/cacert.crt');
                // Google Drive API request returns 'Location' inside response header.
                // Follow 'Location' in response header to get the actual file content.
                $result = curl_exec($ch);
                curl_close($ch);
                return $result;
            }
        } else {
            throw new ConfigException('Can\'t find Google Drive API consumer ID and/or consumer secret.');
        }
    }

    /*
     * Export and save native GoogleDocs file into selected file format (MIME type).
     *
     * SEE: https://developers.google.com/drive/v2/reference/files
     */
    public function export_file($export_url) {
        global $USER;
        $cloud       = self::cloud_info();
        $consumer    = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $sign = (strpos($export_url, '?') == false ? '?' : '&');
            $download_url = $export_url . $sign . 'access_token=' . str_replace('%7E', '~', rawurlencode($accesstoken));
            $result = '';
            $port = $cloud['ssl'] ? '443' : '80';
            $ch = curl_init($download_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_PORT, $port);
            curl_setopt($ch, CURLOPT_POST, false);
            // Google Drive API request returns 'Location' inside response header.
            // Follow 'Location' in response header to get the actual file content.
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        } else {
            throw new ConfigException('Can\'t find Google Drive API consumer ID and/or consumer secret.');
        }
    }

    /*
    public static function get_embeddable_mimetypes() {
        return array(
            'application/vnd.google-apps.document',
            'application/vnd.google-apps.drawing',
            'application/vnd.google-apps.form',
            'application/vnd.google-apps.presentation',
            'application/vnd.google-apps.spreadsheet',
        );
    }
    */

    /*
     * SEE: ?
     * SEE: https://developers.google.com/drive/v2/reference/files (bottom of page - embedLink!)
     *
     *      Officially Google Drive API can't handle embedding of files, it can only return 'embedLink' for Google Docs files.
     *      Embed code needs to be constructed separately, depending on 'mimeType' of each Google Docs file.
     *      Embedding of other files, uploaded to Google Drive is in general not supported.
     */
    public function embed_file($file_id=0, $options=array('width' => 480, 'height' => 360)) {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['driveurl'].$cloud['version'].'/files/'.$file_id;
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('access_token' => $accesstoken);
            $config = array(
                CURLOPT_URL => $url.'?'.oauth_http_build_query($params),
                CURLOPT_PORT => $port,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode($result->data, true);
                if (isset($data['embedLink']) && !empty($data['embedLink'])) {
                    return '<iframe src="' . $data['embedLink'] . '" width="' . $options['width'] . '" height="' . $options['height'] . '" frameborder="0"></iframe>';
                } else {
                    // For non Google Docs files 'alternateLink' is returned instead of 'embedLink'.
                    // Replace '/edit' at the end of that link with '/preview'...
                    // SEE: http://stackoverflow.com/questions/12094932/change-alternatelink-edit-to-embedlink-preview
                    $embedLink = str_replace("/edit", "/preview", $data['alternateLink']);
                    return '<iframe src="' . $embedLink . '" width="' . $options['width'] . '" height="' . $options['height'] . '" frameborder="0"></iframe>';
                }
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find Google Drive API consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: https://developers.google.com/drive/v2/reference/files
     */
    public function public_url($file_id='root') {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['driveurl'].$cloud['version'].'/files/'.$file_id;
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('access_token' => $accesstoken);
            $config = array(
                CURLOPT_URL => $url.'?'.oauth_http_build_query($params),
                CURLOPT_PORT => $port,
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode($result->data, true);
                if (isset($data['embedLink']) && !empty($data['embedLink'])) {
                    return $data['embedLink'];
                } else {
                    // For non Google Docs files 'alternateLink' is returned instead of 'embedLink'.
                    // Replace '/edit' at the end of that link with '/preview'...
                    // SEE: http://stackoverflow.com/questions/12094932/change-alternatelink-edit-to-embedlink-preview
                    return str_replace("/edit", "/preview", $data['alternateLink']);
                }
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find Google Drive API consumer ID and/or consumer secret.');
        }
    }

}

?>
