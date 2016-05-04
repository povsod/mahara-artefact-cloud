<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-box
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2016 Gregor Anzelj, info@povsod.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/oauth.php');


class PluginBlocktypeBox extends PluginBlocktypeCloud {

    public static function get_title() {
        return get_string('title', 'blocktype.cloud/box');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/box');
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
        $display  = (
            !empty($configdata['display']) && $configdata['display'] === 'embed'
            ? 'embed'
            : 'list'
        );
        $width    = (!empty($configdata['width']) ? $configdata['width'] : 466);
        $height   = (!empty($configdata['height']) ? $configdata['height'] : 400);
        
        $smarty = smarty_core();
        $smarty->assign('SERVICE', 'box');
        switch ($display) {
            case 'embed':
                $html = '';
                $options = array(
                    'width'  => $width,
                    'height' => $height,
                );
                if (!empty($selected)) {
                    foreach ($selected as $artefact) {
                        $html .= self::embed_file($artefact, $options);
                    }
                }
                $smarty->assign('embed', $html);
                break;
            case 'list':
                if (!empty($selected)) {
                    $file = self::get_file_info($selected[0]);
                    $folder = $file['parent_id'];
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
        return $smarty->fetch('artefact:cloud:' . $display . '.tpl');
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
        
        $consumer = self::get_service_consumer();
        if (isset($consumer->usrprefs['access_token']) && !empty($consumer->usrprefs['access_token'])) {
            return array(
                'boxlogo' => array(
                    'type' => 'html',
                    'value' => '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/box/theme/raw/static/images/logo.png">',
                ),
                'boxisconnect' => array(
                    'type' => 'cancel',
                    'value' => get_string('revokeconnection', 'blocktype.cloud/box'),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/box/account.php?action=logout&sesskey=' . $USER->get('sesskey'),
                ),
                'boxpath' => array(
                    'type'  => 'hidden',
                    'value' => '0',
                ),
                'boxfiles' => array(
                    'type'     => 'datatables',
                    'title'    => get_string('selectfiles','blocktype.cloud/box'),
                    'service'  => 'box',
                    'block'    => $instanceid,
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
                    'title' => get_string('display','blocktype.cloud/box'),
                    'description' => get_string('displaydesc','blocktype.cloud/box'),
                    'defaultvalue' => (!empty($configdata['display']) ? hsc($configdata['display']) : 'list'),
                    'options' => array(
                        'list'  => get_string('displaylist','blocktype.cloud/box'),
                        'embed' => get_string('displayembed','blocktype.cloud/box')
                    ),
                    'separator' => '<br />',
                ),
                'embedoptions' => array(
                    'type'         => 'fieldset',
                    'class'        => 'last',
                    'collapsible'  => true,
                    'collapsed'    => true,
                    'legend'       => get_string('embedoptions', 'blocktype.cloud/box'),
                    'elements'     => array(
                        'width' => array(
                            'type'  => 'text',
                            'title' => get_string('width', 'blocktype.cloud/box'),
                            'size'  => 3,
                            'defaultvalue' => (!empty($configdata['width']) ? hsc($configdata['width']) : 466),
                            'rules' => array('minvalue' => 1, 'maxvalue' => 2000),
                        ),
                        'height' => array(
                            'type'  => 'text',
                            'title' => get_string('height', 'blocktype.cloud/box'),
                            'size'  => 3,
                            'defaultvalue' => (!empty($configdata['height']) ? hsc($configdata['height']) : 400),
                            'rules' => array('minvalue' => 1, 'maxvalue' => 2000),
                        ),
                    ),
                ),
            );
        }
        else {
            return array(
                'boxlogo' => array(
                    'type' => 'html',
                    'value' => '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/box/theme/raw/static/images/logo.png">',
                ),
                'boxisconnect' => array(
                    'type' => 'cancel',
                    'value' => get_string('connecttobox', 'blocktype.cloud/box'),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/box/account.php?action=login&view=' . $viewid . '&sesskey=' . $USER->get('sesskey'),
                ),
            );
        }
    }

    public static function instance_config_save($values) {
        // Folder and file IDs (and other values) are returned as JSON/jQuery serialized string.
        // We have to parse that string and urldecode it (to correctly convert square brackets)
        // in order to get cloud folder and file IDs - they are stored in $artefacts array.
        parse_str(urldecode($values['boxfiles']));
        if (!isset($artefacts) || empty($artefacts)) {
            $artefacts = array();
        }
        
        $values = array(
            'title'     => $values['title'],
            'artefacts' => $artefacts,
            'display'   => $values['display'],
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
        global $THEME;
        $consumer = self::get_service_consumer();
        $elements = array();
        $elements['applicationdesc'] = array(
            'type'  => 'html',
            'value' => get_string('applicationdesc', 'blocktype.cloud/box', '<a href="https://www.box.com/developers/services" target="_blank">', '</a>'),
        );
        $elements['applicationgeneral'] = array(
            'type' => 'fieldset',
            'class' => 'first',
            'collapsible' => true,
            'collapsed' => false,
            'legend' => get_string('applicationgeneral', 'blocktype.cloud/box'),
            'elements' => array(
                'applicationname' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationname', 'blocktype.cloud/box'),
                    'defaultvalue' => get_config('sitename'),
                    'description'  => get_string('applicationnamedesc', 'blocktype.cloud/box'),
                ),
                'applicationweb' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationweb', 'blocktype.cloud/box'),
                    'defaultvalue' => get_config('wwwroot'),
                    'description'  => get_string('applicationwebdesc', 'blocktype.cloud/box'),
                ),
            )
        );
        $elements['applicationbackend'] = array(
            'type' => 'fieldset',
            'class' => 'last',
            'collapsible' => true,
            'collapsed' => false,
            'legend' => get_string('applicationbackend', 'blocktype.cloud/box'),
            'elements' => array(
                'consumerkey' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumerkey', 'blocktype.cloud/box'),
                    'defaultvalue' => get_config_plugin('blocktype', 'box', 'consumerkey'),
                    'description'  => get_string('consumerkeydesc', 'blocktype.cloud/box'),
                    'rules'        => array('required' => true),
                ),
                'consumersecret' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumersecret', 'blocktype.cloud/box'),
                    'defaultvalue' => get_config_plugin('blocktype', 'box', 'consumersecret'),
                    'description'  => get_string('consumersecretdesc', 'blocktype.cloud/box'),
                    'rules'        => array('required' => true),
                ),
                'redirecturl' => array(
                    'type'         => 'text',
                    'title'        => get_string('redirecturl', 'blocktype.cloud/box'),
                    'value'        => $consumer->callback,
                    'description'  => get_string('redirecturldesc', 'blocktype.cloud/box'),
                    'rules'        => array('required' => true),
                ),
                'applicationicon' => array(
                    'type'         => 'html',
                    'title'        => get_string('applicationicon', 'blocktype.cloud/box'),
                    'value'        => '<table border="0"><tr style="text-align:center">
                                       <td style="vertical-align:bottom;padding:4px"><img src="'.$THEME->get_url('images/016x016.jpg', false, 'artefact/cloud').'" border="0" style="border:1px solid #ccc"><br>16x16</td>
                                       <td style="vertical-align:bottom;padding:4px"><img src="'.$THEME->get_url('images/064x064.jpg', false, 'artefact/cloud').'" border="0" style="border:1px solid #ccc"><br>64x64</td>
                                       <td style="vertical-align:bottom;padding:4px"><img src="'.$THEME->get_url('images/100x080.jpg', false, 'artefact/cloud').'" border="0" style="border:1px solid #ccc"><br>100x80</td>
                                       </table>',
                    'description'  => get_string('applicationicondesc', 'blocktype.cloud/box'),
                ),
            )
        );
        return array(
            'class' => 'panel panel-body',
            'elements' => $elements,
        );

    }

    public static function save_config_options($form, $values) {
        set_config_plugin('blocktype', 'box', 'consumerkey', $values['consumerkey']);
        set_config_plugin('blocktype', 'box', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /*****************************************
     * Methods & stuff for accessing Box API *
     *****************************************/
    
    private function get_service_consumer($owner=null) {
        global $USER;
        if (!isset($owner) || is_null($owner)) {
            $owner = $USER->get('id');
        }
        $wwwroot = get_config('wwwroot');
        $service = new StdClass();
        $service->ssl        = true;
        $service->version    = '2.0'; // API Version
        $service->apiurl     = 'https://api.box.com/';
        $service->contenturl = 'https://upload.box.com/api/';
        $service->wwwurl     = 'https://www.box.com/api'; // without trailing slash, since there isn't API version in those URLs
        $service->key        = get_config_plugin('blocktype', 'box', 'consumerkey');
        $service->secret     = get_config_plugin('blocktype', 'box', 'consumersecret');
        // If SSL is set then force SSL URL for callback
        if ($service->ssl) {
            $wwwroot = str_replace('http://', 'https://', get_config('wwwroot'));
        }
        $service->callback   = $wwwroot . 'artefact/cloud/blocktype/box/callback.php';
        $service->usrprefs   = ArtefactTypeCloud::get_user_preferences('box', $owner);
        return $service;
    }

    public function service_list() {
        global $SESSION;
        $consumer = self::get_service_consumer();
        $service = new StdClass();
        $service->name = 'box';
        $service->url = 'http://www.box.com';
        $service->auth = false;
        $service->manage = false;
        $service->pending = false;

        if (!empty($consumer->key)) {
            if (isset($consumer->usrprefs['access_token']) && !empty($consumer->usrprefs['access_token'])) {
                $service->auth = true;
                $service->manage = true;
                $service->account = self::account_info();
            }
        }
        else {
            $service->pending = true;
            $SESSION->add_error_msg('Can\'t find Box consumer key and/or consumer secret.');
        }
        return $service;
    }

    // SEE: https://developers.box.com/oauth/
    // SEE: https://developers.box.com/docs/#oauth-2-authorize
    public function request_token() {
        global $SESSION;
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->wwwurl.'/oauth2/authorize';
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'response_type' => 'code',
                'client_id' => $consumer->key,
                'redirect_uri' => $consumer->callback,
            );
            $query = oauth_http_build_query($params);
            $request_url = $url . ($query ? ('?' . $query) : '');
            redirect($request_url);
        }
        else {
            $SESSION->add_error_msg('Can\'t find Box consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developers.box.com/oauth/
    // SEE: https://developers.box.com/docs/#oauth-2-token
    public function access_token($oauth_code) {
        global $SESSION;
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->wwwurl.'/oauth2/token';
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'grant_type' => 'authorization_code',
                'code' => $oauth_code,
                'client_id' => $consumer->key,
                'client_secret' => $consumer->secret,
                'redirect_uri' => $consumer->callback,
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
            $SESSION->add_error_msg('Can\'t find Box consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developers.box.com/docs/#oauth-2-token
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
                $url = $consumer->wwwurl.'/oauth2/token';
                $port = $consumer->ssl ? '443' : '80';
                $params = array(
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $consumer->usrprefs['refresh_token'],
                    'client_id' => $consumer->key,
                    'client_secret' => $consumer->secret,
                    'redirect_uri' => $consumer->callback,
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
                    ArtefactTypeCloud::set_user_preferences('box', $USER->get('id'), $prefs);
                    return $prefs['access_token'];
                }
                else {
                    $httpstatus = get_http_status($result->info['http_code']);
                    $SESSION->add_error_msg($httpstatus);
                    log_warn($httpstatus);
                }
            }
            // If access token is not expired, than return it...
            else {
                return $consumer->usrprefs['access_token'];
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Box consumer key and/or consumer secret.');
        }
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('box', $USER->get('id'), null);
    }
    
    // SEE: https://developers.box.com/oauth/
    // SEE: https://developers.box.com/docs/#oauth-2-revoke
    public function revoke_access() {
        global $SESSION;
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->wwwurl.'/oauth2/revoke';
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'client_id' => $consumer->key,
                'client_secret' => $consumer->secret,
                'token' => $consumer->usrprefs['access_token'],
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
        }
        else {
            $SESSION->add_error_msg('Can\'t find Box consumer key and/or consumer secret.');
        }
    }
    
    // SEE: https://developers.box.com/docs/#users-get-the-current-users-information
    public function account_info() {
        global $SESSION;
        $consumer = self::get_service_consumer();
        $token = self::check_access_token();

        $info = new StdClass();
        $info->service_name = 'box';
        $info->service_auth = false;
        $info->user_id      = null;
        $info->user_name    = null;
        $info->user_email   = null;
        $info->user_profile = null;
        $info->space_used   = null;
        $info->space_amount = null;
        $info->space_ratio  = null;

        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/users/me';
            $port = $consumer->ssl ? '443' : '80';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => array('Authorization: Bearer '.$token),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $account = json_decode(substr($result->data, $result->info['header_size']), true);

                $info->service_name = 'box';
                $info->service_url  = 'http://www.box.com';
                $info->service_auth = true;
                $info->user_id      = $account['id'];
                $info->user_name    = $account['name'];
                $info->user_email   = $account['login'];
                $info->space_used   = bytes_to_size1024(floatval($account['space_used']));
                $info->space_amount = bytes_to_size1024(floatval($account['space_amount']));
                $info->space_ratio  = number_format((floatval($account['space_used'])/floatval($account['space_amount']))*100, 2);
                return $info;
            }
            else {
                $httpstatus = get_http_status($result->info['http_code']);
                $SESSION->add_error_msg($httpstatus);
                log_warn($httpstatus);
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Box consumer key and/or consumer secret.');
        }
        return $info;
    }
    
    /*
     * This function returns list of selected files/folders which will be displayed in a view/page.
     *
     * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
     * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
     *
     * SEE: https://developers.box.com/docs/#folders-retrieve-a-folders-items
     *
     */
    public function get_filelist($folder_id=0, $selected=array(), $owner=null) {
        global $SESSION;

        // Get folder contents...
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/folders/'.$folder_id.'/items?fields=size,name,description,created_at';
            $port = $consumer->ssl ? '443' : '80';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => array('Authorization: Bearer '.$token),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $data = json_decode(substr($result->data, $result->info['header_size']));
                $output = array(
                    'folders' => array(),
                    'files'   => array()
                );
                if (isset($data->entries) && !empty($data->entries)) {
                    foreach($data->entries as $artefact) {
                        if (in_array($artefact->id, $selected)) {
                            $id          = $artefact->id;
                            $type        = $artefact->type;
                            $title       = $artefact->name;
                            $description = $artefact->description;
                            $size        = bytes_to_size1024($artefact->size);
                            $created     = ($artefact->created_at ? format_date(strtotime($artefact->created_at), 'strftimedaydate') : null);
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
                                    'ctime' => $created,
                                );
                            }
                        }
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
            $SESSION->add_error_msg('Can\'t find Box consumer key and/or consumer secret.');
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
     * SEE: https://developers.box.com/docs/#folders-get-information-about-a-folder
     * SEE: https://developers.box.com/docs/#folders-retrieve-a-folders-items
     */
    public function get_folder_content($folder_id=0, $options, $block=0) {
        global $SESSION;
        
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
            $url = $consumer->apiurl.$consumer->version.'/folders/'.$folder_id.'/items';
            $port = $consumer->ssl ? '443' : '80';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => array('Authorization: Bearer '.$token),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $data = json_decode(substr($result->data, $result->info['header_size']));
                $output = array();
                $count = 0;
                // Add 'parent' row entry to jQuery Datatable...
                if ($folder_id > 0) {
                    $type        = 'parentfolder';
                    $foldername  = get_string('parentfolder', 'artefact.file');
                    $icon        = '<span class="icon-level-up icon icon-lg"></span>';
                    $title       = '<a class="changefolder" href="javascript:void(0)" id="' . $parent_id . '" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '">' . $foldername . '</a>';
                    $output['data'][] = array($icon, $title, '', $type);
                }
                if (!empty($data->entries)) {
                    $detailspath = get_config('wwwroot') . 'artefact/cloud/blocktype/box/details.php';
                    foreach($data->entries as $artefact) {
                        $id   = $artefact->id;
                        $type = $artefact->type;
                        $artefactname = $artefact->name;
                        if ($artefact->type == 'folder') {
                            $icon  = '<span class="icon-folder-open icon icon-lg"></span>';
                            $title = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $artefactname) . '">' . $artefactname . '</a>';
                        }
                        else {
                            $icon  = '<span class="icon-file icon icon-lg"></span>';
                            $title = '<a class="filedetails" href="' . $detailspath . '?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $artefactname) . '">' . $artefactname . '</a>';
                        }
                        $controls = '';
                        $selected = (in_array(''.$id, $artefacts) ? ' checked' : '');
                        if ($artefact->type == 'folder') {
                            if ($selectFolders) {
                                $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                            }
                        }
                        else {
                            if ($selectFiles && !$manageButtons) {
                                $controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="' . $id . '"' . $selected . '>';
                            }
                            elseif ($manageButtons) {
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
            $SESSION->add_error_msg('Can\'t find Box consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developers.box.com/docs/#folders-get-information-about-a-folder
    public function get_folder_info($folder_id=0, $owner=null) {
        global $SESSION;
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/folders/'.$folder_id;
            $port = $consumer->ssl ? '443' : '80';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => array('Authorization: Bearer '.$token),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $data = json_decode(substr($result->data, $result->info['header_size']), true);
                $info = array(
                    'id'          => $data['id'],
                    'parent_id'   => $data['parent']['id'],
                    'name'        => $data['name'],
                    'bytes'       => $data['size'],
                    'size'        => bytes_to_size1024($data['size']),
                    'description' => $data['description'],
                    'created'     => format_date(strtotime($data['created_at']), 'strfdaymonthyearshort'),
                    'updated'     => format_date(strtotime($data['modified_at']), 'strfdaymonthyearshort'),
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
            $SESSION->add_error_msg('Can\'t find Box consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developers.box.com/docs/#files-get
    public function get_file_info($file_id=0, $owner=null) {
        global $SESSION;
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/files/'.$file_id;
            $port = $consumer->ssl ? '443' : '80';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => array('Authorization: Bearer '.$token),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 200) {
                $data = json_decode(substr($result->data, $result->info['header_size']), true);
                $info = array(
                    'id'          => $data['id'],
                    'parent_id'   => $data['parent']['id'],
                    'name'        => $data['name'],
                    'bytes'       => $data['size'],
                    'size'        => bytes_to_size1024($data['size']),
                    'description' => $data['description'],
                    'created'     => format_date(strtotime($data['created_at']), 'strfdaymonthyearshort'),
                    'updated'     => format_date(strtotime($data['modified_at']), 'strfdaymonthyearshort'),
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
            $SESSION->add_error_msg('Can\'t find Box consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developers.box.com/docs/#files-download-a-file
    public function download_file($file_id=0, $owner=null) {
        global $SESSION;
        $consumer = self::get_service_consumer($owner);
        $token = self::check_access_token($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/files/'.$file_id.'/content';
            $port = $consumer->ssl ? '443' : '80';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_PORT, $port);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$token));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, get_config('docroot').'artefact/cloud/cert/cacert.crt');
            // Box API request returns 'Location' inside response header.
            // Follow 'Location' in response header to get the actual file content.
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            // Another request to get the actual file contents...
            $ch2 = curl_init($info['url']);
            curl_setopt($ch2, CURLOPT_PORT, $port);
            curl_setopt($ch2, CURLOPT_POST, false);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch2, CURLOPT_CAINFO, get_config('docroot').'artefact/cloud/cert/cacert.crt');
            $result2 = curl_exec($ch2);
            curl_close($ch2);
            return $result2;
        }
        else {
            $SESSION->add_error_msg('Can\'t find Box consumer key and/or consumer secret.');
        }
    }

    // SEE: https://developers.box.com/box-embed/
    public function embed_file($file_id=0, $options=array(), $owner=null) {
        $data = self::get_file_info($file_id, $owner);
        $shared = (isset($data['preview']) ? basename($data['preview']) : null);
        $width = (isset($options['width']) ? $options['width'] : 400);
        $height = (isset($options['height']) ? $options['height'] : 300);

        $html = '<iframe src="https://app.box.com/embed_widget/s/' . $shared . '" width="' . $width . '" height="' . $height . '" frameborder="0" allowfullscreen webkitallowfullscreen mozallowfullscreen oallowfullscreen msallowfullscreen></iframe>';

        return $html;
    }

}
