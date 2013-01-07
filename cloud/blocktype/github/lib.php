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
 * @subpackage blocktype-github
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/oauth.php');


class PluginBlocktypeGithub extends PluginBlocktypeCloud {

    const servicepath = 'githubpath';
    
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/github');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/github');
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
        list($folder, $path) = explode('|', $fullpath, 2);
        $data = self::get_filelist($folder, $selected);
        $smarty->assign('folders', $data['folders']);
        $smarty->assign('files', $data['files']);
        $smarty->assign('viewid', $viewid);
        return $smarty->fetch('blocktype:github:list.tpl');
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
            'githubfiles' => array(
                'type'     => 'datatables',
                'title'    => get_string('selectfiles','blocktype.cloud/github'),
                'service'  => 'github',
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
        parse_str(urldecode($values['githubfiles']));
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
            'value' => get_string('applicationdesc', 'blocktype.cloud/github', '<a href="https://github.com/settings/applications/new" target="_blank">', '</a>'),
        );
        $elements['apisettings'] = array(
            'type' => 'fieldset',
            'legend' => get_string('apisettings', 'blocktype.cloud/github'),
            'elements' => array(
                'applicationname' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationname', 'blocktype.cloud/github'),
                    'defaultvalue' => get_config('sitename'),
                    'description'  => get_string('applicationnamedesc', 'blocktype.cloud/github'),
                    'readonly'     => true,
                ),
                'consumerkey' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumerkey', 'blocktype.cloud/github'),
                    'defaultvalue' => get_config_plugin('blocktype', 'github', 'consumerkey'),
                    'description'  => get_string('consumerkeydesc', 'blocktype.cloud/github'),
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'consumersecret' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumersecret', 'blocktype.cloud/github'),
                    'defaultvalue' => get_config_plugin('blocktype', 'github', 'consumersecret'),
                    'description'  => get_string('consumersecretdesc', 'blocktype.cloud/github'),
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'applicationurl' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationurl', 'blocktype.cloud/github'),
                    'defaultvalue' => get_config('wwwroot'),
                    'description'  => get_string('applicationurldesc', 'blocktype.cloud/github'),
                    'readonly'     => true,
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'redirecturl' => array(
                    'type'         => 'text',
                    'title'        => get_string('redirecturl', 'blocktype.cloud/github'),
                    'defaultvalue' => get_config('wwwroot') . 'artefact/cloud/blocktype/github/callback.php',
                    'description'  => get_string('redirecturldesc', 'blocktype.cloud/github'),
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
        set_config_plugin('blocktype', 'github', 'consumerkey', $values['consumerkey']);
        set_config_plugin('blocktype', 'github', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /********************************************
     * Methods & stuff for accessing GitHub API *
     ********************************************/
    
    public function cloud_info() {
        return array(
            'ssl'     => true,
            'version' => '', // API version is currently 'v3', but this string is not used in URLs!
            'baseurl' => 'https://api.github.com', // Final slash character is missing. Don't forget to set it, if/when you set API version!
            'authurl' => 'https://github.com/',
        );
    }
    
    public function consumer_tokens() {
        return array(
            'key'      => get_config_plugin('blocktype', 'github', 'consumerkey'),
            'secret'   => get_config_plugin('blocktype', 'github', 'consumersecret'),
            'callback' => get_config('wwwroot') . 'artefact/cloud/blocktype/github/callback.php'
        );
    }
    
    public function user_tokens($userid) {
        return ArtefactTypeCloud::get_user_preferences('github', $userid);
    }
    
    public function service_list() {
        global $USER;
        $consumer    = self::consumer_tokens();
        $usertoken   = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            if (isset($usertoken['access_token']) && !empty($usertoken['access_token'])) {
                return array(
                    'service_name'   => 'github',
                    'service_url'    => 'http://www.github.com',
                    'service_auth'   => true,
                    'service_manage' => true,
                    //'revoke_access'  => true,
                );
            } else {
                return array(
                    'service_name'   => 'github',
                    'service_url'    => 'http://www.github.com',
                    'service_auth'   => false,
                    'service_manage' => false,
                    //'revoke_access'  => false,
                );
            }
        } else {
            throw new ConfigException('Can\'t find GitHub consumer ID and/or consumer secret.');
        }
    }
    
    /*
     * SEE: http://developer.github.com/v3/oauth/#redirect-users-to-request-github-access
     * SEE: http://developer.github.com/v3/oauth/#scopes
     */
    public function request_token() {
        global $USER;
        $cloud    = self::cloud_info();
        $consumer = self::consumer_tokens();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['authurl'].'login/oauth/authorize';
            $scopes = 'user,repo';
            $params = array(
                'client_id' => $consumer['key'],
                'scope' => $scopes,
                'redirect_uri' => $consumer['callback']
            );
            $query_parameter_string = oauth_http_build_query($params);
            $request_url = $url . ($query_parameter_string ? ('?' . $query_parameter_string) : '' );
            redirect($request_url);
        } else {
            throw new ConfigException('Can\'t find GitHub consumer ID and/or consumer secret.');
        }
    }

    /*
     * SEE: http://developer.github.com/v3/oauth/#github-redirects-back-to-your-site
     */
    public function access_token($oauth_code) {
        global $USER, $SESSION;
        $cloud    = PluginBlocktypeGithub::cloud_info();
        $consumer = PluginBlocktypeGithub::consumer_tokens();
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['authurl'].'login/oauth/access_token';
            $method = 'POST';
            $port = $cloud['ssl'] ? '443' : '80';
            $params   = array(
                'client_id' => $consumer['key'],
                'redirect_uri' => $consumer['callback'],
                'client_secret' => $consumer['secret'],
                'code' => $oauth_code,
            );
            $query = oauth_http_build_query($params);
            $header = array();
            $header[] = build_oauth_header($params, "GitHub API PHP Client");
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
                // Store access_token (oauth_token) and token_type
                // We'll need it for all API calls...
                $prefs = oauth_parse_str(substr($result->data, $result->info['header_size']));
                // Get username and user ID, will be handy later...
                $url2 = $cloud['baseurl'].'/user';
                $method2 = 'GET';
                $params2 = array('access_token' => $prefs['access_token']);
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
                    $data  = json_decode($result2->data, true);
                    $prefs['user_id']   = $data['id'];    // Get user ID
                    $prefs['user_name'] = $data['login']; // Get username
                }
                ArtefactTypeCloud::set_user_preferences('github', $USER->get('id'), $prefs);
            } else {
                $SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/github'));
            }
        } else {
            throw new ConfigException('Can\'t find GitHub consumer ID and/or consumer secret.');
        }
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('github', $USER->get('id'), null);
    }
    
    /*
     * SEE: http://developer.github.com/v3/oauth/#list-your-authorizations
     * SEE: http://developer.github.com/v3/oauth/#delete-an-authorization
     */
    public function revoke_access() {
        // Should work (see above URLs), but all I got was 404 Error. So I'll just leave it at that...
        // This means no real revoking of access on Mahara side. Users can revoke access on GitHub side.
    }
    
    /*
     * SEE: http://developer.github.com/v3/users/#get-the-authenticated-user
     */
    public function account_info() {
        global $USER;
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].'/user';
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('access_token' => $usertoken['access_token']);
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
                $data  = json_decode($result->data, true);
                $used  = floatval($data['disk_usage'])*1024;
                $total = floatval($data['plan']['space'])*1024;
                return array(
                    'service_name' => 'github',
                    'service_auth' => true,
                    'user_id'      => $data['id'],
                    'user_name'    => $data['login'],
                    'user_profile' => $data['html_url'],
                    'space_plan'   => $data['plan']['name'],
                    'space_used'   => bytes_to_size1024($used),
                    'space_amount' => bytes_to_size1024($total),
                    'space_ratio'  => number_format(floatval($used/$total), 2),
                    'public_repos' => $data['public_repos'],
                    'followers'    => $data['followers'],
                    'following'    => $data['following'],
                );
            } else {
                return array(
                    'service_name' => 'github',
                    'service_auth' => false,
                    'user_id'      => null,
                    'user_name'    => null,
                    'user_profile' => null,
                    'space_plan'   => null,
                    'space_used'   => null,
                    'space_amount' => null,
                    'space_ratio'  => null,
                    'public_repos' => null,
                    'public_gists' => null,
                    'followers'    => null,
                    'following'    => null,
                );
            }
        } else {
            throw new ConfigException('Can\'t find GitHub consumer ID and/or consumer secret.');
        }
    }
    
    
    /*
     * This function returns list of selected files/folders which will be displayed in a view/page.
     *
     * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
     * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
     *
     * SEE: http://developer.github.com/v3/repos/#list-your-repositories
     *
     */
    public function get_filelist($folder_id='0', $selected=array()) {
        global $USER, $THEME;

        // Get folder contents...
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/user/repos';
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('access_token' => $usertoken['access_token']);
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
                if (!empty($data)) {
                    foreach($data as $artefact) {
                        if (in_array($artefact['full_name'], $selected)) {
                            $id          = $artefact['full_name'];
                            $type        = $artefact['language'];
                            $icon        = get_config('wwwroot') . 'artefact/cloud/blocktype/github/icons/'.(in_array(strtolower($type), self::get_available_repo_icons()) ? $type : 'repo').'.png';
                            $title       = $artefact['name'];
                            $description = $artefact['description'];
                            $preview     = $artefact['html_url'];
                            $size        = bytes_to_size1024($artefact['size']);
                            $created     = ($artefact['created_at'] ? format_date(strtotime($artefact['created_at']), 'strftimedate') : null);
                            $updated     = ($artefact['updated_at'] ? format_date(strtotime($artefact['updated_at']), 'strftimedate') : null);
                            if ($type == 'folder') {
                                $output['folders'][] = array('iconsrc' => $icon, 'id' => $id, 'type' => $type, 'title' => $title, 'description' => $description, 'preview' => $preview, 'size' => $size, 'ctime' => $created, 'utime' => $updated);
                            } else {
                                $output['files'][] = array('iconsrc' => $icon, 'id' => $id, 'type' => $type, 'title' => $title, 'description' => $description, 'preview' => $preview, 'size' => $size, 'ctime' => $created, 'utime' => $updated);
                            }
                        }
                    }
                }                    
                return $output;
            } else {
                return array();
            }
        } else {
            throw new ConfigException('Can\'t find GitHub consumer ID and/or consumer secret.');
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
     * SEE: http://developer.github.com/v3/repos/#list-your-repositories
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
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/user/repos';
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('access_token' => $usertoken['access_token']);
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
                if (!empty($data)) {
                    foreach($data as $artefact) {
                        $id       = $artefact['full_name'];
                        $type     = strtolower($artefact['language']);
                        $icon     = '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/github/icons/'.(in_array($type, self::get_available_repo_icons()) ? $type : 'repo').'.png">';
                        $title    = '<a class="filedetails" href="details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $artefact['name']) . '">' . $artefact['name'] . '</a>';
                        $controls = '';
                        $selected = (in_array($id, $artefacts) ? ' checked' : '');
                        if ($selectFiles && !$manageButtons) {
                            $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                        } elseif ($manageButtons) {
                            $controls  = '<a class="btn" href="preview.php?id=' . $artefact['full_name'] . '" target="_blank">' . get_string('preview', 'artefact.cloud') . '</a>';
                            $controls .= '<a class="btn" href="download.php?id=' . $artefact['full_name'] . '&archive=tarball&branch=' . (!empty($artefact['master_branch']) ? $artefact['master_branch'] : 'master')  . '">' . get_string('download', 'artefact.cloud') . '&nbsp;TAR</a>';
                            $controls .= '<a class="btn" href="download.php?id=' . $artefact['full_name'] . '&archive=zipball&branch=' . (!empty($artefact['master_branch']) ? $artefact['master_branch'] : 'master')  . '">' . get_string('download', 'artefact.cloud') . '&nbsp;ZIP</a>';
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
            throw new ConfigException('Can\'t find GitHub consumer ID and/or consumer secret.');
        }
    }

    public function get_folder_info($folder_id=0) {
        // Repositories represented as files...
        // No need for folders, so nothing to do.
    }

    /*
     * get_file_info basically means get_repository_info...
     *
     * SEE: http://developer.github.com/v3/repos/#get
     */
    public function get_file_info($repo_id='') {
        global $USER;
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/repos/'.$repo_id;
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array('access_token' => $usertoken['access_token']);
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
                $type = strtolower($data['language']);
                $info = array(
                    'id'          => $data['id'],
                    'repoicon'    => (in_array($type, self::get_available_repo_icons()) ? $type : 'repo'),
                    'name'        => $data['name'],
                    'fullname'    => $data['full_name'],
                    'branch'      => $data['master_branch'],
                    'preview'     => $data['html_url'],
                    'description' => $data['description'],
                    'language'    => $data['language'],
                    'bytes'       => $data['size']*1024, // By default size is returned in kB!
                    'size'        => bytes_to_size1024($data['size']*1024),
                    'created'     => ($data['created_at'] ? format_date(strtotime($data['created_at']), 'strfdaymonthyearshort') : null),
                    'updated'     => ($data['updated_at'] ? format_date(strtotime($data['updated_at']), 'strfdaymonthyearshort') : null),
                );
                return $info;
            } else {
                return null;
            }
        } else {
            throw new ConfigException('Can\'t find GitHub consumer ID and/or consumer secret.');
        }
    }

    public function download_file($repo_id=0) {
        // GitHub API returns direct links for downloading the
        // repository in TGZ or ZIP format, so nothing to do...
        // Also see: download.php for details...
    }

    public function embed_file($repo_id=0, $options=array()) {
        // Nothing to do...
    }
    
    private function get_available_repo_icons() {
        // Return array of all available
        // programming language repo icons...
        return array(
            'c',
            'c#',
            'c++',
            'delphi',
            'java',
            'javascript',
            'perl',
            'php',
            'python',
            'ruby',
            'shell'
        );
    }


}

?>
