<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-picasa
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2017 Gregor Anzelj, info@povsod.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/oauth.php');


class PluginBlocktypePicasa extends PluginBlocktypeCloud {

    public static function get_title() {
        return get_string('title', 'blocktype.cloud/picasa');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/picasa');
    }

    public static function get_categories() {
        return array('external');
    }

    public static function render_instance(BlockInstance $instance, $editing=false, $versioning=false) {
        $configdata = $instance->get('configdata');
        $viewid     = $instance->get('view');
        
        $view = new View($viewid);
        $ownerid = $view->get('owner');

        $selected = (!empty($configdata['artefacts']) ? $configdata['artefacts'] : array());
        $display  = (
            !empty($configdata['display']) && $configdata['display'] === 'embed'
            ? 'embed'
            : 'list'
        );
        $size     = (!empty($configdata['size']) ? $configdata['size'] : '512');
        $frame     = (!empty($configdata['frame']) ? $configdata['frame'] : false);
        $slideshow = (!empty($configdata['slideshow']) ? $configdata['slideshow'] : false);
        
        $smarty = smarty_core();
        $smarty->assign('SERVICE', 'picasa');
        switch ($display) {
            case 'embed':
                $html = '<div class="thumbnails">';
                $options = array(
                    'size' => $size,
                    'frame' => $frame,
                    'slideshow' => $slideshow,
                    'instanceid' => $instance->get('id')
                );
                if (!empty($selected)) {
                    foreach ($selected as $artefact) {
                        list($type, $id) = explode('-', $artefact);
                        if ($type == 'file') {
                            $html .= self::embed_file($id, $options, $ownerid);
                        }
                        if ($type == 'folder') {
                            $html .= self::embed_folder($id, $options, $ownerid);
                        }
                    }
                }
                $html .= '</div>';
                $smarty->assign('embed', $html);
                break;
            case 'list':
                if (!empty($selected)) {
                    list($type, $item) = explode('-', $selected[0]);
                    if ($type == 'file') {
                        $file = self::get_file_info($item);        
                        $folder = $file['parent_id'];
                    }
                    else {
                        $folder = $selected[0];
                    }
                }
                else {
                    $folder = 0;
                }
                $data = self::get_filelist($folder, $selected, $ownerid);
                $smarty->assign('folders', $data['folders']);
                $smarty->assign('files', $data['files']);
                break;
            default:
                log_warn('Invalid display method: {$display}');
                return false;
        }
        $smarty->assign('viewid', $viewid);
        $smarty->assign('SERVICE', 'picasa');
        return $smarty->fetch('artefact:cloud:' . $display . '.tpl');
    }

    public static function has_instance_config() {
        return true;
    }

    public static function instance_config_form(BlockInstance $instance) {
        global $USER;
        $instanceid = $instance->get('id');
        $configdata = $instance->get('configdata');
        $allowed = (!empty($configdata['allowed']) ? $configdata['allowed'] : array());
        safe_require('artefact', 'cloud');
        $instance->set('artefactplugin', 'cloud');
        $viewid = $instance->get('view');
        
        $view = new View($viewid);
        $ownerid = $view->get('owner');
        
        $consumer = self::get_service_consumer();
        if (isset($consumer->usrprefs['access_token']) && !empty($consumer->usrprefs['access_token'])) {
            return array(
                'picasalogo' => array(
                    'type' => 'html',
                    'value' => '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/picasa/theme/raw/static/images/logo.png">',
                ),
                'picasaisconnect' => array(
                    'type' => 'cancel',
                    'value' => get_string('revokeconnection', 'blocktype.cloud/picasa'),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/picasa/account.php?action=logout&sesskey=' . $USER->get('sesskey'),
                ),
                'picasafiles' => array(
                    'type'     => 'datatables',
                    'title'    => get_string('selectfiles','blocktype.cloud/picasa'),
                    'service'  => 'picasa',
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
                    'title' => get_string('display','blocktype.cloud/picasa'),
                    'defaultvalue' => (!empty($configdata['display']) ? hsc($configdata['display']) : 'list'),
                    'options' => array(
                        'list'  => get_string('displaylist','blocktype.cloud/picasa'),
                        'embed' => get_string('displayembed','blocktype.cloud/picasa')
                    ),
                    'separator' => '<br />',
                ),
                'embedoptions' => array(
                    'type'         => 'fieldset',
                    'collapsible'  => true,
                    'collapsed'    => true,
                    'legend'       => get_string('embedoptions', 'blocktype.cloud/picasa'),
                    'elements'     => array(
                        'size' => array(
                            'type' => 'select',
                            'labelhtml' => get_string('size','blocktype.cloud/picasa'),
                            'defaultvalue' => (!empty($configdata['size']) ? hsc($configdata['size']) : '512'),
                            'options' => array(
                                '72-c' => get_string('sizesquare72c','blocktype.cloud/picasa'),
                                '150-c' => get_string('sizesquare150c','blocktype.cloud/picasa'),
                                '110' => get_string('sizethumb110','blocktype.cloud/picasa'),
                                '220' => get_string('sizesmall220','blocktype.cloud/picasa'),
                                '320' => get_string('sizesmall320','blocktype.cloud/picasa'),
                                '512' => get_string('sizemedium512','blocktype.cloud/picasa'),
                                '640' => get_string('sizemedium640','blocktype.cloud/picasa'),
                                '800' => get_string('sizemedium800','blocktype.cloud/picasa'),
                                '1024' => get_string('sizelarge1024','blocktype.cloud/picasa'),
                                '1600' => get_string('sizelarge1600','blocktype.cloud/picasa'),
                                'd' => get_string('sizeoriginal','blocktype.cloud/picasa'),
                            ),
                        ),
                        'frame' => array(
                            'type' => 'checkbox',
                            'labelhtml' => get_string('frame', 'blocktype.cloud/picasa'),
                            'defaultvalue' => (!empty($configdata['frame']) ? hsc($configdata['frame']) : ''),
                        ),
                        'slideshow' => array(
                            'type' => 'checkbox',
                            'labelhtml' => get_string('slideshow', 'blocktype.cloud/picasa'),
                            'defaultvalue' => (!empty($configdata['slideshow']) ? hsc($configdata['slideshow']) : ''),
                        ),
                    ),
                ),
            );
        }
        else {
            return array(
                'picasalogo' => array(
                    'type' => 'html',
                    'value' => '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/picasa/theme/raw/static/images/logo.png">',
                ),
                'picasaisconnect' => array(
                    'type' => 'cancel',
                    'value' => get_string('connecttopicasa', 'blocktype.cloud/picasa'),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/pcasa/account.php?action=login&view=' . $viewid . '&sesskey=' . $USER->get('sesskey'),
                ),
            );
        }
    }

