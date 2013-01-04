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
 * @subpackage blocktype-skydrive
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/oauth.php');


class PluginBlocktypeSkydrive extends PluginBlocktypeCloud {

    const servicepath = 'skydrivepath';
    
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/skydrive');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/skydrive');
    }

    public static function get_categories() {
        return array('cloud');
    }

    public static function render_instance(BlockInstance $instance, $editing=false) {
        $configdata = $instance->get('configdata');
        $viewid     = $instance->get('view');
        
        $fullpath = (!empty($configdata['fullpath']) ? $configdata['fullpath'] : '0|@');
        $selected = (!empty($configdata['artefacts']) ? $configdata['artefacts'] : array());
        $display  = (!empty($configdata['display']) ? $configdata['display'] : 'list');
        
        $smarty = smarty_core();
        switch ($display) {
            case 'embed':
                $html = '';
                if (!empty($selected)) {
                    foreach ($selected as $artefact) {
                        $html .= self::embed_file($artefact);
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
        return $smarty->fetch('blocktype:skydrive:' . $display . '.tpl');
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
            'skydrivefiles' => array(
                'type'     => 'datatables',
                'title'    => get_string('selectfiles','blocktype.cloud/skydrive'),
                'service'  => 'skydrive',
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
                'title' => get_string('display','blocktype.cloud/skydrive'),
                'description' => get_string('displaydesc','blocktype.cloud/skydrive') . '<br>' . get_string('displaydesc2','blocktype.cloud/skydrive'),
                'defaultvalue' => (!empty($configdata['display']) ? hsc($configdata['display']) : 'list'),
                'options' => array(
                    'list'  => get_string('displaylist','blocktype.cloud/skydrive'),
                    //'icon'  => get_string('displayicon','blocktype.cloud/skydrive'),
                    'embed' => get_string('displayembed','blocktype.cloud/skydrive')
                ),
                'separator' => '<br />',
            ),
        );
    }

    public static function instance_config_save($values) {
        global $_SESSION;
        // Folder and file IDs (and other values) are returned as JSON/jQuery serialized string.
        // We have to parse that string and urldecode it (to correctly convert square brackets)
        // in order to get cloud folder and file IDs - they are stored in $artefacts array.
        parse_str(urldecode($values['skydrivefiles']));
        if (!isset($artefacts) || empty($artefacts)) {
            $artefacts = array();
        }
        
        $values = array(
            'title'     => $values['title'],
            'fullpath'  => $_SESSION[self::servicepath],
            'artefacts' => $artefacts,
            'display'   => $values['display'],
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
            'value' => get_string('applicationdesc', 'blocktype.cloud/skydrive', '<a href="https://manage.dev.live.com/" target="_blank">', '</a>'),
        );
        $elements['basicinformation'] = array(
            'type' => 'fieldset',
            'legend' => get_string('basicinformation', 'blocktype.cloud/skydrive'),
            'elements' => array(
                'applicationname' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationname', 'blocktype.cloud/skydrive'),
                    'defaultvalue' => get_config('sitename'),
                    'description'  => get_string('applicationnamedesc', 'blocktype.cloud/skydrive'),
                    'readonly'     => true,
                ),
                'applicationicon' => array(
                    'type'         => 'html',
                    'title'        => get_string('applicationicon', 'blocktype.cloud/skydrive'),
                    'value'        => '<table border="0"><tr style="text-align:center">
                                       <td style="vertical-align:bottom"><img src="'.get_config('wwwroot').'artefact/cloud/icons/048x048.png" border="0" style="border:1px solid #ccc"><br>48x48</td>
                                       </table>',
                    'description'  => get_string('applicationicondesc', 'blocktype.cloud/skydrive'),
                ),
                'applicationterms' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationterms', 'blocktype.cloud/skydrive'),
                    'defaultvalue' => get_config('wwwroot').'terms.php',
                    'size' => 50,
                    'readonly'     => true,
                ),
                'applicationprivacy' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationprivacy', 'blocktype.cloud/skydrive'),
                    'defaultvalue' => get_config('wwwroot').'privacy.php',
                    'size' => 50,
                    'readonly'     => true,
                ),
            )
        );
        $elements['apisettings'] = array(
            'type' => 'fieldset',
            'legend' => get_string('apisettings', 'blocktype.cloud/skydrive'),
            'elements' => array(
                'consumerkey' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumerkey', 'blocktype.cloud/skydrive'),
                    'defaultvalue' => get_config_plugin('blocktype', 'skydrive', 'consumerkey'),
                    'description'  => get_string('consumerkeydesc', 'blocktype.cloud/skydrive'),
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'consumersecret' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumersecret', 'blocktype.cloud/skydrive'),
                    'defaultvalue' => get_config_plugin('blocktype', 'skydrive', 'consumersecret'),
                    'description'  => get_string('consumersecretdesc', 'blocktype.cloud/skydrive'),
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'redirecturl' => array(
                    'type'         => 'text',
                    'title'        => get_string('redirecturl', 'blocktype.cloud/skydrive'),
                    'defaultvalue' => get_config('wwwroot'),
                    'description'  => get_string('redirecturldesc', 'blocktype.cloud/skydrive'),
                    'size' => 50,
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
        set_config_plugin('blocktype', 'skydrive', 'consumerkey', $values['consumerkey']);
        set_config_plugin('blocktype', 'skydrive', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /**************************************************
     * Methods & stuff for accessing Live Connect API *
     **************************************************/
    
    public function cloud_info() {
        return array(
            'ssl'        => true,
            'version'    => 'v5.0',
            'baseurl'    => 'https://apis.live.net/',
            //'contenturl' => 'https://apis.live.net/',
            'authurl'     => 'https://login.live.com/',
        );
    }
    
    public function consumer_tokens() {
        return array(
            'key'      => get_config_plugin('blocktype', 'skydrive', 'consumerkey'),
            'secret'   => get_config_plugin('blocktype', 'skydrive', 'consumersecret'),
            'callback' => get_config('wwwroot') . 'artefact/cloud/blocktype/skydrive/callback.php'
        );
    }
    
    public function user_tokens($userid) {
        return ArtefactTypeCloud::get_user_preferences('skydrive', $userid);
    }
    
    public function service_list() {
        global $USER;
        $consumer    = self::consumer_tokens();
        $usertoken   = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            if (isset($usertoken['refresh_token']) && !empty($usertoken['refresh_token'])) {
                return array(
                    'service_name'   => 'skydrive',
                    'service_url'    => 'http://www.live.com',
                    'service_auth'   => true,
                    'service_manage' => true,
                    //'revoke_access'  => true,
                );
            } else {
                return array(
                    'service_name'   => 'skydrive',
                    'service_url'    => 'http://www.live.com',
                    'service_auth'   => false,
                    'service_manage' => false,
                    //'revoke_access'  => false,
                );
            }
        } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }
    
    /*
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh826543.aspx
     */
    public function request_token() {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['authurl'].'oauth20_authorize.srf';
            $scopes = 'wl.signin wl.basic wl.offline_access wl.skydrive';
            $params = array(
                'client_id' => $consumer['key'],
                'scope' => $scopes,
                'response_type' => 'code',
                'redirect_uri' => $consumer['callback']
            );
            $query = oauth_http_build_query($params);
            $request_url = $url . ($query ? ('?' . $query) : '' );
            redirect($request_url);
        } else {
            throw new ConfigException('Can\'t find Live Connect consumer key and/or consumer secret.');
        }
    }

    /*
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh826543.aspx
     */
    public function access_token($oauth_code) {
        global $USER, $SESSION;
        $cloud    = PluginBlocktypeSkydrive::cloud_info();
        $consumer = PluginBlocktypeSkydrive::consumer_tokens();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['authurl'].'oauth20_token.srf';
            $method = 'POST';
            $port = $cloud['ssl'] ? '443' : '80';
            $scopes = 'wl.basic wl.offline_access wl.skydrive';
            $params = array(
                'client_id' => $consumer['key'],
                'redirect_uri' => $consumer['callback'],
                'client_secret' => $consumer['secret'],
                'code' => $oauth_code,
                'grant_type' => 'authorization_code',
                'response_type' => 'code',
                'scope' => $scopes,
            );
            $query = oauth_http_build_query($params);
            $header = array();
            $header[] = build_oauth_header($params, "Live Connect API PHP Client");
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
                $SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/skydrive'));
            }
        } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh243649.aspx#refresh
     */
    public function check_access_token() {
        global $USER, $SESSION;
        $cloud    = PluginBlocktypeSkydrive::cloud_info();
        $consumer = PluginBlocktypeSkydrive::consumer_tokens();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $usertoken = self::user_tokens($USER->get('id'));
            $tokendate = get_field('artefact', 'mtime', 'artefacttype', 'cloud', 'title', 'skydrive', 'owner', $USER->get('id'));
            // Find out when access token actually expires and take away 10 seconds
            // to avoid access token expiry problems between API calls... 
            $valid = strtotime($tokendate) + intval($usertoken['expires_in']) - 10;
            $now = time();
            // If access token is expired, than get new one using refresh token
            // save it and return it...
            if ($valid < $now) {
                $url = $cloud['authurl'].'oauth20_token.srf';
                $method = 'POST';
                $port = $cloud['ssl'] ? '443' : '80';
                //$scopes = 'wl.basic wl.offline_access wl.skydrive';
                $params   = array(
                    'client_id' => $consumer['key'],
                    'redirect_uri' => $consumer['callback'],
                    'client_secret' => $consumer['secret'],
                    'refresh_token' => $usertoken['refresh_token'],
                    'grant_type' => 'refresh_token',
                );
                $query = oauth_http_build_query($params);
                $header = array();
                $header[] = build_oauth_header($params, "Live Connect API PHP Client");
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
                    ArtefactTypeCloud::set_user_preferences('skydrive', $USER->get('id'), $prefs);
                    return $prefs['access_token'];
                } else {
                    $SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/skydrive'));
                    return null;
                }
            }
            // If access token is not expired, than return it...
            else {
                $usertoken = self::user_tokens($USER->get('id'));
                return $usertoken['access_token'];
            }
        } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('skydrive', $USER->get('id'), null);
    }
    
    /*
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh243649.aspx#signout
     */
    public function revoke_access() {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['authurl'].'oauth20_logout.srf';
            $params = array(
                'client_id' => $consumer['key'],
                'redirect_uri' => $consumer['callback']
            );
            $query = oauth_http_build_query($params);
            $request_url = $url . ($query ? ('?' . $query) : '' );
            redirect($request_url);
        } else {
            throw new ConfigException('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }
    
    /*
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh826533.aspx
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh826545.aspx#quota
     */
    public function account_info() {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/me';
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
                $url2 = $cloud['baseurl'].$cloud['version'].'/me/skydrive/quota';
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
                $quota['used'] = $quota['quota'] - $quota['available'];
                return array(
                    'service_name' => 'skydrive',
                    'service_auth' => true,
                    'user_id'      => $data['id'],
                    'user_name'    => $data['name'],
                    'user_profile' => $data['link'],
                    'space_used'   => bytes_to_size1024(floatval($quota['used'])),
                    'space_amount' => bytes_to_size1024(floatval($quota['quota'])),
                    'space_ratio'  => number_format((floatval($quota['used'])/floatval($quota['quota']))*100, 2),
                );
            } else {
                return array(
                    'service_name' => 'skydrive',
                    'service_auth' => false,
                    'user_id'      => null,
                    'user_name'    => null,
                    'user_profile' => null,
                    'space_used'   => null,
                    'space_amount' => null,
                    'space_ratio'  => null,
                );
            }
         } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }
    
    
    /*
     * This function returns list of selected files/folders which will be displayed in a view/page.
     *
     * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
     * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
     *
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh243648.aspx#folder
     *
     */
    public function get_filelist($folder_id='0', $selected=array()) {
        global $USER, $THEME;

        // Get folder contents...
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            if (strlen($folder_id) > 1) {
                $url = $cloud['baseurl'].$cloud['version'].'/'.$folder_id.'/files';
            } else {
                $url = $cloud['baseurl'].$cloud['version'].'/me/skydrive/files';
            }
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
                //log_debug($data['data']);
                $output = array(
                    'folders' => array(),
                    'files'   => array()
                );
                if (isset($data['data']) && !empty($data['data'])) {
                    foreach($data['data'] as $artefact) {
                        if (in_array($artefact['id'], $selected)) {
                            $id          = $artefact['id'];
                            $type        = $artefact['type'];
                            $icontype    = ($artefact['type'] == 'folder' ? 'folder' : 'file');
                            $icon        = $THEME->get_url('images/' . $icontype . '.gif');
                            $title       = $artefact['name'];
                            $description = $artefact['description'];
                            $size        = bytes_to_size1024($artefact['size']);
                            $created     = ($artefact['created_time'] ? format_date(strtotime($artefact['created_time']), 'strftimedaydate') : null);
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
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
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
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh243648.aspx#folder
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
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            if (strlen($folder_id) > 1) {
                $url = $cloud['baseurl'].$cloud['version'].'/'.$folder_id.'/files';
            } else {
                $url = $cloud['baseurl'].$cloud['version'].'/me/skydrive/files';
            }
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
                if (strlen($_SESSION[self::servicepath]) > 3) {
                    $type        = 'parentfolder';
                    $foldername  = get_string('parentfolder', 'artefact.file');
                    $title       = '<a class="changefolder" href="javascript:void(0)" id="parent" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '"><img src="' . get_config('wwwroot') . 'artefact/cloud/theme/raw/static/images/parentfolder.png"></a>';
                    $output['aaData'][] = array('', $title, '', $type);
                }
                if (!empty($data['data'])) {
                    foreach($data['data'] as $artefact) {
                        $id           = $artefact['id'];
                        $type         = ($artefact['type'] == 'folder' ? 'folder' : 'file');
                        $icon         = '<img src="' . $THEME->get_url('images/' . $type . '.gif') . '">';
                        // Get artefactname by removing parent path from beginning...
                        $artefactname = $artefact['name'];
                        if ($artefact['type'] == 'folder') {
                            $title    = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $artefactname) . '">' . $artefactname . '</a>';
                        } else {
                            $title    = '<a class="filedetails" href="details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $artefactname) . '">' . $artefactname . '</a>';
                        }
                        $controls = '';
                        $selected = (in_array($id, $artefacts) ? ' checked' : '');
                        if ($artefact['type'] == 'folder') {
                            if ($selectFolders) {
                                $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                            }
                        } else {
                            if ($selectFiles && !$manageButtons) {
                                $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                            } elseif ($manageButtons) {
                                $controls  = '<a class="btn" href="preview.php?id=' . $id . '" target="_blank">' . get_string('preview', 'artefact.cloud') . '</a>';
                                if ($artefact['type'] == 'file') {
                                    $controls .= '<a class="btn" href="download.php?id=' . $id . '&save=1">' . get_string('save', 'artefact.cloud') . '</a>';
                                    $controls .= '<a class="btn" href="download.php?id=' . $id . '">' . get_string('download', 'artefact.cloud') . '</a>';
                                }
                            }
                        }
                        $output['aaData'][] = array($icon, $title, $controls, $type);
                        $count++;
                    }
                }
                $output['iTotalRecords'] = $count;
                $output['iTotalDisplayRecords'] = $count;
                return json_encode($output);
            } else {
                return array();
            }
         } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh243648.aspx#folder
     */
    public function get_folder_info($folder_id=0) {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            if (strlen($folder_id) > 1) {
                $url = $cloud['baseurl'].$cloud['version'].'/'.$folder_id;
            } else {
                $url = $cloud['baseurl'].$cloud['version'].'/me/skydrive';
            }
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
                    'name'        => $data['name'],
                    'shared'      => implode(', ', $data['shared_with']),
                    'preview'     => $data['link'],
                    'description' => $data['description'],
                    'created'     => ($data['created_time'] ? format_date(strtotime($data['created_time']), 'strfdaymonthyearshort') : null),
                    'updated'     => ($data['updated_time'] ? format_date(strtotime($data['updated_time']), 'strfdaymonthyearshort') : null),
                );
                return $info;
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh243648.aspx#file
     */
    public function get_file_info($file_id=0) {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/'.$file_id;
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
                    'name'        => $data['name'],
                    'type'        => $data['type'],
                    'bytes'       => $data['size'],
                    'size'        => bytes_to_size1024($data['size']),
                    'shared'      => implode(', ', $data['shared_with']), 
                    'preview'     => $data['link'],
                    'description' => $data['description'],
                    'created'     => ($data['created_time'] ? format_date(strtotime($data['created_time']), 'strfdaymonthyearshort') : null),
                    'updated'     => ($data['updated_time'] ? format_date(strtotime($data['updated_time']), 'strfdaymonthyearshort') : null),
                    'parent'      => $data['parent_id'],
                );
                return $info;
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh243648.aspx#file
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh826531.aspx#downloading_files
     */
    public function download_file($file_id=0) {
        global $USER;
        $cloud       = self::cloud_info();
        $consumer    = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            // Construct download, to download file...
            $download_url = $cloud['baseurl'] . $cloud['version'] . '/' . $file_id . '/content?access_token=' . str_replace('%7E', '~', rawurlencode($accesstoken));
            $result = '';
            $header = array();
            $header[] = 'User-Agent: Live Connect API PHP Client';
            $header[] = 'Host: apis.live.net';
            $port = $cloud['ssl'] ? '443' : '80';   
            $ch = curl_init($download_url);
            curl_setopt($ch, CURLOPT_PORT, $port);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, get_config('docroot').'artefact/cloud/cert/cacert.crt');
            // Live Connect API request returns 'Location' inside response header.
            // Follow 'Location' in response header to get the actual file content.
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh243648.aspx#file
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh826531.aspx#file_links
     */
    public function embed_file($file_id=0, $options=array()) {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/'.$file_id.'/embed';
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
                if (isset($data['embed_html']) && !empty($data['embed_html'])) {
                    return $data['embed_html'];
                } else {
                    return null;
                }
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: http://msdn.microsoft.com/en-us/library/live/hh243648.aspx#file
     */
    public function public_url($file_id=0) {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        // Check if access token is still valid...
        $accesstoken = self::check_access_token();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/'.$file_id;
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
                return $data['link'];
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }

}

?>
