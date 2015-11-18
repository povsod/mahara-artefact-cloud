<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-microsoftdrive
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2015 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/oauth.php');


class PluginBlocktypeMicrosoftdrive extends PluginBlocktypeCloud {
    
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/microsoftdrive');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/microsoftdrive');
    }

    public static function get_categories() {
        return array('external');
    }

    public static function render_instance(BlockInstance $instance, $editing=false) {
        $configdata = $instance->get('configdata');
        $viewid     = $instance->get('view');
        
        $view = new View($viewid);
        $ownerid = $view->get('owner');

        $selected = (!empty($configdata['artefacts']) ? $configdata['artefacts'] : array());
        $display  = (!empty($configdata['display']) ? $configdata['display'] : 'list');
        
        $smarty = smarty_core();
        switch ($display) {
            case 'embed':
                $html = '';
                if (!empty($selected)) {
                    foreach ($selected as $artefact) {
                        $html .= self::embed_file($artefact, null, $ownerid);
                    }
                }
                $smarty->assign('embed', $html);
                break;
            case 'list':
            default:
				$file = self::get_file_info($selected[0]);
				$folder = $file['parent_id'];
                $data = self::get_filelist($folder, $selected, $ownerid);
                $smarty->assign('folders', $data['folders']);
                $smarty->assign('files', $data['files']);
        }
        $smarty->assign('viewid', $viewid);
        return $smarty->fetch('blocktype:microsoftdrive:' . $display . '.tpl');
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
        $viewid = $instance->get('view');
        
        $view = new View($viewid);
        $ownerid = $view->get('owner');

        $data = ArtefactTypeCloud::get_user_preferences('microsoftdrive', $ownerid);
        if ($data) {
            return array(
                'microsoftdrivelogo' => array(
                    'type' => 'html',
                    'value' => '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/microsoftdrive/theme/raw/static/images/logo.png">',
                ),
                'microsoftdriveisconnect' => array(
                    'type' => 'cancel',
                    'value' => get_string('revokeconnection', 'blocktype.cloud/microsoftdrive'),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/microsoftdrive/account.php?action=logout',
                ),
                'microsoftdrivefiles' => array(
                    'type'     => 'datatables',
                    'title'    => get_string('selectfiles','blocktype.cloud/microsoftdrive'),
                    'service'  => 'microsoftdrive',
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
                    'title' => get_string('display','blocktype.cloud/microsoftdrive'),
                    'description' => get_string('displaydesc','blocktype.cloud/microsoftdrive') . '<br>' . get_string('displaydesc2','blocktype.cloud/microsoftdrive'),
                    'defaultvalue' => (!empty($configdata['display']) ? hsc($configdata['display']) : 'list'),
                    'options' => array(
                        'list'  => get_string('displaylist','blocktype.cloud/microsoftdrive'),
                        //'icon'  => get_string('displayicon','blocktype.cloud/microsoftdrive'),
                        'embed' => get_string('displayembed','blocktype.cloud/microsoftdrive')
                    ),
                    'separator' => '<br />',
                ),
            );
        }
        else {
            return array(
                'microsoftdrivelogo' => array(
                    'type' => 'html',
                    'value' => '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/microsoftdrive/theme/raw/static/images/logo.png">',
                ),
                'microsoftdriveisconnect' => array(
                    'type' => 'cancel',
                    'value' => get_string('connecttomicrosoftdrive', 'blocktype.cloud/microsoftdrive'),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/microsoftdrive/account.php?action=login',
                ),
            );
        }
    }

    public static function instance_config_save($values) {
        // Folder and file IDs (and other values) are returned as JSON/jQuery serialized string.
        // We have to parse that string and urldecode it (to correctly convert square brackets)
        // in order to get cloud folder and file IDs - they are stored in $artefacts array.
        parse_str(urldecode($values['microsoftdrivefiles']));
        if (!isset($artefacts) || empty($artefacts)) {
            $artefacts = array();
        }
        
        $values = array(
            'title'     => $values['title'],
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
            'value' => get_string('applicationdesc', 'blocktype.cloud/microsoftdrive', '<a href="https://account.live.com/developers/applications" target="_blank">', '</a>'),
        );
        $elements['basicinformation'] = array(
            'type' => 'fieldset',
            'legend' => get_string('basicinformation', 'blocktype.cloud/microsoftdrive'),
            'elements' => array(
                'applicationname' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationname', 'blocktype.cloud/microsoftdrive'),
                    'defaultvalue' => get_config('sitename'),
                    'description'  => get_string('applicationnamedesc', 'blocktype.cloud/microsoftdrive'),
                    'readonly'     => true,
                ),
                'applicationicon' => array(
                    'type'         => 'html',
                    'title'        => get_string('applicationicon', 'blocktype.cloud/microsoftdrive'),
                    'value'        => '<table border="0"><tr style="text-align:center">
                                       <td style="vertical-align:bottom"><img src="'.get_config('wwwroot').'artefact/cloud/icons/048x048.png" border="0" style="border:1px solid #ccc"><br>48x48</td>
                                       </table>',
                    'description'  => get_string('applicationicondesc', 'blocktype.cloud/microsoftdrive'),
                ),
                'applicationterms' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationterms', 'blocktype.cloud/microsoftdrive'),
                    'defaultvalue' => get_config('wwwroot').'terms.php',
                    'size' => 50,
                    'readonly'     => true,
                ),
                'applicationprivacy' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationprivacy', 'blocktype.cloud/microsoftdrive'),
                    'defaultvalue' => get_config('wwwroot').'privacy.php',
                    'size' => 50,
                    'readonly'     => true,
                ),
            )
        );
        $elements['apisettings'] = array(
            'type' => 'fieldset',
            'legend' => get_string('apisettings', 'blocktype.cloud/microsoftdrive'),
            'elements' => array(
                'consumerkey' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumerkey', 'blocktype.cloud/microsoftdrive'),
                    'defaultvalue' => get_config_plugin('blocktype', 'microsoftdrive', 'consumerkey'),
                    'description'  => get_string('consumerkeydesc', 'blocktype.cloud/microsoftdrive'),
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'consumersecret' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumersecret', 'blocktype.cloud/microsoftdrive'),
                    'defaultvalue' => get_config_plugin('blocktype', 'microsoftdrive', 'consumersecret'),
                    'description'  => get_string('consumersecretdesc', 'blocktype.cloud/microsoftdrive'),
                    'size' => 50,
                    'rules' => array('required' => true),
                ),
                'redirecturl' => array(
                    'type'         => 'text',
                    'title'        => get_string('redirecturl', 'blocktype.cloud/microsoftdrive'),
                    'defaultvalue' => get_config('wwwroot'),
                    'description'  => get_string('redirecturldesc', 'blocktype.cloud/microsoftdrive'),
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

    public static function save_config_options($form, $values) {
        set_config_plugin('blocktype', 'microsoftdrive', 'consumerkey', $values['consumerkey']);
        set_config_plugin('blocktype', 'microsoftdrive', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /**************************************************
     * Methods & stuff for accessing Live Connect API *
     **************************************************/
    
    private function get_service_consumer($owner=null) {
        global $USER;
        if (!isset($owner) || is_null($owner)) {
            $owner = $USER->get('id');
        }
        $wwwroot = get_config('wwwroot');
        $service = new StdClass();
        $service->ssl        = true;
        $service->version    = 'v5.0'; // API Version
        $service->apiurl     = 'https://apis.live.net/';
        $service->authurl    = 'https://login.live.com/';
        $service->key        = get_config_plugin('blocktype', 'microsoftdrive', 'consumerkey');
        $service->secret     = get_config_plugin('blocktype', 'microsoftdrive', 'consumersecret');
		// If SSL is set then force SSL URL for callback
		if ($service->ssl) {
            $wwwroot = str_replace('http://', 'https://', get_config('wwwroot'));
		}
        $service->callback   = $wwwroot . 'artefact/cloud/blocktype/microsoftdrive/callback.php';
        $service->usrprefs   = ArtefactTypeCloud::get_user_preferences('microsoftdrive', $owner);
        return $service;
    }

    public function service_list() {
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            if (isset($consumer->usrprefs['refresh_token']) && !empty($consumer->usrprefs['refresh_token'])) {
                return array(
                    'service_name'   => 'microsoftdrive',
                    'service_url'    => 'http://onedrive.live.com',
                    'service_auth'   => true,
                    'service_manage' => true,
                    //'revoke_access'  => true,
                );
            } else {
                return array(
                    'service_name'   => 'microsoftdrive',
                    'service_url'    => 'http://onedrive.live.com',
                    'service_auth'   => false,
                    'service_manage' => false,
                    //'revoke_access'  => false,
                );
            }
        } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }
    
    // SEE: http://msdn.microsoft.com/en-us/library/dn659750.aspx
    // SEE: http://msdn.microsoft.com/en-us/library/dn631845.aspx#types
    public function request_token() {
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->authurl.'oauth20_authorize.srf';
            $scopes = 'wl.signin wl.basic wl.offline_access wl.skydrive';
            $params = array(
                'client_id' => $consumer->key,
                'scope' => $scopes,
                'response_type' => 'code',
                'redirect_uri' => $consumer->callback
            );
            $query = oauth_http_build_query($params);
            $request_url = $url . ($query ? ('?' . $query) : '' );
            redirect($request_url);
        } else {
            throw new ConfigException('Can\'t find Live Connect consumer key and/or consumer secret.');
        }
    }

    // SEE: http://msdn.microsoft.com/en-us/library/dn659750.aspx
    public function access_token($oauth_code) {
        global $SESSION;
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->authurl.'oauth20_token.srf';
            $port = $consumer->ssl ? '443' : '80';
            $scopes = 'wl.basic wl.offline_access wl.skydrive';
            $params = array(
                'client_id' => $consumer->key,
                'redirect_uri' => $consumer->callback,
                'client_secret' => $consumer->secret,
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
                $SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/microsoftdrive'));
            }
        } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }

    // SEE: http://msdn.microsoft.com/en-us/library/dn659750.aspx
    public function check_access_token($owner=null) {
        global $USER, $SESSION;
        $consumer = self::get_service_consumer($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $tokendate = get_field('artefact', 'mtime', 'artefacttype', 'cloud', 'title', 'microsoftdrive', 'owner', $USER->get('id'));
            // Find out when access token actually expires and take away 10 seconds
            // to avoid access token expiry problems between API calls... 
            $valid = strtotime($tokendate) + intval($consumer->usrprefs['expires_in']) - 10;
            $now = time();
            // If access token is expired, than get new one using refresh token
            // save it and return it...
            if ($valid < $now) {
                $url = $consumer->authurl.'oauth20_token.srf';
                $port = $consumer->ssl ? '443' : '80';
                //$scopes = 'wl.basic wl.offline_access wl.skydrive';
                $params   = array(
                    'client_id' => $consumer->key,
                    'redirect_uri' => $consumer->callback,
                    'client_secret' => $consumer->secret,
                    'refresh_token' => $consumer->usrprefs['refresh_token'],
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
                    // These values are set only on user consent and are not sent anytime later
                    $prefs['refresh_token'] = $consumer->usrprefs['refresh_token'];
                    $prefs['authentication_token'] = $consumer->usrprefs['authentication_token'];

                    ArtefactTypeCloud::set_user_preferences('microsoftdrive', $USER->get('id'), $prefs);
                    return $prefs['access_token'];
                } else {
                    $SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/microsoftdrive'));
                    return null;
                }
            }
            // If access token is not expired, than return it...
            else {
                return $consumer->usrprefs['access_token'];
            }
        } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('microsoftdrive', $USER->get('id'), null);
    }
    
    // SEE: http://msdn.microsoft.com/en-us/library/dn659750.aspx
    public function revoke_access() {
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->authurl.'oauth20_logout.srf';
            $params = array(
                'client_id' => $consumer->key,
                'redirect_uri' => $consumer->callback
            );
            $query = oauth_http_build_query($params);
            $request_url = $url . ($query ? ('?' . $query) : '' );
            redirect($request_url);
        } else {
            throw new ConfigException('Can\'t find Live Connect consumer ID and/or consumer secret.');
        }
    }
    
    // SEE: http://msdn.microsoft.com/en-us/library/dn659736.aspx
    // SEE: http://msdn.microsoft.com/en-us/library/dn659731.aspx (quota!)
    public function account_info() {
        $consumer = self::get_service_consumer();
        $token = self::check_access_token();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/me';
            $port = $consumer->ssl ? '443' : '80';
            $params = array('access_token' => $token);
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
                $url2 = $consumer->apiurl.$consumer->version.'/me/skydrive/quota';
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
                    'service_name' => 'microsoftdrive',
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
                    'service_name' => 'microsoftdrive',
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
     * SEE: http://msdn.microsoft.com/en-us/library/dn659731.aspx
     *
     */
    public function get_filelist($folder_id='0', $selected=array(), $owner=null) {
        global $THEME;

        // Get folder contents...
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            if (strlen($folder_id) > 1) {
                $url = $consumer->apiurl.$consumer->version.'/'.$folder_id.'/files';
            } else {
                $url = $consumer->apiurl.$consumer->version.'/me/skydrive/files';
            }
            $port = $consumer->ssl ? '443' : '80';
            $params = array('access_token' => $token);
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
                if (isset($data['data']) && !empty($data['data'])) {
                    foreach($data['data'] as $artefact) {
                        if (in_array($artefact['id'], $selected)) {
                            $id          = $artefact['id'];
                            $type        = $artefact['type'];
                            $icontype    = ($artefact['type'] == 'folder' ? 'folder' : 'file');
                            $icon        = $THEME->get_url('images/' . $icontype . '.png');
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
     *
     * $output      array     Function returns JSON encoded array of values that is suitable to feed jQuery Datatables with.
                              jQuery Datatable than draw an enriched HTML table according to values, contained in $output.
     * PLEASE NOTE: For jQuery Datatable to work, the $output array must be properly formatted and JSON encoded.
     *              Please see: http://datatables.net/usage/server-side (Reply from the server)!
     *
     * SEE: http://msdn.microsoft.com/en-us/library/dn659731.aspx
     *
     */
    public function get_folder_content($folder_id=0, $options, $block=0) {
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

        // Get folder parent...
		$parent_id = 0; // either 'root' folder itself or parent is 'root' folder
		$folder = self::get_folder_info($folder_id);
		if (!empty($folder['parent_id'])) {
			$parent_id = $folder['parent_id'];
		}

        // Get folder contents...
        $consumer = self::get_service_consumer();
        $token = self::check_access_token();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            if (strlen($folder_id) > 1) {
                $url = $consumer->apiurl.$consumer->version.'/'.$folder_id.'/files';
            } else {
                $url = $consumer->apiurl.$consumer->version.'/me/skydrive/files';
            }
            $port = $consumer->ssl ? '443' : '80';
            $params = array('access_token' => $token);
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
                if ($parent_id != '0') {
                    $type        = 'parentfolder';
                    $foldername  = get_string('parentfolder', 'artefact.file');
                    $title       = '<a class="changefolder" href="javascript:void(0)" id="' . $parent_id . '" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '"><img src="' . get_config('wwwroot') . 'artefact/cloud/theme/raw/static/images/parentfolder.png"></a>';
                    $output['aaData'][] = array('', $title, '', $type);
                }
                if (!empty($data['data'])) {
                    foreach($data['data'] as $artefact) {
                        $id           = $artefact['id'];
                        $type         = ($artefact['type'] == 'folder' ? 'folder' : 'file');
                        $icon         = '<img src="' . $THEME->get_url('images/' . $type . '.png') . '">';
                        // Get artefactname by removing parent path from beginning...
                        $artefactname = $artefact['name'];
                        if ($artefact['type'] == 'folder') {
                            $title    = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $artefactname) . '">' . $artefactname . '</a>';
                        } else {
                            $title    = '<a class="filedetails" href="' . get_config('wwwroot') . 'artefact/cloud/blocktype/microsoftdrive/details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $artefactname) . '">' . $artefactname . '</a>';
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
                                $controls  = '<div class="btns3">';
                                $controls .= '<a title="' . get_string('preview', 'artefact.cloud') . '" href="preview.php?id=' . $id . '" target="_blank"><img src="' . get_config('wwwroot') . 'artefact/cloud/theme/raw/static/images/btn_preview.png" alt="' . get_string('preview', 'artefact.cloud') . '"></a>';
                                if ($artefact['type'] == 'file') {
                                    $controls .= '<a title="' . get_string('save', 'artefact.cloud') . '" href="download.php?id=' . $id . '&save=1"><img src="' . get_config('wwwroot') . 'artefact/cloud/theme/raw/static/images/btn_save.png" alt="' . get_string('save', 'artefact.cloud') . '"></a>';
                                    $controls .= '<a title="' . get_string('download', 'artefact.cloud') . '" href="download.php?id=' . $id . '"><img src="' . get_config('wwwroot') . 'artefact/cloud/theme/raw/static/images/btn_download.png" alt="' . get_string('download', 'artefact.cloud') . '"></a>';
                                } else {
                                    $controls .= '<img src="' . $THEME->get_url('images/private_bkgd.png') . '" width="64" height="24">';
                                }
                                $controls .= '</div>';
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

    // SEE: http://msdn.microsoft.com/en-us/library/dn659731.aspx#read_a_folder_s__properties
    public function get_folder_info($folder_id=0, $owner=null) {
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            if (strlen($folder_id) > 1) {
                $url = $consumer->apiurl.$consumer->version.'/'.$folder_id;
            } else {
                $url = $consumer->apiurl.$consumer->version.'/me/skydrive';
            }
            $port = $consumer->ssl ? '443' : '80';
            $params = array('access_token' => $token);
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
                    'parent_id'   => $data['parent_id'],
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

    // SEE: http://msdn.microsoft.com/en-us/library/dn659731.aspx#read_a_file_s_properties
    public function get_file_info($file_id=0, $owner=null) {
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/'.$file_id;
            $port = $consumer->ssl ? '443' : '80';
            $params = array('access_token' => $token);
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
                    'parent_id'   => $data['parent_id'],
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

    // SEE: http://msdn.microsoft.com/en-us/library/dn659731.aspx
    // SEE: http://msdn.microsoft.com/en-us/library/dn659726.aspx#download_a_file
    public function download_file($file_id=0, $owner=null) {
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            // Construct download, to download file...
            $download_url = $consumer->apiurl.$consumer->version.'/'. $file_id.'/content?access_token='.str_replace('%7E', '~', rawurlencode($token));
            $result = '';
            $header = array();
            $header[] = 'User-Agent: Live Connect API PHP Client';
            $header[] = 'Host: apis.live.net';
            $port = $consumer->ssl ? '443' : '80';   
            $ch = curl_init($download_url);
            curl_setopt($ch, CURLOPT_PORT, $port);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
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

    // SEE: http://msdn.microsoft.com/en-us/library/dn659731.aspx#get_links_to_files_and_folders
    public function embed_file($file_id=0, $options=array(), $owner=null) {
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/'.$file_id.'/embed';
            $port = $consumer->ssl ? '443' : '80';
            $params = array('access_token' => $token);
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

    // SEE: http://msdn.microsoft.com/en-us/library/dn659731.aspx#get_links_to_files_and_folders
    public function public_url($file_id=0, $owner=null) {
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/'.$file_id.'/shared_read_link';
            $port = $consumer->ssl ? '443' : '80';
            $params = array('access_token' => $token);
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