    public static function instance_config_save($values) {
        // Folder and file IDs (and other values) are returned as JSON/jQuery serialized string.
        // We have to parse that string and urldecode it (to correctly convert square brackets)
        // in order to get cloud folder and file IDs - they are stored in $artefacts array.
        parse_str(urldecode($values['picasafiles']), $params);
        if (!isset($params['artefacts']) || empty($params['artefacts'])) {
            $artefacts = array();
        }
        else {
            $artefacts = $params['artefacts'];
        }

        $values = array(
            'title'     => $values['title'],
            'artefacts' => $artefacts,
            'display'   => $values['display'],
            'size'      => $values['size'],
            'frame'     => $values['frame'],
            'slideshow' => $values['slideshow'],
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
        $wwwroot = str_replace('http', 'https', get_config('wwwroot'));
        $wwwroot = str_replace('httpss', 'https', get_config('wwwroot'));
        $elements = array();
        $elements['applicationdesc'] = array(
            'type'  => 'html',
            'value' => get_string('applicationdesc', 'blocktype.cloud/picasa', '<a href="https://console.cloud.google.com/apis/credentials">', '</a>'),
        );
        $elements['webappsclientid'] = array(
            'type' => 'fieldset',
            'class' => 'first',
            'collapsible' => true,
            'collapsed' => false,
            'legend' => get_string('webappsclientid', 'blocktype.cloud/picasa'),
            'elements' => array(
                'consumerkey' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumerkey', 'blocktype.cloud/picasa'),
                    'defaultvalue' => get_config_plugin('blocktype', 'picasa', 'consumerkey'),
                    'description'  => get_string('consumerkeydesc', 'blocktype.cloud/picasa'),
                    'rules'        => array('required' => true),
                ),
                'consumersecret' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumersecret', 'blocktype.cloud/picasa'),
                    'defaultvalue' => get_config_plugin('blocktype', 'picasa', 'consumersecret'),
                    'description'  => get_string('consumersecretdesc', 'blocktype.cloud/picasa'),
                    'rules'        => array('required' => true),
                ),
                'redirecturl' => array(
                    'type'         => 'text',
                    'title'        => get_string('redirecturl', 'blocktype.cloud/picasa'),
                    'defaultvalue' => $wwwroot . 'artefact/cloud/blocktype/picasa/callback.php',
                    'description'  => get_string('redirecturldesc', 'blocktype.cloud/picasa'),
                    'rules'        => array('required' => true),
                ),
            )
        );
        $elements['brandinginformation'] = array(
            'type' => 'fieldset',
            'class' => 'last',
            'collapsible' => true,
            'collapsed' => false,
            'legend' => get_string('brandinginformation', 'blocktype.cloud/picasa'),
            'elements' => array(
                'applicationname' => array(
                    'type'         => 'text',
                    'title'        => get_string('productname', 'blocktype.cloud/picasa'),
                    'defaultvalue' => get_config('sitename'),
                    'description'  => get_string('productnamedesc', 'blocktype.cloud/picasa'),
                ),
                'applicationweb' => array(
                    'type'         => 'text',
                    'title'        => get_string('productweb', 'blocktype.cloud/picasa'),
                    'defaultvalue' => get_config('wwwroot'),
                    'description'  => get_string('productwebdesc', 'blocktype.cloud/picasa'),
                ),
                'applicationiconurl' => array(
                    'type'         => 'text',
                    'title'        => get_string('productlogo', 'blocktype.cloud/picasa'),
                    'defaultvalue' => get_config('wwwroot') . 'artefact/cloud/theme/raw/static/images/120x120.png',
                ),
                'applicationicon' => array(
                    'type'         => 'html',
                    'title'        => null,
                    'value'        => '<table border="0"><tr style="text-align:center">
                                       <td style="vertical-align:bottom"><img src="'.$THEME->get_url('images/120x120.png', false, 'artefact/cloud').'" border="0" style="border:1px solid #ccc"><br>120x120</td>
                                       </table>',
                    'description'  => get_string('productlogodesc', 'blocktype.cloud/picasa'),
                ),
                'privacyurl' => array(
                    'type'         => 'text',
                    'title'        => get_string('privacyurl', 'blocktype.cloud/picasa'),
                    'defaultvalue' => get_config('wwwroot') . 'privacy.php',
                ),
                'termsurl' => array(
                    'type'         => 'text',
                    'title'        => get_string('termsurl', 'blocktype.cloud/picasa'),
                    'defaultvalue' => get_config('wwwroot') . 'terms.php',
                ),
            )
        );
        return array(
            'class' => 'panel panel-body',
            'elements' => $elements,
        );

    }

    public static function save_config_options(Pieform $form, $values) {
        set_config_plugin('blocktype', 'picasa', 'consumerkey', $values['consumerkey']);
        set_config_plugin('blocktype', 'picasa', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /************************************************
     * Methods & stuff for accessing Picasa Web API *
     ************************************************/
    
    private function get_service_consumer($owner=null) {
        global $USER;
        if (!isset($owner) || is_null($owner)) {
            $owner = $USER->get('id');
        }
        $wwwroot = get_config('wwwroot');
        $service = new StdClass();
        $service->ssl        = true;
        $service->version    = 'v2'; // API Version
        $service->baseurl    = 'https://www.googleapis.com/';
        $service->picasaurl  = 'https://picasaweb.google.com/data/feed/api/';
        $service->authurl    = 'https://accounts.google.com/o/oauth2/';
        $service->key        = get_config_plugin('blocktype', 'picasa', 'consumerkey');
        $service->secret     = get_config_plugin('blocktype', 'picasa', 'consumersecret');
        // If SSL is set then force SSL URL for callback
        if ($service->ssl) {
            $wwwroot = str_replace('http://', 'https://', get_config('wwwroot'));
        }
        $service->callback   = $wwwroot . 'artefact/cloud/blocktype/picasa/callback.php';
        $service->usrprefs   = ArtefactTypeCloud::get_user_preferences('google', $owner);
        return $service;
    }

    public function service_list() {
        global $SESSION;
        $consumer = self::get_service_consumer();
        $service = new StdClass();
        $service->name = 'picasa';
        $service->url = 'https://picasaweb.google.com';
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
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
        }
        return $service;
    }
    
    // SEE: https://developers.google.com/accounts/docs/OAuth2WebServer#formingtheurl
    // SEE: https://developers.google.com/picasa-web/docs/2.0/developers_guide_protocol#Audience
    public function request_token() {
        global $SESSION;
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->authurl.'auth';
            $scopes = 'https://www.googleapis.com/auth/drive '
                    . 'https://picasaweb.google.com/data/ '
                    . 'https://www.googleapis.com/auth/userinfo.profile '
                    . 'https://www.googleapis.com/auth/userinfo.email';
            $params = array(
                'client_id' => $consumer->key,
                'scope' => $scopes,
                'response_type' => 'code',
                'access_type' => 'offline',
                'redirect_uri' => $consumer->callback
            );
            $query = oauth_http_build_query($params);
            $request_url = $url . ($query ? ('?' . $query) : '' );
            redirect($request_url);
        }
        else {
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developers.google.com/accounts/docs/OAuth2WebServer#handlingtheresponse
    public function access_token($oauth_code) {
        global $SESSION;
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->authurl.'token';
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'client_id' => $consumer->key,
                'redirect_uri' => $consumer->callback,
                'client_secret' => $consumer->secret,
                'code' => $oauth_code,
                'grant_type' => 'authorization_code',
            );
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => oauth_http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $data = json_decode($result->data, true);
                return $data;
            }
            else {
                $SESSION->add_error_msg(get_string('accesstokennotreturned', 'artefact.cloud'));
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developers.google.com/accounts/docs/OAuth2WebServer#refresh
    public function check_access_token($owner=null) {
        global $USER, $SESSION;
        $consumer = self::get_service_consumer($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            // Find out when access token actually expires and take away 10 seconds
            // to avoid access token expiry problems between API calls... 
            $valid = strtotime($consumer->usrprefs['record_ctime']) + intval($consumer->usrprefs['expires_in']) - 10;
            $now = time();
            // If access token is expired, than get new one using refresh token
            // save it and return it...
            if ($valid < $now) {
                $url = $consumer->authurl.'token';
                $port = $consumer->ssl ? '443' : '80';
                $params = array(
                    'client_id' => $consumer->key,
                    'client_secret' => $consumer->secret,
                    'refresh_token' => $consumer->usrprefs['refresh_token'],
                    'grant_type' => 'refresh_token',
                );
                $config = array(
                    CURLOPT_URL => $url,
                    CURLOPT_PORT => $port,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => oauth_http_build_query($params),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
                );
                $result = mahara_http_request($config);
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                    $prefs = json_decode($result->data, true);
                    // Request for new access_token doesn't return refresh_token at all!
                    // Add refresh_token so we'll be able to get new access_token in the future...
                    $prefs = array_merge($prefs, array('refresh_token' => $consumer->usrprefs['refresh_token']));
                    ArtefactTypeCloud::set_user_preferences('google', $USER->get('id'), $prefs);
                    return $prefs['access_token'];
                }
            }
            // If access token is not expired, than return it...
            else {
                return $consumer->usrprefs['access_token'];
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
        }
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('google', $USER->get('id'), null);
    }
    
    // SEE: https://developers.google.com/accounts/docs/OAuth2WebServer#tokenrevoke
    public function revoke_access() {
        global $SESSION;
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            // Construct revoke url, for revokin access...
            $revoke_url = $consumer->authurl . 'revoke?token=' . str_replace('%7E', '~', rawurlencode($consumer->usrprefs['access_token']));
            $port = $consumer->ssl ? '443' : '80';
            $ch = curl_init($revoke_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_PORT, $port);
            curl_setopt($ch, CURLOPT_POST, false);
            $result = curl_exec($ch);
            curl_close($ch);
        }
        else {
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
        }
    }
    
    // SEE: https://developers.google.com/accounts/docs/OAuth2Login#userinfocall
    // SEE: https://developers.google.com/drive/v2/reference/about/get (quota and other user info)
    // SEE: https://developers.google.com/picasa-web/faq#quota         (picasa web quota)
    public function account_info() {
        global $SESSION;
        $consumer = self::get_service_consumer();
        $token = self::check_access_token();

        $info = new StdClass();
        $info->service_name = 'picasa';
        $info->service_auth = false;
        $info->user_id      = null;
        $info->user_name    = null;
        $info->user_email   = null;
        $info->user_profile = null;
        $info->space_used   = null;
        $info->space_amount = null;
        $info->space_ratio  = null;

        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->baseurl.'oauth2/v1/userinfo';
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
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $data = json_decode($result->data, true);
                // Get user's quota information...
                $url2 = $consumer->picasaurl.'user/default';
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
                // Also get all the data from 'gphoto' namespace
                $gphoto = array();
                $xml = new DOMDocument();
                $xml->loadXML($result2->data);
                foreach ($xml->getElementsByTagNameNS('http://schemas.google.com/photos/2007', '*') as $element) {
                    $gphoto[$element->localName] = $element->nodeValue;
                }

                $info->service_name = 'picasa';
                $info->service_auth = true;
                $info->user_id      = $data['id'];
                $info->user_name    = $data['name'];
                $info->user_email   = $data['email'];
                $info->user_profile = $data['link'];
                $info->space_used   = bytes_to_size1024(floatval($gphoto['quotacurrent']));
                $info->space_amount = bytes_to_size1024(floatval($gphoto['quotalimit']));
                $info->space_ratio  = number_format((floatval($gphoto['quotacurrent'])/floatval($gphoto['quotalimit']))*100, 2);
                return $info;
            }
            else {
                $httpstatus = get_http_status($result->info['http_code']);
                $SESSION->add_error_msg($httpstatus);
                log_warn($httpstatus);
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
        }
    }
    
    
    /*
     * This function returns list of selected files/folders which will be displayed in a view/page.
     *
     * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
     * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
     *
     * SEE: https://developers.google.com/picasa-web/docs/2.0/developers_guide_protocol#ListAlbums
     * SEE: https://developers.google.com/picasa-web/docs/2.0/developers_guide_protocol#ListPhotos
     *
     */
    public function get_filelist($folder_id=0, $selected=array(), $owner=null) {
        global $SESSION;

        // Get folder contents...
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            if ($folder_id == 0) {
                $url = $consumer->picasaurl.'user/default';
            }
            else {
                $url = $consumer->picasaurl.'user/default/albumid/'.$folder_id;
            }
            $port = $consumer->ssl ? '443' : '80';
            $params = array('access_token' => $token);
            // Automatically created albums have gphoto:albumType property set,
            // so select only albums/entries without gphoto:albumType property!
            // Get number of photos in an album with gphoto:numphotos property.
            if ($folder_id == 0) {
                $params['fields'] = 'gphoto:*,entry[not(gphoto:albumType)]';
            }
            else {
                $params['fields'] = 'entry[gphoto:size]';
            }
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
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $data = oauth_parse_xml($result->data);
                $output = array(
                    'folders' => array(),
                    'files'   => array()
                );
                if ($folder_id == 0) {
                    $sizes = array();
                    $xml = new DOMDocument();
                    $xml->loadXML($result->data);
                    foreach ($xml->getElementsByTagNameNS('http://schemas.google.com/photos/2007', '*') as $element) {
                        // Save sizes of all albums from 'gphoto:bytesUsed'
                        // into array with same keys as $albums array...
                        if ($element->localName == 'bytesUsed') {
                            $sizes[] = $element->nodeValue;
                        }
                    }
                    $albums = $data['entry'];
                    $i = 0;
                    foreach($albums as $album) {
                        $id = basename($album['id']);
                        if (in_array('folder-'.$id, $selected)) {
                            $title       = $album['title'];
                            $description = '';
                            $size        = bytes_to_size1024($sizes[$i]);
                            $ctime       = format_date(strtotime($album['published']), 'strftimedaydate');
                            $files       = self::get_folder_files($id);
                            $output['folders'][] = array(
                                'id' => $id,
                                'title' => $title,
                                'description' => $description,
                                'artefacttype' => 'folder-open',
                                'size' => $size,
                                'ctime' => $ctime,
                                'files' => $files,
                            );
                        }
                        $i++;
                    }
                }
                else {
                    $sizes = array();
                    $xml = new DOMDocument();
                    $xml->loadXML($result->data);
                    foreach ($xml->getElementsByTagNameNS('http://schemas.google.com/photos/2007', '*') as $element) {
                        // Save sizes of all photos from 'gphoto:size' into
                        // array with same keys as $photos array...
                        if ($element->localName == 'size') {
                            $sizes[] = $element->nodeValue;
                        }
                    }
                    if (count($sizes) == 1) {
                        // If there is only one photo in the album
                        $photos = array('0' => $data['entry']);
                    }
                    else {
                        $photos = $data['entry'];
                    }
                    $i = 0;
                    foreach($photos as $photo) {
                        $id = basename($photo['id']);
                        if (in_array('file-'.$id, $selected)) {
                            $type        = 'file';
                            $title       = $photo['title'];
                            $description = (isset($data['subtitle']) && !is_array($data['subtitle']) ? $data['subtitle'] : null);
                            $size        = bytes_to_size1024($sizes[$i]);
                            $ctime       = format_date(strtotime($photo['published']), 'strftimedaydate');
                            $output['files'][] = array(
                                'id' => $id,
                                'type' => $type,
                                'title' => $title,
                                'description' => $description,
                                'artefacttype' => 'picture-o',
                                'size' => $size,
                                'ctime' => $ctime,
                            );
                        }
                        $i++;
                    }
                }
                return $output;
            }
            else {
                $httpstatus = get_http_status($result->info['http_code']);
                $SESSION->add_error_msg($httpstatus);
                log_warn($httpstatus);
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
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
     * SEE: https://developers.google.com/picasa-web/docs/2.0/developers_guide_protocol#ListAlbums
     * SEE: https://developers.google.com/picasa-web/docs/2.0/developers_guide_protocol#ListPhotos
     * SEE: https://groups.google.com/forum/#!topic/google-picasa-data-api/ByMRtHtWqhk  (Exclude automatically created Albums)
     *
     */
    public function get_folder_content($folder_id=0, $options, $block=0) {
        global $SESSION;
        
        // $folder_id is globally set to '0', set it to 0
        // as it is the Picasa default root folder ...
        if ($folder_id == '0') {
            $folder_id = 0;
        }

        // Get selected artefacts (folders and/or files)
        if ($block > 0) {
            $data = unserialize(get_field('block_instance', 'configdata', 'id', $block));
            if (!empty($data) && isset($data['artefacts'])) {
                $artefacts = $data['artefacts'];
            }
            else {
                $artefacts = array();
            }
        }
        else {
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
            if ($folder_id == 0) {
                $url = $consumer->picasaurl.'user/default';
            }
            else {
                $url = $consumer->picasaurl.'user/default/albumid/'.$folder_id;
            }
            $port = $consumer->ssl ? '443' : '80';
            $params = array('access_token' => $token);
            // Automatically created albums have gphoto:albumType property set,
            // so select only albums/entries without gphoto:albumType property!
            // Get number of photos in an album with gphoto:numphotos property.
            if ($folder_id == 0) {
                $params['fields'] = 'gphoto:*,entry[not(gphoto:albumType)]';
            }
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
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $data = oauth_parse_xml($result->data);
                $output = array();
                $count = 0;
                // Add 'parent' row entry to jQuery Datatable...
                //if (strlen($_SESSION[self::servicepath]) > 6) {
                if ($folder_id != 0) {
                    $type        = 'parentfolder';
                    $foldername  = get_string('parentfolder', 'artefact.file');
                    $icon        = '<span class="icon icon-level-up icon-lg"></span>';
                    $title       = '<a class="changefolder" href="javascript:void(0)" id="' . $parent_id . '" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '">' . $foldername . '</a>';
                    $output['data'][] = array($icon, $title, '', $type);
                }
                if ($folder_id == 0) {
                    $albums = $data['entry'];
                    foreach($albums as $album) {
                        $id    = basename($album['id']);
                        $type  = 'folder';
                        $icon  = '<span class="icon-folder-open icon icon-lg"></span>';
                        $title = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $album['title']) . '">' . $album['title'] . '</a>';
                        if ($selectFolders) {
                            $selected = (in_array('folder-'.$id, $artefacts) ? ' checked' : '');
                            $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="folder-' . $id . '"' . $selected . '>';
                        }
                        else {
                            $controls = '';
                        }
                        $output['data'][] = array($icon, $title, $controls, $type);
                        $count++;
                    }
                }
                else {
                    // Get gphoto:numphotos from 'gphoto' namespace
                    $numphotos = 1;
                    $xml = new DOMDocument();
                    $xml->loadXML($result->data);
                    foreach ($xml->getElementsByTagNameNS('http://schemas.google.com/photos/2007', '*') as $element) {
                        if ($element->localName == 'numphotos') {
                            $numphotos = $element->nodeValue;
                        }
                    }
                    if ($numphotos == 1) {
                        // If there is only one photo in the album
                        $photos = array('0' => $data['entry']);
                    }
                    else {
                        $photos = $data['entry'];
                    }
                    foreach($photos as $photo) {
                        $id    = basename($photo['id']);
                        $type  = 'file';
                        $icon  = '<span class="icon-picture-o icon icon-lg"></span>';
                        $title = '<a class="filedetails" href="' . get_config('wwwroot') . 'artefact/cloud/blocktype/picasa/details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $photo['title']) . '">' . $photo['title'] . '</a>';
                        if ($selectFiles && !$manageButtons) {
                            $selected = (in_array('file-'.$id, $artefacts) ? ' checked' : '');
                            $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="file-' . $id . '"' . $selected . '>';
                        }
                        elseif ($manageButtons) {
                            $controls  = '<div class="btn-group">';
                            $controls .= '<a class="btn btn-default btn-xs" title="' . get_string('save', 'artefact.cloud') . '" href="download.php?id=' . $id . '&save=1"><span class="icon icon-floppy-o icon-lg"></span></a>';
                            $controls .= '<a class="btn btn-default btn-xs" title="' . get_string('download', 'artefact.cloud') . '" href="download.php?id=' . $id . '"><span class="icon icon-download icon-lg"></span></a>';
                            $controls .= '</div>';
                        }
                        else {
                            $controls = '';
                        }
                        $output['data'][] = array($icon, $title, $controls, $type);
                        $count++;
                    }
                }
                $output['recordsTotal'] = $count;
                $output['recordsFiltered'] = $count;
                return json_encode($output);
            }
            else {
                $httpstatus = get_http_status($result->info['http_code']);
                $SESSION->add_error_msg($httpstatus);
                log_warn($httpstatus);
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developers.google.com/picasa-web/docs/2.0/developers_guide_protocol#ListPhotos
    public function get_folder_info($folder_id=0, $owner=null) {
        global $SESSION;
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            if ($folder_id == 0) {
                $url = $consumer->picasaurl.'user/default';
            }
            else {
                $url = $consumer->picasaurl.'user/default/albumid/'.$folder_id;
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
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $data = oauth_parse_xml($result->data);
                $previewurl = '';
                // Extract preview link from array of different album links
                foreach ($data['link'] as $link) {
                    if ($link['@attributes']['rel'] == 'alternate') {
                        $previewurl = $link['@attributes']['href'];
                    }
                }
                // Also get all the data from 'gphoto' namespace
                $gphoto = array();
                $xml = new DOMDocument();
                $xml->loadXML($result->data);
                foreach ($xml->getElementsByTagNameNS('http://schemas.google.com/photos/2007', '*') as $element) {
                    $gphoto[$element->localName] = $element->nodeValue;
                }
                $info = array(
                    'id'          => $folder_id,
                    'parent_id'   => 0, // All albums have one parent = 'root' folder
                    'name'        => $data['title'],
                    'shared'      => (isset($data['rights']) ? $data['rights'] : null),
                    'size'        => $gphoto['bytesUsed'],
                    'description' => (!empty($data['subtitle']) && is_string($data['subtitle']) ? $data['subtitle'] : null),
                    'created'     => ($gphoto['timestamp'] ? format_date(substr($gphoto['timestamp'], 0, -3), 'strfdaymonthyearshort') : null),
                    'updated'     => ($data['updated'] ? format_date(strtotime($data['updated']), 'strfdaymonthyearshort') : null),
                );
                return $info;
            }
            else {
                $httpstatus = get_http_status($result->info['http_code']);
                $SESSION->add_error_msg($httpstatus);
                log_warn($httpstatus);
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
        }
    }

    // Get all the photos that are contained in a specified album
    // SEE: https://developers.google.com/picasa-web/docs/2.0/developers_guide_protocol#ListPhotos
    public function get_folder_files($folder_id=0, $owner=null) {
        global $SESSION;
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->picasaurl.'user/default/albumid/'.$folder_id;
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'access_token' => $token,
                'fileds' => 'entry[gphoto:size]'
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
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $data = oauth_parse_xml($result->data);
                $files = array();
                $sizes = array();
                $xml = new DOMDocument();
                $xml->loadXML($result->data);
                foreach ($xml->getElementsByTagNameNS('http://schemas.google.com/photos/2007', '*') as $element) {
                    // Save sizes of all photos from 'gphoto:size' into
                    // array with same keys as $photos array...
                    if ($element->localName == 'size') {
                        $sizes[] = $element->nodeValue;
                    }
                }
                if (count($sizes) == 1) {
                    // If there is only one photo in the album
                    $photos = array('0' => $data['entry']);
                }
                else {
                    $photos = $data['entry'];
                }
                $i = 0;
                if (isset($photos) && !empty($photos)) {
                    foreach($photos as $photo) {
                        $id          = basename($photo['id']);
                        $type        = 'file';
                        $title       = $photo['title'];
                        $description = (!empty($photo['summary']) && is_string($photo['summary']) ? $photo['summary'] : null);
                        $size        = bytes_to_size1024($sizes[$i]);
                        $ctime       = format_date(strtotime($photo['published']), 'strftimedaydate');
                        $files[]     = array(
                            'id' => $id,
                            'type' => $type,
                            'title' => $title,
                            'description' => $description,
                            'artefacttype' => 'picture-o',
                            'size' => $size,
                            'ctime' => $ctime,
                        );
                        $i++;
                    }
                }
                return $files;
            }
            else {
                $httpstatus = get_http_status($result->info['http_code']);
                $SESSION->add_error_msg($httpstatus);
                log_warn($httpstatus);
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developers.google.com/picasa-web/docs/2.0/developers_guide_protocol#UpdatePhotos
    // SEE: https://developers.google.com/picasa-web/docs/2.0/reference#Parameters   (imgmax parameter!)
    public function construct_photo_url($thumbnail_url='', $size) {
        global $SESSION;
        // Picasa offers much more valid sizes for uploaded photos.
        // The selected sizes match the ones that Flickr offers...
        $validsizes = array(
            '72-c',  // small square 72x72
            '150-c', // large square 150x150
            '110',   // thumbnail, 100 on longest side
            '220',   // small, 220 on longest side
            '320',   // small, 320 on longest side
            '512',   // medium, 512 on longest side
            '640',   // medium 640, 640 on longest side
            '800',   // medium 800, 800 on longest side
            '1024',  // large, 1024 on longest side
            '1600',  // large 1600, 1600 on longest side
            'd',     // original image
        );
        if (empty($thumbnail_url)) {
            throw new ParameterException("Cannot generate URL to Picasa photo");
        }
        if (in_array($size, $validsizes)) {
            $oldsize = '/s72/';  // The size part of $thumbnail_url
            $newsize = '/s' . $size . '/';
            // To "create" correct photo URL just replace the old size with the new size.
            // That way we can trim down the number of needed requests to Picasa Web API.
            return str_replace($oldsize, $newsize, $thumbnail_url);
        }
        else {
            $SESSION->add_error_msg('Undefined Picasa photo size: $size');
        }
    }

    // SEE: https://developers.google.com/picasa-web/docs/2.0/developers_guide_protocol#UpdatePhotos
    // SEE: https://developers.google.com/picasa-web/docs/2.0/reference#Parameters  (imgmax parameter!)
    public function get_file_info($file_id=0, $owner=null) {
        global $SESSION;
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->picasaurl.'user/default/photoid/'.$file_id;
            $port = $consumer->ssl ? '443' : '80';
            $params = array('access_token' => $token, 'imgmax' => 'd');
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
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $data = oauth_parse_xml($result->data);
                // Extract preview link from array of different album links
                foreach ($data['link'] as $link) {
                    if ($link['@attributes']['rel'] == 'alternate') {
                        $previewurl = $link['@attributes']['href'];
                    }
                }
                // Also get all the data from 'gphoto' namespace
                $gphoto = array();
                $xml = new DOMDocument();
                $xml->loadXML($result->data);
                foreach ($xml->getElementsByTagNameNS('http://schemas.google.com/photos/2007', '*') as $element) {
                    $gphoto[$element->localName] = $element->nodeValue;
                }
                // Also get all the data from 'media' namespace
                $media = array();
                $xml = new DOMDocument();
                $xml->loadXML($result->data);
                foreach ($xml->getElementsByTagNameNS('http://search.yahoo.com/mrss/', '*') as $element) {
                    if ($element->hasAttribute('url') && $element->hasAttribute('width') && $element->hasAttribute('height')) {
                        $media[$element->localName][] = array(
                            'url'    => $element->getAttribute('url'),
                            'width'  => $element->getAttribute('width'),
                            'height' => $element->getAttribute('height'),
                        );
                    }
                    else {
                        $media[$element->localName] = $element->nodeValue;
                    }
                }
                $info = array(
                    'id'          => $file_id,
                    'parent_id'   => $gphoto['albumid'],
                    'name'        => $data['title'],
                    'width'       => $gphoto['width'],
                    'height'      => $gphoto['height'],
                    'bytes'       => ($gphoto['size'] > 0 ? $gphoto['size'] : '-'),
                    'size'        => ($gphoto['size'] > 0 ? bytes_to_size1024($gphoto['size']) : '-'),
                    'shared'      => $gphoto['access'], 
                    'description' => (isset($data['subtitle']) && is_string($data['subtitle']) ? $data['subtitle'] : null),
                    'created'     => ($gphoto['timestamp'] ? format_date(substr($gphoto['timestamp'], 0, -3), 'strfdaymonthyearshort') : null),
                    'updated'     => ($data['updated'] ? format_date(strtotime($data['updated']), 'strfdaymonthyearshort') : null),
                    // URL to original photo (imgmax = 'd')
                    'downloadurl' => (isset($media['content'][0]['url']) ? $media['content'][0]['url'] : null),
                    // URL to smallest thumbnail (imgmax = '72')                    
                    'previewurl'  => (isset($media['thumbnail'][0]['url']) ? $media['thumbnail'][0]['url'] : null),
                );
                return $info;
            }
            else {
                $httpstatus = get_http_status($result->info['http_code']);
                $SESSION->add_error_msg($httpstatus);
                log_warn($httpstatus);
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developers.google.com/picasa-web/docs/2.0/developers_guide_protocol#UpdatePhotos
    // SEE: https://developers.google.com/picasa-web/docs/2.0/reference#Parameters   (imgmax parameter!)
    public function download_file($file_id=0, $owner=null) {
        global $SESSION;
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->picasaurl.'user/default/photoid/'.$file_id;
            $port = $consumer->ssl ? '443' : '80';
            $params = array('access_token' => $token, 'imgmax' => 'd');
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
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $download_url = '';
                $xml = new DOMDocument();
                $xml->loadXML($result->data);
                foreach ($xml->getElementsByTagNameNS('http://search.yahoo.com/mrss/', '*') as $element) {
                    if ($element->localName == 'content' && $element->hasAttribute('url')) {
                        $download_url = $element->getAttribute('url');
                    }
                }
                if (empty($download_url)) {
                    throw new NotFoundException("Picasa photo download URL not found.");
                }
                $result = '';
                $ch = curl_init($download_url);
                curl_setopt($ch, CURLOPT_PORT, $port);
                curl_setopt($ch, CURLOPT_POST, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_CAINFO, get_config('docroot').'artefact/cloud/cert/cacert.crt');
                // Picasa API request returns 'Location' inside response header.
                // Follow 'Location' in response header to get the actual file content.
                $result = curl_exec($ch);
                curl_close($ch);
                return $result;
            }
            else {
                $httpstatus = get_http_status($result->info['http_code']);
                $SESSION->add_error_msg($httpstatus);
                log_warn($httpstatus);
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developers.google.com/picasa-web/docs/2.0/developers_guide_protocol#UpdatePhotos
    // SEE: https://developers.google.com/picasa-web/docs/2.0/reference#Parameters   (imgmax parameter!)
    public function embed_file($file_id=0, $options=array('size' => '512', 'frame' => false), $owner=null) {
        global $SESSION;
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $photoinfo = self::get_file_info($file_id, $owner);
            $photourl = self::construct_photo_url($photoinfo['previewurl'], $options['size']);
            $previewurl = self::construct_photo_url($photoinfo['previewurl'], '640');
            if ($options['frame']) {
                $html = '<span style="float:left;padding:0.3em;">';
            }
            else {
                $html = '<span style="float:left;">';
            }
            $slimbox2 = get_config_plugin('blocktype', 'gallery', 'useslimbox2');
            if ($slimbox2) {
                $slimbox2attr = 'lightbox_' . $options['instanceid'];
            }
            else {
                $slimbox2attr = null;
            }
            $html .= '<a rel="' . $slimbox2attr . '" href="' . $previewurl . '" title="' . $photoinfo['name'] . '">';
            $html .= '<img src="' . $photourl . '" alt="' . $photoinfo['name'] . '" title="' . $photoinfo['name'] . '"';
            if ($options['frame']) {
                $html .= ' class="frame">';
            }
            else {
                $html .= '>';
            }
            $html .= '</a></span>';
            return $html;
        }
        else {
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developers.google.com/picasa-web/docs/2.0/developers_guide_protocol#UpdatePhotos
    // SEE: https://developers.google.com/picasa-web/docs/2.0/reference#Parameters   (imgmax parameter!)
    public function embed_folder($folder_id=0, $options=array('size' => '512', 'frame' => false, 'slideshow' => false), $owner=null) {
        global $SSSION;
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->picasaurl.'user/default/albumid/'.$folder_id;
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'access_token' => $token,
                'fileds' => 'entry[gphoto:size]'
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
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $data = oauth_parse_xml($result->data);
                // Extract slideshow link from array of different album links
                foreach ($data['link'] as $link) {
                    if ($link['@attributes']['rel'] == 'http://schemas.google.com/photos/2007#slideshow') {
                        $slideshowurl = $link['@attributes']['href'];
                    }
                }
                list($url, $attributes) = explode('slideshow.swf?', $slideshowurl);
                if ($options['slideshow']) {
                    switch ($options['size']) {
                        case '72-c': // Square
                          $slideshow = array('width' => 72, 'height' => 72);
                          break;
                        case '150-c': // Large Square
                          $slideshow = array('width' => 150, 'height' => 150);
                          break;
                        case '110': // Thumbnail
                          $slideshow = array('width' => 100, 'height' => 83);
                          break;
                        case '220': // Small
                          $slideshow = array('width' => 220, 'height' => 165);
                          break;
                        case '320': // Small 320
                          $slideshow = array('width' => 320, 'height' => 240);
                          break;
                        case '640': // Medium 640
                          $slideshow = array('width' => 640, 'height' => 480);
                          break;
                        case '800': // Medium 800
                          $slideshow = array('width' => 800, 'height' => 600);
                          break;
                        case '1024': // Large
                          $slideshow = array('width' => 1024, 'height' => 768);
                          break;
                        case '1600': // Large 1600
                          $slideshow = array('width' => 1600, 'height' => 1200);
                          break;
                        case '512': // Medium
                        default:
                          $slideshow = array('width' => 512, 'height' => 384);
                          break;
                    }
                    if ($options['frame']) {
                        $html = '<span style="float:left;padding:0.3em;"><div class="frame" style="padding:5px;">';
                    }
                    else {
                        $html = '<span style="float:left;"><div>';
                    }
                    $html .= '<object width="' . $slideshow['width'] . '" height="' . $slideshow['height'] . '">';
                    $html .= '<param name="flashvars" value="' . $attributes . '"></param>';
                    $html .= '<param name="movie" value="https://picasaweb.google.com/s/c/bin/slideshow.swf"></param>';
                    $html .= '<param name="allowFullScreen" value="true"></param>';
                    $html .= '<embed type="application/x-shockwave-flash" src="https://picasaweb.google.com/s/c/bin/slideshow.swf" allowFullScreen="true" flashvars="' . $attributes . '" width="' . $slideshow['width'] . '" height="' . $slideshow['height'] . '"></embed>';
                    $html .= '</object></div></span>';
                    return $html;
                }
                else {
                    $count = 0;
                    $xml = new DOMDocument();
                    $xml->loadXML($result->data);
                    foreach ($xml->getElementsByTagNameNS('http://schemas.google.com/photos/2007', '*') as $element) {
                        // Find out number of photos in an album...
                        if ($element->localName == 'size') {
                            $count++;
                        }
                    }
                    if ($count == 1) {
                        // If there is only one photo in the album
                        $photos = array('0' => $data['entry']);
                    }
                    else {
                        $photos = $data['entry'];
                    }
                    $html = '';
                    foreach ($photos as $photo) {
                        $html .= self::embed_file(basename($photo['id']), $options, $owner);
                    }
                    return $html;
                }
            }
            else {
                $httpstatus = get_http_status($result->info['http_code']);
                $SESSION->add_error_msg($httpstatus);
                log_warn($httpstatus);
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Picasa consumer key and/or consumer secret.');
        }
    }

}

