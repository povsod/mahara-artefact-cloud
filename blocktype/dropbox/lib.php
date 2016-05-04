<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-dropbox
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2016 Gregor Anzelj, info@povsod.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/oauth.php');


class PluginBlocktypeDropbox extends PluginBlocktypeCloud {
    
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/dropbox');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/dropbox');
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
        $smarty->assign('SERVICE', 'dropbox');
        if (!empty($selected)) {
            $folder = dirname($selected[0]);
            // Update root folder from '\' to '/'
            $folder = ($folder == '\\' ? '/' : $folder);
        }
        else {
            $folder = '';
        }
        $data = self::get_filelist($folder, $selected, $ownerid);
        $smarty->assign('folders', $data['folders']);
        $smarty->assign('files', $data['files']);
        $smarty->assign('viewid', $viewid);
        return $smarty->fetch('artefact:cloud:list.tpl');
    }

    public static function has_instance_config() {
        return true;
    }

    public static function instance_config_form($instance) {
        global $USER;
        $instanceid = $instance->get('id');
        $configdata = $instance->get('configdata');
        safe_require('artefact', 'cloud');
        $instance->set('artefactplugin', 'cloud');
        $viewid = $instance->get('view');

        $view = new View($viewid);
        $ownerid = $view->get('owner');
        
        $consumer = self::get_service_consumer();
        if (isset($consumer->usrprefs['access_token']) && !empty($consumer->usrprefs['access_token'])) {
            return array(
                'dropboxlogo' => array(
                    'type' => 'html',
                    'value' => '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/dropbox/theme/raw/static/images/logo.png">',
                ),
                'dropboxisconnect' => array(
                    'type' => 'cancel',
                    'value' => get_string('revokeconnection', 'blocktype.cloud/dropbox'),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/dropbox/account.php?action=logout&sesskey=' . $USER->get('sesskey'),
                ),
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
        else {
            return array(
                'dropboxlogo' => array(
                    'type' => 'html',
                    'value' => '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/dropbox/theme/raw/static/images/logo.png">',
                ),
                'dropboxisconnect' => array(
                    'type' => 'cancel',
                    'value' => get_string('connecttodropbox', 'blocktype.cloud/dropbox'),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/dropbox/account.php?action=login&view=' . $viewid . '&sesskey=' . $USER->get('sesskey'),
                ),
            );
        }
    }

    public static function instance_config_save($values) {
        // Folder and file IDs (and other values) are returned as JSON/jQuery serialized string.
        // We have to parse that string and urldecode it (to correctly convert square brackets)
        // in order to get cloud folder and file IDs - they are stored in $artefacts array.
        parse_str(urldecode($values['dropboxfiles']), $params);
        if (!isset($params['artefacts']) || empty($params['artefacts'])) {
            $artefacts = array();
        }
        else {
            $artefacts = $params['artefacts'];
        }

        $values = array(
            'title'     => $values['title'],
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
        global $THEME;
        $consumer = self::get_service_consumer();
        $elements = array();
        $elements['applicationdesc'] = array(
            'type'  => 'html',
            'value' => get_string('applicationdesc', 'blocktype.cloud/dropbox', '<a href="https://www.dropbox.com/developers/apps" target="_blank">', '</a>'),
        );
        $elements['applicationgeneral'] = array(
            'type' => 'fieldset',
            'class' => 'first',
            'collapsible' => true,
            'collapsed' => false,
            'legend' => get_string('applicationgeneral', 'blocktype.cloud/dropbox'),
            'elements' => array(
                'consumerkey' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumerkey', 'blocktype.cloud/dropbox'),
                    'defaultvalue' => get_config_plugin('blocktype', 'dropbox', 'consumerkey'),
                    'description'  => get_string('consumerkeydesc', 'blocktype.cloud/dropbox'),
                    'rules'        => array('required' => true),
                ),
                'consumersecret' => array(
                    'type'         => 'text',
                    'title'        => get_string('consumersecret', 'blocktype.cloud/dropbox'),
                    'defaultvalue' => get_config_plugin('blocktype', 'dropbox', 'consumersecret'),
                    'description'  => get_string('consumersecretdesc', 'blocktype.cloud/dropbox'),
                    'rules'        => array('required' => true),
                ),
                'redirecturl' => array(
                    'type'        => 'text',
                    'title'       => get_string('redirecturl', 'blocktype.cloud/dropbox'),
                    'value'       => $consumer->callback,
                    'description' => get_string('redirecturldesc', 'blocktype.cloud/dropbox'),
                    'rules'       => array('required' => true),
                ),
                'permissiontype' => array(
                    'type'         => 'html',
                    'title'        => get_string('permissiontype', 'blocktype.cloud/dropbox'),
                    'value' => get_string('fulldropbox', 'blocktype.cloud/dropbox'),
                ),
            )
        );
        $elements['applicationadditional'] = array(
            'type' => 'fieldset',
            'class' => 'last',
            'collapsible' => true,
            'collapsed' => false,
            'legend' => get_string('applicationadditional', 'blocktype.cloud/dropbox'),
            'elements' => array(
                'applicationname' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationname', 'blocktype.cloud/dropbox'),
                    'defaultvalue' => get_config('sitename'),
                    'description'  => get_string('applicationnamedesc', 'blocktype.cloud/dropbox'),
                ),
                'applicationweb' => array(
                    'type'         => 'text',
                    'title'        => get_string('applicationweb', 'blocktype.cloud/dropbox'),
                    'defaultvalue' => get_config('wwwroot'),
                    'description'  => get_string('applicationwebdesc', 'blocktype.cloud/dropbox'),
                ),
                'applicationicon' => array(
                    'type'         => 'html',
                    'title'        => get_string('applicationicon', 'blocktype.cloud/dropbox'),
                    'value'        => '<table border="0"><tr style="text-align:center">
                                       <td style="vertical-align:bottom;padding:4px"><img src="'.$THEME->get_url('images/064x064.png', false, 'artefact/cloud').'" border="0" style="border:1px solid #ccc"><br>64x64</td>
                                       <td style="vertical-align:bottom;padding:4px"><img src="'.$THEME->get_url('images/256x256.png', false, 'artefact/cloud').'" border="0" style="border:1px solid #ccc"><br>256x256</td>
                                       </table>',
                    'description'  => get_string('applicationicondesc', 'blocktype.cloud/dropbox'),
                ),
            )
        );
        return array(
            'class' => 'panel panel-body',
            'elements' => $elements,
        );

    }

    public static function save_config_options($form, $values) {
        set_config_plugin('blocktype', 'dropbox', 'consumerkey', $values['consumerkey']);
        set_config_plugin('blocktype', 'dropbox', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /*********************************************
     * Methods & stuff for accessing Dropbox API *
     *********************************************/

    private function get_service_consumer($owner=null) {
        global $USER;
        if (!isset($owner) || is_null($owner)) {
            $owner = $USER->get('id');
        }
        $wwwroot = get_config('wwwroot');
        $service = new StdClass();
        $service->ssl        = true;
        $service->version    = 1; // API Version
        $service->apiurl     = 'https://api.dropbox.com/';
        $service->contenturl = 'https://api-content.dropbox.com/';
        $service->wwwurl     = 'https://www.dropbox.com/';
        $service->key        = get_config_plugin('blocktype', 'dropbox', 'consumerkey');
        $service->secret     = get_config_plugin('blocktype', 'dropbox', 'consumersecret');
        // If SSL is set then force SSL URL for callback
        if ($service->ssl) {
            $wwwroot = str_replace('http://', 'https://', get_config('wwwroot'));
        }
        $service->callback   = $wwwroot . 'artefact/cloud/blocktype/dropbox/callback.php';
        $service->usrprefs   = ArtefactTypeCloud::get_user_preferences('dropbox', $owner);
        return $service;
    }

    public function service_list() {
        global $SESSION;
        $consumer = self::get_service_consumer();
        $service = new StdClass();
        $service->name = 'dropbox';
        $service->url = 'http://www.dropbox.com';
        $service->auth = false;
        $service->manage = false;
        $service->pending = false;

        if (!empty($consumer->key) && !empty($consumer->secret)) {
            if (isset($consumer->usrprefs['access_token']) && !empty($consumer->usrprefs['access_token'])) {
                $service->auth = true;
                $service->manage = true;
                $service->account = self::account_info();
            }
        }
        else {
            $service->pending = true;
            $SESSION->add_error_msg('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
        return $service;
    }

    // SEE: https://www.dropbox.com/developers/core/docs#oa2-authorize
    public function request_token() {
        global $SESSION;
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->wwwurl.$consumer->version.'/oauth2/authorize';
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
            $SESSION->add_error_msg('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }

    // SEE: https://www.dropbox.com/developers/core/docs#oa2-token
    public function access_token($oauth_code) {
        global $SESSION;
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/oauth2/token';
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'code' => $oauth_code,
                'grant_type' => 'authorization_code',
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
            $SESSION->add_error_msg('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }

    public function check_access_token($owner=null) {
        // NOT NEEDED since access token doesn't expire?
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('dropbox', $USER->get('id'), null);
    }

    // SEE: https://www.dropbox.com/developers/core/docs#disable-token
    public function revoke_access() {
        global $SESSION;
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $revoke_url = $consumer->apiurl.$consumer->version.'/disable_access_token';
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'access_token' => $consumer->usrprefs['access_token']
            );
            $config = array(
                CURLOPT_URL => $revoke_url,
                CURLOPT_PORT => $port,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => oauth_http_build_query($params),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if ($result->error) {
                $SESSION->add_error_msg($error);
            }
            // If we were successful at deleting the token, Dropbox will respond
            // with an empty JSON dictionary.
            else if (!isset($result->data) || trim($result->data) !== '{}') {
                $SESSION->add_error_msg('Error deleting access token.');
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }

    // SEE: https://www.dropbox.com/developers/core/docs#account-info
    public function account_info() {
        global $SESSION;
        $consumer = self::get_service_consumer();

        $info = new StdClass();
        $info->service_name = 'dropbox';
        $info->service_auth = false;
        $info->user_id      = null;
        $info->user_name    = null;
        $info->user_email   = null;
        $info->user_profile = null;
        $info->space_used   = null;
        $info->space_amount = null;
        $info->space_ratio  = null;

        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/account/info';
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'access_token' => $consumer->usrprefs['access_token']
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
                $account = json_decode($result->data);

                $info->service_name = 'dropbox';
                $info->service_auth = true;
                $info->user_id      = $account->uid;
                $info->user_name    = $account->display_name;
                $info->user_email   = $account->email;
                $info->space_used   = bytes_to_size1024(floatval($account->quota_info->normal)+floatval($account->quota_info->shared));
                $info->space_amount = bytes_to_size1024(floatval($account->quota_info->quota));
                $info->space_ratio  = number_format(((floatval($account->quota_info->normal)+floatval($account->quota_info->shared))/floatval($account->quota_info->quota))*100, 2);
                return $info;
            }
            else {
                $httpstatus = get_http_status($result->info['http_code']);
                $SESSION->add_error_msg($httpstatus);
                log_warn($httpstatus);
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Dropox consumer key and/or consumer secret.');
        }
        return $info;
    }
    
    /*
     * This function returns list of selected files/folders which will be displayed in a view/page.
     *
     * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
     * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
     *
     * SEE: https://www.dropbox.com/developers/core/docs#metadata
     */
    public function get_filelist($folder_id='', $selected=array(), $owner=null) {
        global $SESSION;

        // Get folder contents...
        $consumer = self::get_service_consumer($owner);
        if (!isset($consumer->usrprefs['access_token']) || empty($consumer->usrprefs['access_token'])) {
            $SESSION->add_error_msg(get_string('accesstokennotreturned', 'artefact.cloud'));
            return false;
        }
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/metadata/dropbox';
            $url .= implode('/', array_map('rawurlencode', explode('/', $folder_id)));
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'access_token' => $consumer->usrprefs['access_token']
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
                $data = json_decode($result->data);
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
                            // Get artefactname by removing parent path from beginning...
                            $title       = basename($artefact->path);
                            $description = ''; // Dropbox doesn't support file/folder descriptions
                            $size        = bytes_to_size1024($artefact->bytes);
                            if ($artefact->is_dir) {
                                $created = format_date(strtotime($artefact->modified), 'strftimedaydate');
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
                                $created = format_date(strtotime($artefact->client_mtime), 'strftimedaydate');
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
            $SESSION->add_error_msg('Can\'t find Dropbox consumer key and/or consumer secret.');
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
     * SEE: https://www.dropbox.com/developers/core/docs#metadata
     */
    public function get_folder_content($folder_id='/', $options, $block=0) {
        global $SESSION;

        // $folder_id is globally set to '0', set it to '/'
        // as it is the Dropbox default root folder ...
        if ($folder_id == '0') {
            $folder_id = '/';
        }

        // Get selected artefacts (folders and/or files)
        if ($block > 0) {
            $data = unserialize(get_field('block_instance', 'configdata', 'id', $block));
            if (!empty($data)) {
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

        // Get folder contents...
        $consumer = self::get_service_consumer();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/metadata/dropbox';
            $url .= implode('/', array_map('rawurlencode', explode('/', $folder_id)));
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'access_token' => $consumer->usrprefs['access_token']
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
                $data = json_decode($result->data);
                $output = array();
                $count = 0;
                // Add 'parent' row entry to jQuery Datatable...
                if (strlen($data->path) > 1) {
                    $parentpath  = str_replace('\\', '/', dirname($data->path));
                    $type        = 'parentfolder';
                    $foldername  = get_string('parentfolder', 'artefact.file');
                    $icon        = '<span class="icon-level-up icon icon-lg"></span>';
                    $title       = '<a class="changefolder" href="javascript:void(0)" id="' . $parentpath . '" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '">' . $foldername . '</a>';
                    $output['data'][] = array($icon, $title, '', $type);
                }
                if (!empty($data->contents)) {
                    $detailspath = get_config('wwwroot') . 'artefact/cloud/blocktype/dropbox/details.php';
                    foreach($data->contents as $artefact) {
                        // In Dropbox id basically means path...
                        $id   = $artefact->path;
                        $type = ($artefact->is_dir ? 'folder' : 'file');
                        // Get artefactname by removing parent path from beginning...
                        $artefactname = basename($artefact->path);
                        if ($artefact->is_dir) {
                            $icon  = '<span class="icon-folder-open icon icon-lg"></span>';
                            $title = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $artefactname) . '">' . $artefactname . '</a>';
                        }
                        else {
                            $icon  = '<span class="icon-file icon icon-lg"></span>';
                            $title = '<a class="filedetails" href="' . $detailspath . '?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $artefactname) . '">' . $artefactname . '</a>';
                        }
                        $controls = '';
                        $selected = (in_array(''.$id, $artefacts) ? ' checked' : '');
                        if ($artefact->is_dir) {
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
            $SESSION->add_error_msg('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }
    
    // SEE: https://www.dropbox.com/developers/core/docs#metadata
    public function get_folder_info($folder_id='/', $owner=null) {
        global $SESSION;
        $consumer = self::get_service_consumer($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/metadata/dropbox'.$folder_id;
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'access_token' => $consumer->usrprefs['access_token'],
                // Method specific parameters...
                'list' => false,
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
                $data = json_decode($result->data);
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
            else {
                $httpstatus = get_http_status($result->info['http_code']);
                $SESSION->add_error_msg($httpstatus);
                log_warn($httpstatus);
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }
    
    // SEE: https://www.dropbox.com/developers/core/docs#metadata
    public function get_file_info($file_id='/', $owner=null) {
        global $SESSION;
        $consumer = self::get_service_consumer($owner);
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->apiurl.$consumer->version.'/metadata/dropbox'.$file_id;
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'access_token' => $consumer->usrprefs['access_token'],
                // Method specific parameters...
                'list' => false,
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
                $data = json_decode($result->data);
                $info = array(
                    'id'        => $data->path,
                    'parent_id' => dirname($data->path),
                    'name'      => basename($data->path),
                    'size'      => bytes_to_size1024($data->bytes),
                    'bytes'     => $data->bytes,
                    'updated'   => format_date(strtotime($data->modified), 'strfdaymonthyearshort'),
                    'created'   => format_date(strtotime($data->client_mtime), 'strfdaymonthyearshort'),
                    'mimetype'  => $data->mime_type,
                    'rev'       => $data->rev,
                    'root'      => $data->root,
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
            $SESSION->add_error_msg('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }
    
    // SEE: https://www.dropbox.com/developers/core/docs#files-GET
    public function download_file($file_id='/', $owner=null) {
        global $SESSION;
        $consumer = self::get_service_consumer($owner);
        $params   = array();
        if (!empty($consumer->key) && !empty($consumer->secret)) {
            $url = $consumer->contenturl.$consumer->version.'/files/dropbox'.$file_id;
            $port = $consumer->ssl ? '443' : '80';
            $params = array(
                'access_token' => $consumer->usrprefs['access_token']
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
            return $result->data;
        }
        else {
            $SESSION->add_error_msg('Can\'t find Dropbox consumer key and/or consumer secret.');
        }
    }
    
    public function embed_file($file_id='/', $options=array(), $owner=null) {
        // Dropbox API doesn't support embedding of files, so:
        // Nothing to do!
    }

}
