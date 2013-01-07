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
 * @subpackage blocktype-soundcloud
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/oauth.php');


class PluginBlocktypeSoundcloud extends PluginBlocktypeCloud {

    const servicepath = 'soundcloudpath';
    
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/soundcloud');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/soundcloud');
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
        $color    = (!empty($configdata['color']) ? $configdata['color'] : '#ff6600');
        $autoplay = (!empty($configdata['autoplay']) ? $configdata['autoplay'] : false);
        
        $smarty = smarty_core();
        switch ($display) {
            case 'embed':
                $html = '';
                $options = array('color' => $color, 'autoplay' => $autoplay);
                if (!empty($selected)) {
                    foreach ($selected as $artefact) {
                        $data = explode("-", $artefact);
                        if ($data[0] == 'track') {
                            $html .= self::embed_file($data[1], $options);
                            $html .= '<br><br>';
                        }
                        if ($data[0] == 'set') {
                            $html .= self::embed_folder($data[1], $options);
                            $html .= '<br><br>';
                        }
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
        return $smarty->fetch('blocktype:soundcloud:' . $display . '.tpl');
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
            'soundcloudfiles' => array(
                'type'     => 'datatables',
                'title'    => get_string('selectfiles','blocktype.cloud/soundcloud'),
                'service'  => 'soundcloud',
                'block'    => $instanceid,
                'fullpath' => (isset($configdata['fullpath']) ? $configdata['fullpath'] : null),
                'options'  => array(
                    'showFolders'    => true,
                    'showFiles'      => true,
                    'selectFolders'  => true,
                    'selectFiles'    => true,
                    'selectMultiple' => true
                ),
            ),
            'display' => array(
                'type' => 'radio',
                'title' => get_string('display','blocktype.cloud/soundcloud'),
                //'description' => get_string('displaydesc','blocktype.cloud/soundcloud') . '<br>' . get_string('displaydesc2','blocktype.cloud/soundcloud'),
                'defaultvalue' => (!empty($configdata['display']) ? hsc($configdata['display']) : 'list'),
                'options' => array(
                    'list'  => get_string('displaylist','blocktype.cloud/soundcloud'),
                    //'icon'  => get_string('displayicon','blocktype.cloud/soundcloud'),
                    'embed' => get_string('displayembed','blocktype.cloud/soundcloud')
                ),
                'separator' => '<br />',
            ),
            'embedoptions' => array(
                'type'         => 'fieldset',
                'collapsible'  => true,
                'collapsed'    => true,
                'legend'       => get_string('embedoptions', 'blocktype.cloud/soundcloud'),
                'elements'     => array(
                    'color' => array(
                        'type' => 'color',
                        'labelhtml' => get_string('color','blocktype.cloud/soundcloud'),
                        'defaultvalue' => (!empty($configdata['color']) ? hsc($configdata['color']) : '#ff6600'),
                    ),
                    'autoplay' => array(
                        'type'  => 'checkbox',
                        'labelhtml' => get_string('autoplay', 'blocktype.cloud/soundcloud'),
                        'defaultvalue' => (!empty($configdata['autoplay']) ? hsc($configdata['autoplay']) : false),
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
        parse_str(urldecode($values['soundcloudfiles']));
        if (!isset($artefacts) || empty($artefacts)) {
            $artefacts = array();
        }
        
        $values = array(
            'title'     => $values['title'],
            'fullpath'  => $_SESSION[self::servicepath],
            'artefacts' => $artefacts,
            'display'   => $values['display'],
            'color'     => $values['color'],
            'autoplay'  => $values['autoplay'],
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
            'value' => get_string('applicationdesc', 'blocktype.cloud/soundcloud', '<a href="http://soundcloud.com/you/apps/new" target="_blank">', '</a>'),
        );
        $elements['apisettings'] = array(
            'type' => 'fieldset',
            'legend' => get_string('apisettings', 'blocktype.cloud/soundcloud'),
            'elements' => array(
                'applicationname' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationname', 'blocktype.cloud/soundcloud'),
                    'defaultvalue' => get_config('sitename'),
                    'description'  => get_string('applicationnamedesc', 'blocktype.cloud/soundcloud'),
                    'readonly'     => true,
                ),
                'consumerkey' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumerkey', 'blocktype.cloud/soundcloud'),
                    'defaultvalue' => get_config_plugin('blocktype', 'soundcloud', 'consumerkey'),
                    'description'  => get_string('consumerkeydesc', 'blocktype.cloud/soundcloud'),
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'consumersecret' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumersecret', 'blocktype.cloud/soundcloud'),
                    'defaultvalue' => get_config_plugin('blocktype', 'soundcloud', 'consumersecret'),
                    'description'  => get_string('consumersecretdesc', 'blocktype.cloud/soundcloud'),
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'applicationurl' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationurl', 'blocktype.cloud/soundcloud'),
                    'defaultvalue' => get_config('wwwroot'),
                    'description'  => get_string('applicationurldesc', 'blocktype.cloud/soundcloud'),
                    'readonly'     => true,
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'redirecturl' => array(
                    'type'         => 'text',
                    'title'        => get_string('redirecturl', 'blocktype.cloud/soundcloud'),
                    'defaultvalue' => get_config('wwwroot') . 'artefact/cloud/blocktype/soundcloud/callback.php',
                    'description'  => get_string('redirecturldesc', 'blocktype.cloud/soundcloud'),
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
        set_config_plugin('blocktype', 'soundcloud', 'consumerkey', $values['consumerkey']);
        set_config_plugin('blocktype', 'soundcloud', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /*************************************************
     * Methods & stuff for accessing Sound Cloud API *
     *************************************************/
    
    public function cloud_info() {
        return array(
            'ssl'        => true,
            'version'    => '',
            'wwwurl'     => 'http://soundcloud.com/', // Needed for oembed service!
            'baseurl'    => 'https://soundcloud.com/',
            'apiurl'     => 'https://api.soundcloud.com/',
            'authurl'    => 'https://api.soundcloud.com/oauth2/',
        );
    }
    
    public function consumer_tokens() {
        return array(
            'key'      => get_config_plugin('blocktype', 'soundcloud', 'consumerkey'),
            'secret'   => get_config_plugin('blocktype', 'soundcloud', 'consumersecret'),
            'callback' => get_config('wwwroot') . 'artefact/cloud/blocktype/soundcloud/callback.php'
        );
    }
    
    public function user_tokens($userid) {
        return ArtefactTypeCloud::get_user_preferences('soundcloud', $userid);
    }
    
    public function service_list() {
        global $USER;
        $consumer    = self::consumer_tokens();
        $usertoken   = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            if (isset($usertoken['access_token']) && !empty($usertoken['access_token'])) {
                return array(
                    'service_name'   => 'soundcloud',
                    'service_url'    => 'http://soundcloud.com',
                    'service_auth'   => true,
                    'service_manage' => true,
                    //'revoke_access'  => true,
                );
            } else {
                return array(
                    'service_name'   => 'soundcloud',
                    'service_url'    => 'http://soundcloud.com',
                    'service_auth'   => false,
                    'service_manage' => false,
                    //'revoke_access'  => false,
                );
            }
        } else {
            throw new ConfigException('Can\'t find SoundCloud API consumer ID and/or consumer secret.');
        }
    }
    
    /*
     * SEE: http://developers.soundcloud.com/docs#authentication
     * SEE: http://developers.soundcloud.com/docs/api/reference#connect
     */
    public function request_token() {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].'connect';
            $params = array(
                'client_id' => $consumer['key'],
                'redirect_uri' => $consumer['callback'],
                'response_type' => 'code',
                // Non expiring access token, see: http://developers.soundcloud.com/blog/non-expiring-tokens
                'scope' => 'non-expiring'
            );
            $query = oauth_http_build_query($params);
            $request_url = $url . ($query ? ('?' . $query) : '' );
            redirect($request_url);
        } else {
            throw new ConfigException('Can\'t find SoundCloud API  consumer key and/or consumer secret.');
        }
    }

    /*
     * SEE: http://developers.soundcloud.com/docs#authentication
     * SEE: http://developers.soundcloud.com/docs/api/reference#token
     */
    public function access_token($oauth_code) {
        global $USER, $SESSION;
        $cloud    = PluginBlocktypeSoundcloud::cloud_info();
        $consumer = PluginBlocktypeSoundcloud::consumer_tokens();
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
            $header[] = build_oauth_header($params, "SoundCloud API  PHP Client");
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
                $SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/soundcloud'));
            }
        } else {
            throw new ConfigException('Can\'t find SoundCloud API  consumer ID and/or consumer secret.');
        }
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('soundcloud', $USER->get('id'), null);
    }
    
    public function revoke_access() {
        // Not implemented yet...
        // SEE: http://blog.soundcloud.com/2010/01/25/connect/
        // SEE: https://groups.google.com/forum/?fromgroups=#!topic/soundcloudapi/Nvw3QLtxXcc
    }
    
    /*
     * SEE: http://developers.soundcloud.com/docs/api/reference#me
     */
    public function account_info() {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['apiurl'].'me.json';
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('oauth_token' => $usertoken['access_token']);
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
                // With SoundCloud it means how many seconds of sound can user upload...
                // SEE: http://help.soundcloud.com/customer/portal/articles/243685-how-many-sounds-can-i-upload-with-soundcloud-
                $timeAvailable = $data['upload_seconds_left'];
                switch ($data['plan']) {
			case "Free": $timeTotal = 7200; break;   // 2 hours
                     case "Lite": $timeTotal = 14400; break;  // 4 hours
                     case "Solo": $timeTotal = 43200; break;  // 12 hours
                     case "Pro":  $timeTotal = 129600; break; // 36 hours
                     case "Pro Plus":
                     default:     $timeTotal = 'unlimited'; break; // unlimited
                }
                $timeUsed = $timeTotal - $timeAvailable; 
                return array(
                    'service_name' => 'soundcloud',
                    'service_auth' => true,
                    'user_id'      => $data['id'],
                    'user_name'    => $data['username'],
                    'user_profile' => $data['permalink_url'],
                    'space_used'   => seconds_to_hms($timeUsed),
                    'space_amount' => ($timeTotal != 'unlimited' ? seconds_to_hms($timeTotal) : get_string('unlimited', 'blocktype.cloud/soundcloud')),
                    'space_ratio'  => ($timeTotal != 'unlimited' ? number_format((floatval($timeUsed)/floatval($timeTotal)*100), 2) : number_format(0, 2)),
                );
            } else {
                return array(
                    'service_name' => 'soundcloud',
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
            throw new ConfigException('Can\'t find SoundCloud API  consumer ID and/or consumer secret.');
        }
    }
    
    
    /*
     * This function returns list of selected files/folders which will be displayed in a view/page.
     *
     * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
     * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
     *
     * SEE: http://developers.soundcloud.com/docs/api/reference#users
     * SEE: http://developers.soundcloud.com/docs/api/reference#playlists
     *
     */
    public function get_filelist($folder_id=0, $selected=array()) {
        global $USER, $THEME;

        // Get folder contents...
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $output = array(
                'folders' => array(),
                'files'   => array()
            );
            if ($folder_id > 0) {
                $url = $cloud['apiurl'].'playlists/'.$folder_id.'.json';
            } else {
                $url = $cloud['apiurl'].'users/me/playlists.json';
            }
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('oauth_token' => $usertoken['access_token']) ;
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
                if (!empty($data) && $folder_id == 0) {
                    // Add folders (playlists or sets)...
                    foreach($data as $artefact) {
                        if (in_array('set-'.$artefact['id'], $selected)) {
                            $id          = $artefact['id'];
                            $type        = 'folder'; // actually 'playlist' or 'set'
                            $icon        = '<img src="' . $THEME->get_url('images/folder.gif') . '">';
                            $title       = $artefact['title'];
                            $description = (!empty($artefact['description']) ? $artefact['description'] : '');
                            $created     = ($artefact['created_at'] ? format_date(strtotime($artefact['created_at']), 'strftimedaydate') : null);
                            $duration    = milliseconds_to_hms($artefact['duration']);
                            $output['folders'][] = array('iconsrc' => $icon, 'id' => $id, 'type' => $type, 'title' => $title, 'description' => $description, 'duration' => $duration, 'ctime' => $created);
                        }
                    }
                    // Add files (tracks)...
                    $url2 = $cloud['apiurl'].'users/me/tracks.json';
                    $method = 'GET';
                    $port = $cloud['ssl'] ? '443' : '80';
                    $params2 = array('oauth_token' => $usertoken['access_token']) ;
                    $config2 = array(
                        CURLOPT_URL => $url2.'?'.oauth_http_build_query($params2),
                        CURLOPT_PORT => $port,
                        CURLOPT_POST => false,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYHOST => 2,
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
                    );
                    $result2 = mahara_http_request($config2);
                    if ($result2->info['http_code'] == 200 && !empty($result2->data)) {
                        $tracks = json_decode($result2->data, true);
                        foreach($tracks as $artefact) {
                            if (in_array('track-'.$artefact['id'], $selected)) {
                                $id          = $artefact['id'];
                                $type        = 'file'; // actually 'track'
                                $icon        = '<img src="' . $THEME->get_url('images/folder.gif') . '">';
                                $title       = $artefact['title'];
                                $description = (!empty($artefact['description']) ? $artefact['description'] : '');
                                $created     = ($artefact['created_at'] ? format_date(strtotime($artefact['created_at']), 'strftimedaydate') : null);
                                $duration    = milliseconds_to_hms($artefact['duration']);
                                $output['files'][] = array('iconsrc' => $icon, 'id' => $id, 'type' => $type, 'title' => $title, 'description' => $description, 'duration' => $duration, 'ctime' => $created);
                            }
                        }
                    }
                } else {
                    // Add files (tracks) from selected folder (playlist or set)...
                    foreach($data['tracks'] as $artefact) {
                        if (in_array('track-'.$artefact['id'], $selected)) {
                            $id          = $artefact['id'];
                            $type        = 'file'; // actually 'track'
                            $icon        = '<img src="' . $THEME->get_url('images/folder.gif') . '">';
                            $title       = $artefact['title'];
                            $description = (!empty($artefact['description']) ? $artefact['description'] : '');
                            $created     = ($artefact['created_at'] ? format_date(strtotime($artefact['created_at']), 'strftimedaydate') : null);
                            $duration    = milliseconds_to_hms($artefact['duration']);
                            $output['files'][] = array('iconsrc' => $icon, 'id' => $id, 'type' => $type, 'title' => $title, 'description' => $description, 'duration' => $duration, 'ctime' => $created);
                        }
                    }
                }
            }
            return $output;
         } else {
            throw new ConfigException('Can\'t find SoundCloud API  consumer ID and/or consumer secret.');
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
     * SEE: http://developers.soundcloud.com/docs/api/reference#users
     * SEE: http://developers.soundcloud.com/docs/api/reference#playlists
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
                if ($folder_id > 0) {
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
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $output = array();
            $count = 0;
            if ($folder_id > 0) {
                $url = $cloud['apiurl'].'playlists/'.$folder_id.'.json';
            } else {
                $url = $cloud['apiurl'].'users/me/playlists.json';
            }
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('oauth_token' => $usertoken['access_token']) ;
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
                // Add 'parent' row entry to jQuery Datatable...
                if (strlen($_SESSION[self::servicepath]) > 6) {
                    $type        = 'parentfolder';
                    $foldername  = get_string('parentfolder', 'artefact.file');
                    $title       = '<a class="changefolder" href="javascript:void(0)" id="parent" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '"><img src="' . get_config('wwwroot') . 'artefact/cloud/theme/raw/static/images/parentfolder.png"></a>';
                    $output['aaData'][] = array('', $title, '', $type);
                }
                if (!empty($data) && $folder_id == 0) {
                    // Add folders (playlists or sets)...
                    foreach($data as $artefact) {
                        $id       = $artefact['id'];
                        $type     = 'folder'; // actually 'playlist' or 'set'
                        $icon     = '<img src="' . $THEME->get_url('images/folder.gif') . '">';
                        $title    = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $artefact['title']) . '">' . $artefact['title'] . '</a>';
                        $controls = '';
                        $selected = (in_array('set-'.$id, $artefacts) ? ' checked' : '');
                        if ($selectFolders) {
                            $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="set-' . $id . '"' . $selected . '>';
                        }
                        $output['aaData'][] = array($icon, $title, $controls, $type);
                        $count++;
                    }
                    // Add files (tracks)...
                    $url2 = $cloud['apiurl'].'users/me/tracks.json';
                    $method = 'GET';
                    $port = $cloud['ssl'] ? '443' : '80';
                    $params2 = array('oauth_token' => $usertoken['access_token']) ;
                    $config2 = array(
                        CURLOPT_URL => $url2.'?'.oauth_http_build_query($params2),
                        CURLOPT_PORT => $port,
                        CURLOPT_POST => false,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYHOST => 2,
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
                    );
                    $result2 = mahara_http_request($config2);
                    if ($result2->info['http_code'] == 200 && !empty($result2->data)) {
                        $tracks = json_decode($result2->data, true);
                        foreach($tracks as $artefact) {
                            $id       = $artefact['id'];
                            $type     = 'file'; // actually 'track'
                            $icon     = '<img src="' . $THEME->get_url('images/file.gif') . '">';
                            $title    = '<a class="filedetails" href="details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $artefact['title']) . '">' . $artefact['title'] . '</a>';
                            $controls = '';
                            $selected = (in_array('track-'.$id, $artefacts) ? ' checked' : '');
                            if ($selectFiles && !$manageButtons) {
                                $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="track-' . $id . '"' . $selected . '>';
                            } elseif ($manageButtons) {
                                $controls  = '<a class="btn" href="preview.php?id=' . $id . '" target="_blank">' . get_string('preview', 'artefact.cloud') . '</a>';
                                $controls .= '<a class="btn" href="download.php?id=' . $id . '&save=1">' . get_string('save', 'artefact.cloud') . '</a>';
                                $controls .= '<a class="btn" href="download.php?id=' . $id . '">' . get_string('download', 'artefact.cloud') . '</a>';
                            }
                            $output['aaData'][] = array($icon, $title, $controls, $type);
                            $count++;
                        }
                    }
                } else {
                    // Add files (tracks) from selected folder (playlist or set)...
                    foreach($data['tracks'] as $artefact) {
                        $id       = $artefact['id'];
                        $type     = 'file'; // actually 'track'
                        $icon     = '<img src="' . $THEME->get_url('images/file.gif') . '">';
                        $title    = '<a class="filedetails" href="details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $artefact['title']) . '">' . $artefact['title'] . '</a>';
                        $controls = '';
                        $selected = (in_array('track-'.$id, $artefacts) ? ' checked' : '');
                        if ($selectFiles && !$manageButtons) {
                            $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="track-' . $id . '"' . $selected . '>';
                        } elseif ($manageButtons) {
                            $controls  = '<a class="btn" href="preview.php?id=' . $id . '" target="_blank">' . get_string('preview', 'artefact.cloud') . '</a>';
                            $controls .= '<a class="btn" href="download.php?id=' . $id . '&save=1">' . get_string('save', 'artefact.cloud') . '</a>';
                            $controls .= '<a class="btn" href="download.php?id=' . $id . '">' . get_string('download', 'artefact.cloud') . '</a>';
                        }
                        $output['aaData'][] = array($icon, $title, $controls, $type);
                        $count++;
                    }
                }
            }
            $output['iTotalRecords'] = $count;
            $output['iTotalDisplayRecords'] = $count;
            return json_encode($output);
        } else {
            throw new ConfigException('Can\'t find SoundCloud API  consumer ID and/or consumer secret.');
        }
    }

    /*
     * get_folder_info basically means get_playlist_(set)_info...
     *
     * SEE: http://developers.soundcloud.com/docs/api/reference#playlists
     */
    public function get_folder_info($folder_id=0) {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['apiurl'].'playlists/'.$folder_id.'.json';
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('oauth_token' => $usertoken['access_token']) ;
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
                    'type'        => $data['kind'],
                    'name'        => $data['title'],
                    'license'     => $data['license'],
                    'duration_ms' => $data['duration'],
                    'duration'    => milliseconds_to_hms($data['duration']),
                    'shared'      => $data['sharing'],
                    'preview'     => $data['permalink_url'],
                    'description' => ($data['description'] ? $data['description'] : null),
                    'created'     => ($data['created_at'] ? format_date(strtotime($data['created_at']), 'strfdaymonthyearshort') : null),
                    'tracks'      => count($data['tracks']),
                );
                return $info;
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find SoundCloud API  consumer ID and/or consumer secret.');
        }
    }

    /*
     * get_file_info basically means get_track_info...
     *
     * SEE: http://developers.soundcloud.com/docs/api/reference#tracks
     */
    public function get_file_info($file_id=0) {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['apiurl'].'tracks/'.$file_id.'.json';
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('oauth_token' => $usertoken['access_token']) ;
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
                    'type'        => $data['kind'],
                    'name'        => $data['title'],
                    'license'     => $data['license'],
                    'duration_ms' => $data['duration'],
                    'duration'    => milliseconds_to_hms($data['duration']),
                    'format'      => $data['original_format'],
                    'shared'      => $data['sharing'], 
                    'preview'     => $data['permalink_url'],
                    'description' => (isset($data['description']) ? $data['description'] : null),
                    'created'     => (isset($data['created_at']) ? format_date(strtotime($data['created_at']), 'strfdaymonthyearshort') : null),
                );
                return $info;
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find SoundCloud API  consumer ID and/or consumer secret.');
        }
    }

    /*
     * Returns array of audio MIME types, supported by SoundCloud
     *
     * SEE: http://help.soundcloud.com/customer/portal/articles/247477-what-formats-can-i-upload-
     * SEE: https://github.com/mptre/php-soundcloud/blob/master/Services/Soundcloud.php
     */
    private function get_supported_mimetypes() {
        return array(
            'aac' => 'video/mp4',
            'aiff' => 'audio/x-aiff',
            'flac' => 'audio/flac',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'wav' => 'audio/x-wav'
        );
    }

    /*
     * SEE: http://developers.soundcloud.com/docs/api/reference#tracks
     */
    public function download_file($file_id=0) {
        global $USER;
        $cloud       = self::cloud_info();
        $consumer    = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['apiurl'].'tracks/'.$file_id.'.json';
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('oauth_token' => $usertoken['access_token']) ;
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
                $download_url = $data['download_url'] . '?oauth_token=' . str_replace('%7E', '~', rawurlencode($usertoken['access_token']));
                $result = '';
                $port = $cloud['ssl'] ? '443' : '80';
                $ch = curl_init($download_url);
                curl_setopt($ch, CURLOPT_PORT, $port);
                curl_setopt($ch, CURLOPT_POST, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_CAINFO, get_config('docroot').'artefact/cloud/cert/cacert.crt');
                // SoundCloud API  request returns 'Location' inside response header.
                // Follow 'Location' in response header to get the actual file content.
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $result = curl_exec($ch);
                curl_close($ch);
                return $result;
            }
        } else {
            throw new ConfigException('Can\'t find SoundCloud API  consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: http://developers.soundcloud.com/docs/api/reference#oembed
     */
    public function embed_file($file_id=0, $options=array('color' => '#ff6600', 'autoplay' => false)) {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].'oembed';
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array(
                //'oauth_token' => $usertoken['access_token'],
                // Method specific parametres...
                'url'    => self::public_url($file_id, 'file'),
                'format' => 'json',
                'color' => $options['color'],
                'auto_play' => $options['autoplay'],
            );
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
                return $data['html'];
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find SoundCloud API  consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: http://developers.soundcloud.com/docs/api/reference#oembed
     */
    public function embed_folder($folder_id=0, $options=array('color' => '#ff6600', 'autoplay' => false)) {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].'oembed';
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array(
                //'oauth_token' => $usertoken['access_token'],
                // Method specific parametres...
                'url'    => self::public_url($folder_id, 'folder'),
                'format' => 'json',
                'color' => $options['color'],
                'auto_play' => $options['autoplay'],
            );
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
                return $data['html'];
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find SoundCloud API  consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: http://developers.soundcloud.com/docs/api/reference#tracks
     * SEE: http://developers.soundcloud.com/docs/api/reference#playlists
     */
    public function public_url($artefact_id=0, $type='file') {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            if ($type == 'file') {
                $url = $cloud['apiurl'].'tracks/'.$artefact_id.'.json';
            } else {
                $url = $cloud['apiurl'].'playlists/'.$artefact_id.'.json';
            }
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('oauth_token' => $usertoken['access_token']) ;
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
                return $data['permalink_url'];
            } else {
                return null;
            }
         } else {
            throw new ConfigException('Can\'t find SoundCloud API  consumer ID and/or consumer secret.');
        }
    }
   
    
}

?>
