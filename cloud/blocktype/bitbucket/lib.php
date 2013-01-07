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
 * @subpackage blocktype-bitbucket
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/oauth.php');
//require_once('lib/api.php'); // Bitbucket API methods


class PluginBlocktypeBitbucket extends PluginBlocktypeCloud {

    const servicepath = 'bitbucketpath';
    
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/bitbucket');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/bitbucket');
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
        
        $smarty = smarty_core();
        list($folder, $path) = explode('|', $fullpath, 2);
        $data = self::get_filelist($folder, $selected);
        $smarty->assign('folders', $data['folders']);
        $smarty->assign('files', $data['files']);
        $smarty->assign('viewid', $viewid);
        return $smarty->fetch('blocktype:bitbucket:list.tpl');
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
            'bitbucketfiles' => array(
                'type'     => 'datatables',
                'title'    => get_string('selectfiles','blocktype.cloud/bitbucket'),
                'service'  => 'bitbucket',
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
        parse_str(urldecode($values['bitbucketfiles']));
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
        $username = get_config_plugin('blocktype', 'bitbucket', 'consumeruser');
        if (!empty($username) && count($username)>0) {
            $linkstart = '<a href="https://bitbucket.org/account/user/'.$username.'/api" target="_blank">'; 
            $linkend   = '</a>';
            $required  = true; // Require consumer key and secret because username is already set.
        } else {
            $linkstart = ''; 
            $linkend   = '';
            $required  = false; // Don't require consumer key and secret because username isn't set.
        }
        $elements = array();
        $elements['applicationdesc'] = array(
            'type'  => 'html',
            'value' => get_string('consumerdesc', 'blocktype.cloud/bitbucket', $linkstart, $linkend)
            . '<br>' . get_string('consumerdesc2', 'blocktype.cloud/bitbucket', $linkstart, $linkend),
        );
        $elements['consumergeneral'] = array(
            'type' => 'fieldset',
            'legend' => get_string('consumergeneral', 'blocktype.cloud/bitbucket'),
            'elements' => array(
                'consumername' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumername', 'blocktype.cloud/bitbucket'),
                    'defaultvalue' => get_config('sitename'),
                    'description'  => get_string('consumernamedesc', 'blocktype.cloud/bitbucket'),
                    'readonly'     => true,
                ),
                'consumeruser' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumeruser', 'blocktype.cloud/bitbucket'),
                    'defaultvalue' => get_config_plugin('blocktype', 'bitbucket', 'consumeruser'),
                    'description'  => get_string('consumeruserdesc', 'blocktype.cloud/bitbucket'),
                ),
            )
        );
        $elements['consumerbackend'] = array(
            'type' => 'fieldset',
            'legend' => get_string('consumerbackend', 'blocktype.cloud/bitbucket'),
            'elements' => array(
                'consumerkey' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumerkey', 'blocktype.cloud/bitbucket'),
                    'defaultvalue' => get_config_plugin('blocktype', 'bitbucket', 'consumerkey'),
                    'description'  => get_string('consumerkeydesc', 'blocktype.cloud/bitbucket'),
                    'size' => 40,
                    'rules' => array('required' => $required),
                ),
                'consumersecret' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumersecret', 'blocktype.cloud/bitbucket'),
                    'defaultvalue' => get_config_plugin('blocktype', 'bitbucket', 'consumersecret'),
                    'description'  => get_string('consumersecretdesc', 'blocktype.cloud/bitbucket'),
                    'size' => 40,
                    'rules' => array('required' => $required),
                ),
            )
        );
        return array(
            'elements' => $elements,
        );

    }

    public static function save_config_options($values) {
        set_config_plugin('blocktype', 'bitbucket', 'consumeruser', $values['consumeruser']);
        set_config_plugin('blocktype', 'bitbucket', 'consumerkey', $values['consumerkey']);
        set_config_plugin('blocktype', 'bitbucket', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /***********************************************
     * Methods & stuff for accessing Bitbucket API *
     ***********************************************/
    
    public function cloud_info() {
        return array(
            'ssl'        => true,
            'version'    => '1.0',
            'baseurl'    => 'https://bitbucket.org/!api/',
            'contenturl' => 'https://api.bitbucket.org/',
            'wwwurl'     => 'https://bitbucket.org/',
        );
    }
    
    public function consumer_tokens() {
        return array(
            'key'      => get_config_plugin('blocktype', 'bitbucket', 'consumerkey'),
            'secret'   => get_config_plugin('blocktype', 'bitbucket', 'consumersecret'),
            'callback' => get_config('wwwroot') . 'artefact/cloud/blocktype/bitbucket/callback.php'
        );
    }
    
    public function user_tokens($userid) {
        return ArtefactTypeCloud::get_user_preferences('bitbucket', $userid);
    }
    
    public function service_list() {
        global $USER;
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            if (isset($usertoken['oauth_token']) && !empty($usertoken['oauth_token']) &&
                isset($usertoken['oauth_token_secret']) && !empty($usertoken['oauth_token_secret'])) {
                return array(
                    'service_name'   => 'bitbucket',
                    'service_url'    => 'http://www.bitbucket.org',
                    'service_auth'   => true,
                    'service_manage' => true,
                    //'revoke_access'  => false,
                );
            } else {
                return array(
                    'service_name'   => 'bitbucket',
                    'service_url'    => 'http://www.bitbucket.org',
                    'service_auth'   => false,
                    'service_manage' => false,
                    //'revoke_access'  => false,
                );
            }
        } else {
            throw new ConfigException('Can\'t find Bitbucket consumer key and/or consumer secret.');
        }
    }
    
    public function request_token() {
        global $USER;
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
            $header[] = build_oauth_header($params, "Bitbucket API PHP Client");
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
                $body  = substr($result->data, $result->info['header_size']);
                $prefs = oauth_parse_str($body);
                ArtefactTypeCloud::set_user_preferences('bitbucket', $USER->get('id'), $prefs);
                redirect($cloud['baseurl'].$cloud['version'].'/oauth/authenticate?'.rfc3986_decode($body));
            } else {
                $SESSION->add_error_msg(get_string('requesttokennotreturned', 'blocktype.cloud/bitbucket'));
            }
        } else {
            throw new ConfigException('Can\'t find Bitbucket consumer key and/or consumer secret.');
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
            $header[] = build_oauth_header($params, "Bitbucket API PHP Client");
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
                // Store access_token (oauth_token) and access_token_secret (outh_token_secret)
                // We'll need it for all API calls...
                $prefs = oauth_parse_str(substr($result->data, $result->info['header_size']));
                ArtefactTypeCloud::set_user_preferences('bitbucket', $USER->get('id'), $prefs);
            } else {
                $SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/bitbucket'));
            }
        } else {
            throw new ConfigException('Can\'t find Bitbucket consumer key and/or consumer secret.');
        }
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('bitbucket', $USER->get('id'), null);
    }
    
    public function revoke_access() {
        // Bitbucket API doesn't allow programmatical access revoking, so:
        // Nothing to do!
    }
    
    /*
     * SEE: https://confluence.atlassian.com/display/BITBUCKET/user+Endpoint#userEndpoint-GETauserprofile
     */
    public function account_info() {
        global $USER;
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['contenturl'].$cloud['version'].'/user';
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
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode($result->data, true);
                // Get followers...
                $url2 = $cloud['contenturl'].$cloud['version'].'/users/'.$data['user']['username'].'/followers';
                $params2 = array(
                    'oauth_version' => '1.0',
                    'oauth_nonce' => mt_rand(),
                    'oauth_timestamp' => time(),
                    'oauth_consumer_key' => $consumer['key'],
                    'oauth_token' => $usertoken['oauth_token'],
                    'oauth_signature_method' => 'HMAC-SHA1',
                );
                $params2['oauth_signature'] = oauth_compute_hmac_sig($method, $url2, $params2, $consumer['secret'], $usertoken['oauth_token_secret']);
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
                $followers = json_decode($result2->data, true);
                // Get following...
                // Bitbucket API actually doesn't support that, so we'll use a hack
                // and read that information from user's homepage/dashboard...
                $url3 = $cloud['wwwurl'].$data['user']['username'];
                $config3 = array(
                    CURLOPT_URL => $url3,
                    CURLOPT_PORT => $port,
                    CURLOPT_POST => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
                );
                $result3 = mahara_http_request($config3);
                $matches = array();
                // Returns first occurance of the string: <span class="aui-badge">XXX</span> // Depreceated
                // Returns first occurance of the string: <span class="count">([0-9]+)</span> Following
                // This XXX number happens to be the number of following.
                // If/when order of followers/following change, use preg_match_all function!

                //preg_match('#<span class="aui-badge">([0-9]+)</span>#', $result3->data, $matches);
                preg_match('#<span class="count">([0-9]+)</span> Following#', $result3->data, $matches);
                $used = 0;
                foreach ($data['repositories'] as $repository) {
                    $used += $repository['size'];
                }
                return array(
                    'service_name' => 'bitbucket',
                    'service_auth' => true,
                    'user_id'      => $data['user']['first_name'].' '.$data['user']['last_name'],
                    'user_name'    => $data['user']['username'],
                    'user_profile' => $cloud['wwwurl'].$data['user']['username'],
                    'space_used'   => bytes_to_size1024($used),
                    'space_amount' => get_string('unlimited', 'blocktype.cloud/bitbucket'),
                    // SEE: https://confluence.atlassian.com/pages/viewpage.action?pageId=273877699
                    'space_ratio'  => number_format(0, 2),
                    'repositories' => count($data['repositories']),
                    'followers'    => $followers['count'],
                    'following'    => (isset($matches[1]) && !empty($matches[1]) ? $matches[1] : '?'),
                );
            } else {
                return array(
                    'service_name' => 'bitbucket',
                    'service_auth' => false,
                    'user_id'      => null,
                    'user_name'    => null,
                    'user_profile' => null,
                    'space_used'   => null,
                    'space_amount' => null,
                    'space_ratio'  => null,
                    'repositories' => null,
                    'followers'    => null,
                    'following'    => null,
                );
            }
        } else {
            throw new ConfigException('Can\'t find Bitbucket consumer key and/or consumer secret.');
        }
    }
    
    /*
     * This function returns list of selected files/folders which will be displayed in a view/page.
     *
     * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
     * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
     *
     * SEE: https://confluence.atlassian.com/display/BITBUCKET/user+Endpoint#userEndpoint-GETalistofrepositories
     */
    public function get_filelist($folder_id='', $selected=array()) {
        global $USER, $THEME;

        // Get folder contents...
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['baseurl'].$cloud['version'].'/user/repositories';
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
                        $fullname = $artefact['owner'].'/'.$artefact['slug'];
                        if (in_array($fullname, $selected)) {
                            $id          = $fullname;
                            $type        = 'repo';
                            $icon        = get_config('wwwroot') . 'artefact/cloud/blocktype/bitbucket/icons/'.$type.'.png';
                            $title       = $artefact['name'];
                            $preview     = $cloud['wwwurl'].$id;
                            if ($type == 'folder') {
                                $output['folders'][] = array('iconsrc' => $icon, 'id' => $id, 'type' => $type, 'title' => $title, 'preview' => $preview);
                            } else {
                                $output['files'][] = array('iconsrc' => $icon, 'id' => $id, 'type' => $type, 'title' => $title, 'preview' => $preview);
                            }
                        }
                    }
                }                    
                return $output;
            } else {
                return array();
            }
         } else {
            throw new ConfigException('Can\'t find Bitbucket consumer key and/or consumer secret.');
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
     * SEE: https://confluence.atlassian.com/display/BITBUCKET/user+Endpoint#userEndpoint-GETalistofrepositories
     */
    public function get_folder_content($folder_id='', $options, $block=0, $fullpath='0|@') {
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
            $url = $cloud['baseurl'].$cloud['version'].'/user/repositories';
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
                        $id       = $artefact['owner'].'/'.$artefact['slug'];
                        $type     = 'repo'; // Since the language of repository is not supported at this level...
                        $icon     = '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/bitbucket/icons/'.$type.'.png">';
                        $title    = '<a class="filedetails" href="details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $artefact['name']) . '">' . $artefact['name'] . '</a>';
                        $controls = '';
                        $selected = (in_array($id, $artefacts) ? ' checked' : '');
                        if ($selectFiles && !$manageButtons) {
                            $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                        } elseif ($manageButtons) {
                            $controls  = '<a class="btn" href="preview.php?id=' . $id . '" target="_blank">' . get_string('preview', 'artefact.cloud') . '</a>';
                            $controls .= '<a class="btn" href="download.php?id=' . $id . '">' . get_string('download', 'artefact.cloud') . '</a>';
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
            throw new ConfigException('Can\'t find Bitbucket consumer key and/or consumer secret.');
        }
    }
    
    public function get_folder_info($folder_id='') {
        // Repositories represented as files...
        // No need for folders, so nothing to do.
    }
    
    /*
     * SEE: https://confluence.atlassian.com/display/BITBUCKET/repo_slug+Resource#repo_slugResource-DELETEanexistingrepository
     * Note: Use GET instead of DELETE!
     */
    public function get_file_info($repo_id='') {
        global $USER;
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['contenturl'].$cloud['version'].'/repositories/'.$repo_id;
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
                    'slug'        => $data['slug'],
                    'repoicon'    => (in_array($type, self::get_available_repo_icons()) ? $type : 'repo'),
                    'name'        => $data['name'],
                    'description' => $data['description'],
                    'owner'       => $data['owner'],
                    'size'        => bytes_to_size1024($data['size']),
                    'bytes'       => $data['size'],
                    'updated'     => format_date(strtotime($data['last_updated']), 'strfdaymonthyearshort'),
                    'created'     => format_date(strtotime($data['created_on']), 'strfdaymonthyearshort'),
                    'language'    => $data['language'],
                );
                return $info;
            }
         } else {
            throw new ConfigException('Can\'t find Bitbucket consumer key and/or consumer secret.');
        }
    }
    
    /*
     * Get the last repository changeset and construct direct download link from it...
     * e.g.: https://bitbucket.org/<username>/<slug>/get/<node>.zip
     *
     * SEE: https://confluence.atlassian.com/display/BITBUCKET/changesets+Resource#changesetsResource-GETalistofchangesets
     */
    public function download_file($repo_id='') {
        global $USER;
        $cloud     = self::cloud_info();
        $consumer  = self::consumer_tokens();
        $usertoken = self::user_tokens($USER->get('id'));
        if (!empty($consumer['key']) && !empty($consumer['secret'])) {
            $url = $cloud['contenturl'].$cloud['version'].'/repositories/'.$repo_id.'/changesets';
            $method = 'GET';
            $port = $cloud['ssl'] ? '443' : '80';
            $params = array(
                'oauth_version' => '1.0',
                'oauth_nonce' => mt_rand(),
                'oauth_timestamp' => time(),
                'oauth_consumer_key' => $consumer['key'],
                'oauth_token' => $usertoken['oauth_token'],
                'oauth_signature_method' => 'HMAC-SHA1',
                // Custom request parameters
                'limit' => 1,
            );
            $params['oauth_signature'] = oauth_compute_hmac_sig($method, $url, $params, $consumer['secret'], $usertoken['oauth_token_secret']);
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
                $node = $data['changesets'][0]['node'];
                // Construct download url, to download file...
                // e.g.: https://bitbucket.org/<username>/<slug>/get/<node>.zip
                redirect($cloud['wwwurl'].$repo_id.'/get/'.$node.'.zip');
            }
         } else {
            throw new ConfigException('Can\'t find Bitbucket consumer key and/or consumer secret.');
        }
    }
    
    public function embed_file($file_id='/', $options=array()) {
        // Bitbucket API doesn't support embedding of files, so:
        // Nothing to do!
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
