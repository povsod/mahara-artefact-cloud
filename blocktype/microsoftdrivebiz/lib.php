<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-microsoftdrivebiz
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2015-2017 Gregor Anzelj, info@povsod.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/oauth.php');


class PluginBlocktypeMicrosoftdrivebiz extends PluginBlocktypeCloud {
    
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/microsoftdrivebiz');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/microsoftdrivebiz');
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
        
        $smarty = smarty_core();
        $file = self::get_file_info($selected[0]);
        $folder = $file['parent_id'];
        $data = self::get_filelist($folder, $selected, $ownerid);
        $smarty->assign('folders', $data['folders']);
        $smarty->assign('files', $data['files']);
        $smarty->assign('viewid', $viewid);
        return $smarty->fetch('blocktype:microsoftdrivebiz:list.tpl');
    }

    public static function has_instance_config() {
        return true;
    }

    public static function instance_config_form($instance) {
        global $USER;
        $instanceid = $instance->get('id');
        $configdata = $instance->get('configdata');
        $allowed = (!empty($configdata['allowed']) ? $configdata['allowed'] : array());
        safe_require('artefact', 'cloud');
        $instance->set('artefactplugin', 'cloud');
        $viewid = $instance->get('view');
        
        $view = new View($viewid);
        $ownerid = $view->get('owner');

        $data = ArtefactTypeCloud::get_user_preferences('office365_files', $ownerid);
        if ($data) {
            return array(
                'microsoftdrivebizlogo' => array(
                    'type' => 'html',
                    'value' => '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/microsoftdrivebiz/theme/raw/static/images/logo.png">',
                ),
                'microsoftdrivebizisconnect' => array(
                    'type' => 'cancel',
                    'value' => get_string('revokeconnection', 'blocktype.cloud/microsoftdrivebiz'),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/microsoftdrivebiz/account.php?action=logout&sesskey=' . $USER->get('sesskey'),
                ),
                'microsoftdrivebizfiles' => array(
                    'type'     => 'datatables',
                    'title'    => get_string('selectfiles','blocktype.cloud/microsoftdrivebiz'),
                    'service'  => 'microsoftdrivebiz',
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
                    'title' => get_string('display','blocktype.cloud/microsoftdrivebiz'),
                    'description' => get_string('displaydesc','blocktype.cloud/microsoftdrivebiz') . '<br>' . get_string('displaydesc2','blocktype.cloud/microsoftdrivebiz'),
                    'defaultvalue' => (!empty($configdata['display']) ? hsc($configdata['display']) : 'list'),
                    'options' => array(
                        'list'  => get_string('displaylist','blocktype.cloud/microsoftdrivebiz'),
                        //'icon'  => get_string('displayicon','blocktype.cloud/microsoftdrivebiz'),
                        'embed' => get_string('displayembed','blocktype.cloud/microsoftdrivebiz')
                    ),
                    'separator' => '<br />',
                ),
            );
        }
        else {
            return array(
                'microsoftdrivebizlogo' => array(
                    'type' => 'html',
                    'value' => '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/microsoftdrivebiz/theme/raw/static/images/logo.png">',
                ),
                'microsoftdrivebizisconnect' => array(
                    'type' => 'cancel',
                    'value' => get_string('connecttomicrosoftdrivebiz', 'blocktype.cloud/microsoftdrivebiz'),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/microsoftdrivebiz/account.php?action=login&view=' . $viewid . '&sesskey=' . $USER->get('sesskey'),
                ),
            );
        }
    }

    public static function instance_config_save($values) {
        // Folder and file IDs (and other values) are returned as JSON/jQuery serialized string.
        // We have to parse that string and urldecode it (to correctly convert square brackets)
        // in order to get cloud folder and file IDs - they are stored in $artefacts array.
        parse_str(urldecode($values['microsoftdrivebizfiles']));
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
        global $THEME;
        $elements = array();
        $elements['applicationdesc'] = array(
            'type'  => 'html',
            'value' => get_string('applicationdesc', 'blocktype.cloud/microsoftdrivebiz', '<a href="https://account.live.com/developers/applications">', '</a>'),
        );
        $elements['applicationdesc2'] = array(
            'type'  => 'html',
            'class' => 'bg-danger text-danger',
            'value' => '<div style="padding:0 10px">' . get_string('applicationdesc2', 'blocktype.cloud/microsoftdrivebiz') . '</div>',
        );
        $elements['basicinformation'] = array(
            'type' => 'fieldset',
            'class' => 'first',
            'collapsible' => true,
            'collapsed' => false,
            'legend' => get_string('basicinformation', 'blocktype.cloud/microsoftdrivebiz'),
            'elements' => array(
                'applicationname' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationname', 'blocktype.cloud/microsoftdrivebiz'),
                    'defaultvalue' => get_config('sitename'),
                    'description'  => get_string('applicationnamedesc', 'blocktype.cloud/microsoftdrivebiz'),
                ),
                'applicationicon' => array(
                    'type'         => 'html',
                    'title'        => get_string('applicationicon', 'blocktype.cloud/microsoftdrivebiz'),
                    'value'        => '<table border="0"><tr style="text-align:center">
                                       <td style="vertical-align:bottom"><img src="'.$THEME->get_url('images/048x048.png', false, 'artefact/cloud').'" border="0" style="border:1px solid #ccc"><br>48x48</td>
                                       </table>',
                    'description'  => get_string('applicationicondesc', 'blocktype.cloud/microsoftdrivebiz'),
                ),
                'applicationterms' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationterms', 'blocktype.cloud/microsoftdrivebiz'),
                    'defaultvalue' => get_config('wwwroot').'terms.php',
                ),
                'applicationprivacy' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationprivacy', 'blocktype.cloud/microsoftdrivebiz'),
                    'defaultvalue' => get_config('wwwroot').'privacy.php',
                ),
            )
        );
        $elements['apisettings'] = array(
            'type' => 'fieldset',
            'class' => 'last',
            'collapsible' => true,
            'collapsed' => false,
            'legend' => get_string('apisettings', 'blocktype.cloud/microsoftdrivebiz'),
            'elements' => array(
                'consumerkey' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumerkey', 'blocktype.cloud/microsoftdrivebiz'),
                    'defaultvalue' => get_config_plugin('blocktype', 'microsoftdrive', 'consumerkey'),
                    'description'  => get_string('consumerkeydesc', 'blocktype.cloud/microsoftdrivebiz'),
                    'rules'        => array('required' => true),
                ),
                'consumersecret' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumersecret', 'blocktype.cloud/microsoftdrivebiz'),
                    'defaultvalue' => get_config_plugin('blocktype', 'microsoftdrive', 'consumersecret'),
                    'description'  => get_string('consumersecretdesc', 'blocktype.cloud/microsoftdrivebiz'),
                    'rules'        => array('required' => true),
                ),
                'redirecturl' => array(
                    'type'         => 'text',
                    'title'        => get_string('redirecturl', 'blocktype.cloud/microsoftdrivebiz'),
                    'defaultvalue' => get_config('wwwroot'),
                    'description'  => get_string('redirecturldesc', 'blocktype.cloud/microsoftdrivebiz'),
                    'rules'        => array('required' => true),
                ),
            )
        );
        return array(
            'class' => 'panel panel-body',
            'elements' => $elements,
        );

    }

    public static function save_config_options($form, $values) {
        set_config_plugin('blocktype', 'microsoftdrivebiz', 'consumerkey', $values['consumerkey']);
        set_config_plugin('blocktype', 'microsoftdrivebiz', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /*****************************************************
     * Methods & stuff for accessing Microsoft Graph API *
     *****************************************************/
    
    private function get_service_consumer($owner=null) {
        global $USER;
        if (!isset($owner) || is_null($owner)) {
            $owner = $USER->get('id');
        }
        $wwwroot = get_config('wwwroot');
        $service = new StdClass();
        $service->ssl           = true;
        $service->aad_version   = 'v2.0'; // Azure Active Directory (AAD) version
        $service->graph_version = 'v1.0'; // Microsoft Graph API version
        $service->graphurl      = 'https://graph.microsoft.com/';
        $service->authurl       = 'https://login.microsoftonline.com/organizations/oauth2/';
        $service->key           = get_config_plugin('blocktype', 'microsoftdrivebiz', 'consumerkey');
        $service->secret        = get_config_plugin('blocktype', 'microsoftdrivebiz', 'consumersecret');
        // If SSL is set then force SSL URL for callback
        if ($service->ssl) {
            $wwwroot = str_replace('http://', 'https://', get_config('wwwroot'));
        }
        $service->callback     = $wwwroot . 'artefact/cloud/blocktype/microsoftdrivebiz/callback.php';
        $service->usrprefs   = ArtefactTypeCloud::get_user_preferences('microsoftdrivebiz', $owner);
        return $service;
    }

    public function service_list() {
        global $SESSION;
        global $USER;
        $consumer = self::get_service_consumer();
        $service = new StdClass();
        $service->name = 'microsoftdrivebiz';
        $service->url = 'https://portal.office.com';
        $service->auth = false;
        $service->manage = false;
        $service->pending = false;

        if (!empty($consumer->key) && !empty($consumer->secret)) {
            if (isset($consumer->usrprefs['refresh_token']) && !empty($consumer->usrprefs['refresh_token'])) {
                $service->auth = true;
                $service->manage = true;
                $service->account = self::account_info();
            }
        }
        else {
            $service->pending = true;
            $SESSION->add_error_msg('Can\'t find Microsoft Graph consumer key and/or consumer secret.');
        }
        return $service;
    }

    // SEE: https://developer.microsoft.com/en-us/graph/docs/concepts/auth_v2_user#2-get-authorization
    public function request_token() {
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->authurl.$consumer->aad_version.'/authorize';
            $scope = 'offline_access user.read files.read';
            $params = array(
                'response_type' => 'code',
                'client_id' => $consumer->key,
                'redirect_uri' => $consumer->callback,
                'response_mode' => 'query',
                'scope' => $scope
            );
            $query = oauth_http_build_query($params);
            $request_url = $url . ($query ? ('?' . $query) : '' );
            redirect($request_url);
        }
        else {
            throw new ConfigException('Can\'t find Microsoft Graph consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developer.microsoft.com/en-us/graph/docs/concepts/auth_v2_user#3-get-a-token
    public function access_token($oauth_code) {
        global $SESSION;
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->authurl.$consumer->aad_version.'/token';
            $port = $consumer->ssl ? '443' : '80';
            $scope = 'offline_access user.read files.read';
            $params = array(
                'client_id' => $consumer->key,
                'client_secret' => $consumer->secret,
                'code' => $oauth_code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $consumer->callback,
                'scope' => $scope
            );
            $query = oauth_http_build_query($params);
            $header = array();
            $header[] = build_oauth_header($params, "Office 365 API PHP Client");
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
            }
            else {
                $SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/microsoftdrivebiz'));
            }
        }
        else {
            throw new ConfigException('Can\'t find Microsoft Graph consumer ID and/or consumer key.');
        }
    }

    // SEE: https://developer.microsoft.com/en-us/graph/docs/concepts/auth_v2_user#5-use-the-refresh-token-to-get-a-new-access-token
    public function check_access_token($owner=null) {
        global $USER, $SESSION;
        $consumer = self::get_service_consumer($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $tokendate = get_field('artefact', 'mtime', 'artefacttype', 'cloud', 'title', 'microsoftdrivebiz', 'owner', $USER->get('id'));
            // Find out when access token actually expires and take away 10 seconds
            // to avoid access token expiry problems between API calls... 
            $valid = strtotime($tokendate) + intval($consumer->usrprefs['expires_in']) - 10;
            $now = time();
            // If access token is expired, than get new one using refresh token
            // save it and return it...
            if ($valid < $now) {
                $url = $consumer->authurl.$consumer->aad_version.'/token';
                $port = $consumer->ssl ? '443' : '80';
                $scope = 'offline_access user.read files.read';
                $params   = array(
                    'client_id' => $consumer->key,
                    'client_secret' => $consumer->secret,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $consumer->usrprefs['refresh_token'],
                    'redirect_uri' => $consumer->callback,
                    'scope' => $scope
                );
                $query = oauth_http_build_query($params);
                $header = array();
                $header[] = build_oauth_header($params, "Office 365 API PHP Client");
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

                    ArtefactTypeCloud::set_user_preferences('microsoftdrivebiz', $USER->get('id'), $prefs);
                    return $prefs['access_token'];
                }
                else {
                    $SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/microsoftdrivebiz'));
                    return null;
                }
            }
            // If access token is not expired, than return it...
            else {
                return $consumer->usrprefs['access_token'];
            }
        }
        else {
            throw new ConfigException('Can\'t find Microsoft Graph consumer ID and/or consumer key.');
        }
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('microsoftdrivebiz', $USER->get('id'), null);
    }
    
    // OneDrive for Business sign out (logout) is not supported.
    // Apparently https://login.microsoftonline.com/organizations/oauth2/v2.0/logout endpoint works anyway...
    public function revoke_access() {
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->authurl.$consumer->aad_version.'/logout';
            $params = array(
                'client_id' => $consumer->key,
                'redirect_uri' => $consumer->callback
            );
            $query = oauth_http_build_query($params);
            $request_url = $url . ($query ? ('?' . $query) : '' );
            redirect($request_url);
        }
        else {
            throw new ConfigException('Can\'t find Microsoft Graph consumer ID and/or consumer key.');
        }
    }
    
    // SEE: https://developer.microsoft.com/en-us/graph/docs/api-reference/v1.0/api/user_get
    // SEE: https://developer.microsoft.com/en-us/graph/docs/api-reference/v1.0/api/drive_get
    public function account_info() {
        global $SESSION;
        $consumer = self::get_service_consumer();
        $token = self::check_access_token();

        $info = new StdClass();
        $info->service_name = 'microsoftdrivebiz';
        $info->service_auth = false;
        $info->user_id      = null;
        $info->user_name    = null;
        $info->user_email   = null;
        $info->user_profile = null;
        $info->space_used   = null;
        $info->space_amount = null;
        $info->space_ratio  = null;

        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->graphurl.$consumer->graph_version.'/me';
            $port = $consumer->ssl ? '443' : '80';
            $header = array();
            $header[] = 'Authorization: Bearer ' . $token;
            $header[] = 'Content-Type: application/json';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $data = json_decode(substr($result->data, $result->info['header_size']), true);

                $url2 = $consumer->graphurl.$consumer->graph_version.'/me/drive';
                $config2 = array(
                    CURLOPT_URL => $url2,
                    CURLOPT_PORT => $port,
                    CURLOPT_HEADER => true,
                    CURLOPT_HTTPHEADER => $header,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
                );
                $result2 = mahara_http_request($config2);
                $quota = json_decode(substr($result2->data, $result2->info['header_size']), true);

                $info->service_name = 'microsoftdrivebiz';
                $info->service_auth = true;
                $info->user_id      = $data['id'];
                $info->user_name    = $data['displayName'];
                $info->user_email   = strtolower($data['mail']);
                $info->space_used   = bytes_to_size1024(floatval($quota['quota']['used']));
                $info->space_amount = bytes_to_size1024(floatval($quota['quota']['total']));
                $info->space_ratio  = number_format((floatval($quota['quota']['used'])/floatval($quota['quota']['total']))*100, 2);
                return $info;
            }
            else {
                $httpstatus = get_http_status($result->info['http_code']);
                $SESSION->add_error_msg($httpstatus);
                log_warn($httpstatus);
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Microsoft Graph consumer ID and/or consumer key.');
        }
        return $info;
    }


    /*
     * This function returns list of selected files/folders which will be displayed in a view/page.
     *
     * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
     * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
     *
     * SEE: https://developer.microsoft.com/en-us/graph/docs/api-reference/v1.0/api/item_list_children
     *
     */
    public function get_filelist($folder_id='root', $selected=array(), $owner=null) {
        global $THEME;

        // $folder_id is globally set to '0', set it to 'root'
        // as it is the OneDrive for Business default root folder ...
        if ($folder_id == '0') {
            $folder_id = 'root';
        }

        // Get folder contents...
        $consumer = self::get_service_consumer();
        $token = self::check_access_token();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->graphurl.$consumer->graph_version.'/me/drive/items/'.$folder_id.'/children';
            $port = $consumer->ssl ? '443' : '80';
            $header = array();
            $header[] = 'Authorization: Bearer ' . $token;
            $header[] = 'Content-Type: application/json';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);

            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode(substr($result->data, $result->info['header_size']), true);
                $output = array(
                    'folders' => array(),
                    'files'   => array()
                );
                if (isset($data['value']) && !empty($data['value'])) {
                    foreach($data['value'] as $artefact) {
                        if (in_array($artefact['id'], $selected)) {
                            $id          = $artefact['id'];
                            $type        = (array_key_exists('folder', $artefact) ? 'folder' : 'file');
                            $title       = $artefact['name'];
                            $description = null;
                            $size        = bytes_to_size1024($artefact['size']);
                            $created     = ($artefact['createdDateTime'] ? format_date(strtotime($artefact['createdDateTime']), 'strftimedaydate') : null);
                            if ($type == 'folder') {
                                $output['folders'][] = array(
                                    'id' => $id,
                                    'title' => $title,
                                    'description' => $description,
                                    'artefacttype' => $type,
                                    'size' => $size,
                                    'ctime' => $created,
                                );
                            }
                            else {
                                $output['files'][] = array(
                                    'id' => $id,
                                    'title' => $title,
                                    'description' => $description, 
                                    'artefacttype' => $type,
                                    'size' => $size,
                                    'ctime' => $created
                                );
                            }
                        }
                    }
                }                    
                return $output;
            }
            else {
                return array();
            }
        }
        else {
            throw new ConfigException('Can\'t find Microsoft Graph consumer ID and/or consumer key.');
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
     * SEE: https://developer.microsoft.com/en-us/graph/docs/api-reference/v1.0/api/item_list_children
     *
     */
    public function get_folder_content($folder_id='root', $options, $block=0) {
        global $USER, $THEME;
        
        // $folder_id is globally set to '0', set it to 'root'
        // as it is the OneDrive for Business default root folder ...
        if ($folder_id == '0') {
            $folder_id = 'root';
        }

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
        $parent_id = 'root'; // either 'root' folder itself or parent is 'root' folder
        $folder = self::get_folder_info($folder_id);
        if (!empty($folder['parent_id'])) {
            $parent_id = $folder['parent_id'];
        }

        // Get folder contents...
        $consumer = self::get_service_consumer();
        $token = self::check_access_token();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->graphurl.$consumer->graph_version.'/me/drive/items/'.$folder_id.'/children';
            $port = $consumer->ssl ? '443' : '80';
            $header = array();
            $header[] = 'Authorization: Bearer ' . $token;
            $header[] = 'Content-Type: application/json';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);

            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode(substr($result->data, $result->info['header_size']), true);
                $output = array();
                $count = 0;
                // Add 'parent' row entry to jQuery Datatable...
                if ($parent_id != 'root') {
                    $type        = 'parentfolder';
                    $foldername  = get_string('parentfolder', 'artefact.file');
                    $icon        = '<span class="icon-level-up icon icon-lg"></span>';
                    $title       = '<a class="changefolder" href="javascript:void(0)" id="' . $parent_id . '" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '">' . $foldername . '</a>';
                    $output['data'][] = array($icon, $title, '', $type);
                }
                if (!empty($data['value'])) {
                    foreach($data['value'] as $artefact) {
                        $id           = $artefact['id'];
                        $type         = (array_key_exists('folder', $artefact) ? 'folder' : 'file');
                        $artefactname = $artefact['name'];
                        if ($type == 'folder') {
                            $icon  = '<span class="icon-folder-open icon icon-lg"></span>';
                            $title    = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $artefactname) . '">' . $artefactname . '</a>';
                        }
                        else {
                            $icon  = '<span class="icon-file icon icon-lg"></span>';
                            $title    = '<a class="filedetails" href="' . get_config('wwwroot') . 'artefact/cloud/blocktype/microsoftdrive/details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $artefactname) . '">' . $artefactname . '</a>';
                        }
                        $controls = '';
                        $selected = (in_array($id, $artefacts) ? ' checked' : '');
                        if ($type == 'folder') {
                            if ($selectFolders) {
                                $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                            }
                        }
                        else {
                            if ($selectFiles && !$manageButtons) {
                                $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                            }
                            elseif ($manageButtons && $type == 'file') {
                                $controls  = '<div class="btn-group">';
                                $controls .= '<a class="btn btn-default btn-xs" title="' . get_string('save', 'artefact.cloud') . '" href="download.php?id=' . $id . '&save=1"><span class="icon icon-floppy-o icon-lg"></span></a>';
                                $controls .= '<a class="btn btn-default btn-xs" title="' . get_string('download', 'artefact.cloud') . '" href="download.php?id=' . $id . '"><span class="icon icon-download icon-lg"></span></a>';
                                $controls .= '</div>';
                            }
                        }
                        $output['data'][] = array($icon, $title, $controls, $type);
                        $count++;
                    }
                }
                $output['iTotalRecords'] = $count;
                $output['iTotalDisplayRecords'] = $count;
                return json_encode($output);
            }
            else {
                return array();
            }
        }
        else {
            throw new ConfigException('Can\'t find Microsoft Graph consumer ID and/or consumer key.');
        }
    }

    // SEE: https://developer.microsoft.com/en-us/graph/docs/api-reference/v1.0/api/item_get
    public function get_folder_info($folder_id='root', $owner=null) {
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->graphurl.$consumer->graph_version.'/me/drive/items/'.$folder_id;
            if ($folder_id == 'root') {
                $url = $consumer->graphurl.$consumer->graph_version.'/me/drive/root';
            }
            $port = $consumer->ssl ? '443' : '80';
            $header = array();
            $header[] = 'Authorization: Bearer ' . $token;
            $header[] = 'Content-Type: application/json';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);

            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode(substr($result->data, $result->info['header_size']), true);
                $info = array(
                    'id'          => $data['id'],
                    'parent_id'   => (isset($data['parentReference']) ? $data['parentReference']['id'] : null),
                    'name'        => $data['name'],
                    'type'        => (array_key_exists('folder', $data) ? 'folder' : $data['@odata.type']),
                    'preview'     => $data['webUrl'],
                    //'description' => $data['description'],
                    'created'     => ($data['createdDateTime'] ? format_date(strtotime($data['createdDateTime']), 'strfdaymonthyearshort') : null),
                    'updated'     => ($data['lastModifiedDateTime'] ? format_date(strtotime($data['lastModifiedDateTime']), 'strfdaymonthyearshort') : null),
                );
                return $info;
            }
            else {
                return null;
            }
        }
        else {
            throw new ConfigException('Can\'t find Microsoft Graph consumer ID and/or consumer key.');
        }
    }

    // SEE: https://developer.microsoft.com/en-us/graph/docs/api-reference/v1.0/api/item_get
    public function get_file_info($file_id='0', $owner=null) {
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->graphurl.$consumer->graph_version.'/me/drive/items/'.$file_id;
            $header = array();
            $header[] = 'Authorization: Bearer ' . $token;
            $header[] = 'Content-Type: application/json';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);

            if ($result->info['http_code'] == 200 && !empty($result->data)) {
                $data = json_decode(substr($result->data, $result->info['header_size']), true);
                $info = array(
                    'id'          => $data['id'],
                    'parent_id'   => (isset($data['parentReference']) ? $data['parentReference']['id'] : null),
                    'name'        => $data['name'],
                    'type'        => (array_key_exists('file', $data) ? 'file' : $data['@odata.type']),
                    'bytes'       => $data['size'],
                    'size'        => bytes_to_size1024($data['size']),
                    'preview'     => $data['webUrl'],
                    //'description' => $data['description'],
                    'created'     => ($data['createdDateTime'] ? format_date(strtotime($data['createdDateTime']), 'strfdaymonthyearshort') : null),
                    'updated'     => ($data['lastModifiedDateTime'] ? format_date(strtotime($data['lastModifiedDateTime']), 'strfdaymonthyearshort') : null),
                );
                return $info;
            }
            else {
                return null;
            }
        }
        else {
            throw new ConfigException('Can\'t find Microsoft Graph consumer ID and/or consumer key.');
        }
    }

    // SEE: https://developer.microsoft.com/en-us/graph/docs/api-reference/v1.0/api/item_downloadcontent
    public function download_file($file_id='0', $owner=null) {
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->graphurl.$consumer->graph_version.'/me/drive/items/'.$file_id.'/content';
            $header = array();
            $header[] = 'Authorization: Bearer ' . $token;
            $header[] = 'Content-Type: application/json';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            $data = substr($result->data, $result->info['header_size']);
            return $data;
        }
        else {
            throw new ConfigException('Can\'t find Microsoft Graph consumer ID and/or consumer key.');
        }
    }

    public function embed_file($file_id='0', $options=array(), $owner=null) {
        // Not supported
    }

}
