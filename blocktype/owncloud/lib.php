<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-owncloud
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2017 Gregor Anzelj, info@povsod.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/oauth.php');


class PluginBlocktypeOwncloud extends PluginBlocktypeCloud {
    
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/owncloud');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/owncloud');
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
        
        $smarty = smarty_core();
        $smarty->assign('SERVICE', 'owncloud');
        if (!empty($selected)) {
            $folder = dirname($selected[0]);
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

        $sub = get_config_plugin('blocktype', 'owncloud', 'subservice');
        $subservice = (isset($sub) && !empty($sub) ? $sub : 'images');

        $consumer = self::get_service_consumer();
        if (isset($consumer->usrprefs['token']) && !empty($consumer->usrprefs['token'])) {
            return array(
                'owncloudlogo' => array(
                    'type' => 'html',
                    'value' => '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/owncloud/theme/raw/static/' . $subservice . '/logo.png">',
                ),
                'owncloudisconnect' => array(
                    'type' => 'cancel',
                    'value' => get_string('revokeconnection', 'blocktype.cloud/owncloud') . ' • '
                             . get_config_plugin('blocktype', 'owncloud', 'servicetitle'),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/owncloud/account.php?action=logout&sesskey=' . $USER->get('sesskey'),
                ),
                'owncloudfiles' => array(
                    'type'     => 'datatables',
                    'title'    => get_string('selectfiles','blocktype.cloud/owncloud'),
                    'service'  => 'owncloud',
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
                'owncloudlogo' => array(
                    'type' => 'html',
                    'value' => '<img src="' . get_config('wwwroot') . 'artefact/cloud/blocktype/owncloud/theme/raw/static/images/logo.png">',
                ),
                'owncloudisconnect' => array(
                    'type' => 'cancel',
                    'value' => get_string('connecttoowncloud', 'blocktype.cloud/owncloud'),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/blocktype/owncloud/account.php?action=login&view=' . $viewid . '&sesskey=' . $USER->get('sesskey'),
                ),
            );
        }
    }

    public static function instance_config_save($values) {
        // Folder and file IDs (and other values) are returned as JSON/jQuery serialized string.
        // We have to parse that string and urldecode it (to correctly convert square brackets)
        // in order to get cloud folder and file IDs - they are stored in $artefacts array.
        parse_str(urldecode($values['owncloudfiles']), $params);
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
        $servicetitle = get_config_plugin('blocktype', 'owncloud', 'servicetitle');
        $subservice = get_config_plugin('blocktype', 'owncloud', 'subservice');
        $elements = array();
        $elements['owncloudgeneral'] = array(
            'type' => 'fieldset',
            'class' => 'last',
            'collapsible' => true,
            'collapsed' => false,
            'legend' => get_string('owncloudgeneral', 'blocktype.cloud/owncloud'),
            'elements' => array(
                'servicetitle' => array(
                    'type'         => 'text',
                    'title'        => get_string('servicetitle', 'blocktype.cloud/owncloud'),
                    'defaultvalue' => (isset($servicetitle) ? $servicetitle : get_string('service', 'blocktype.cloud/owncloud')),
                    'description'  => get_string('servicetitledesc', 'blocktype.cloud/owncloud'),
                ),
                'subservice' => array(
                    'type'         => 'select',
                    'title'        => get_string('subservice', 'blocktype.cloud/owncloud'),
                    'defaultvalue' => (isset($subservice) ? $subservice : '0'),
                    'description'  => get_string('subservicedesc', 'blocktype.cloud/owncloud'),
                    'options'      => array(
                        '0'     => get_string('none'),
                        'arnes' => get_string('subservice.arnes', 'blocktype.cloud/owncloud'),
                    ),
                ),
                'webdavurl' => array(
                    'type'         => 'text',
                    'title'        => get_string('webdavurl', 'blocktype.cloud/owncloud'),
                    'defaultvalue' => get_config_plugin('blocktype', 'owncloud', 'webdavurl'),
                    'description'  => get_string('webdavurldesc', 'blocktype.cloud/owncloud'),
                    'rules'        => array('required' => true),
                ),
                'webdavproxy' => array(
                    'type'         => 'text',
                    'title'        => get_string('webdavproxy', 'blocktype.cloud/owncloud'),
                    'defaultvalue' => get_config_plugin('blocktype', 'owncloud', 'webdavproxy'),
                    'description'  => get_string('webdavproxydesc', 'blocktype.cloud/owncloud'),
                ),
            )
        );
        return array(
            'class' => 'panel panel-body',
            'elements' => $elements,
        );

    }

    public static function save_config_options(Pieform $form, $values) {
        // Strip last slash character if there is any and then add it.
        // This is the only way to be sure it really exists!
        $webdavurl = rtrim($values['webdavurl'], '/') . '/';
        set_config_plugin('blocktype', 'owncloud', 'servicetitle', $values['servicetitle']);
        set_config_plugin('blocktype', 'owncloud', 'subservice', $values['subservice']);
        set_config_plugin('blocktype', 'owncloud', 'webdavurl', $webdavurl);
        set_config_plugin('blocktype', 'owncloud', 'webdavproxy', $values['webdavproxy']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

    /***********************************************
     * Methods & stuff for accessing ownCloud API *
     ***********************************************/
    
    private function get_service_consumer($owner=null) {
        global $USER;
        if (!isset($owner) || is_null($owner)) {
            $owner = $USER->get('id');
        }
        $webdavurl = get_config_plugin('blocktype', 'owncloud', 'webdavurl');
        $url = parse_url($webdavurl);
        $service = new StdClass();
        $service->ssl         = ($url['scheme'] == 'https' ? true : false);
        $service->version     = ''; // API Version
        $service->title       = get_config_plugin('blocktype', 'owncloud', 'servicetitle');
        $service->webdavurl   = get_config_plugin('blocktype', 'owncloud', 'webdavurl');
        $service->webdavproxy = get_config_plugin('blocktype', 'owncloud', 'webdavproxy');
        $service->usrprefs    = ArtefactTypeCloud::get_user_preferences('owncloud', $owner);
        if (isset($service->usrprefs['token'])) {
            $service->usrprefs['token'] = base64_decode($service->usrprefs['token']);
        }
        return $service;
    }

    public function service_list() {
        global $SESSION;
        $consumer = self::get_service_consumer();
        $owncloud = get_config_plugin('blocktype', 'owncloud', 'servicetitle');
        $service = new StdClass();
        $service->name = 'owncloud';
        $service->subname = (isset($owncloud) && !empty($owncloud) ? $owncloud : '');
        // For customized service icons...
        $service->subservice = get_config_plugin('blocktype', 'owncloud', 'subservice');
        $service->url = '';
        $service->auth = false;
        $service->manage = false;
        $service->pending = false;

        if (!empty($consumer->webdavurl)) {
            $webdavurl = parse_url($consumer->webdavurl);
            $service->url = $webdavurl['scheme'].'://'.$webdavurl['host'];
            if (isset($consumer->usrprefs['token']) && !empty($consumer->usrprefs['token'])) {
                $service->auth = true;
                $service->manage = true;
                $service->account = self::account_info();
            }
        }
        else {
            $service->pending = true;
            $SESSION->add_error_msg('Can\'t access ownCloud via WebDAV. Incorrect user credentials.');
        }
        return $service;
    }
    
    public function request_token() {
        // ownCloud doesn't use request token, but HTTP Basic Authentication
    }

    public function access_token($request_token) {
        // ownCloud doesn't use access token, but HTTP Basic Authentication
    }

    public function delete_token() {
        global $USER;
        ArtefactTypeCloud::set_user_preferences('owncloud', $USER->get('id'), null);
    }
    
    public function revoke_access() {
        // Revoke access to ownCloud by deleting user credentials
        // That happens in delete_token function
    }
    
    public function account_info() {
        global $SESSION;
        $consumer = self::get_service_consumer();

        $info = new StdClass();
        $info->service_name = 'owncloud';
        $info->service_auth = false;
        $info->user_id      = null;
        $info->user_name    = null;
        $info->user_email   = null;
        $info->user_profile = null;
        $info->space_used   = null;
        $info->space_amount = null;
        $info->space_ratio  = null;

        if (isset($consumer->usrprefs['token']) && !empty($consumer->usrprefs['token'])) {
            $url = $consumer->webdavurl;
            $port = $consumer->ssl ? '443' : '80';
            $proxy = $consumer->webdavproxy;
            $webdavurl = parse_url($consumer->webdavurl);
            $header = array();
            $header[] = 'User-Agent: ownCloud API PHP Client';
            $header[] = 'Host: ' . $webdavurl['host'];
            $header[] = 'Content-Type: application/xml; charset=UTF-8';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_PROXY => $proxy,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_CUSTOMREQUEST => 'PROPFIND',
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => $consumer->usrprefs['token'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 207) { /* HTTP/1.1 207 Multi-Status */
                $xml = simplexml_load_string(substr($result->data, $result->info['header_size']));
                $namespaces = $xml->getNameSpaces(true);
                $dav = $xml->children($namespaces['d']);
                // Get user's quota...
                $used  = (float) $dav->response[0]->propstat->prop->{'quota-used-bytes'};
                $total = (float) $dav->response[0]->propstat->prop->{'quota-available-bytes'};
                $user  = explode(':', $consumer->usrprefs['token']);

                $info->service_name = 'owncloud';
                $info->service_auth = true;
                $info->user_name    = $user[0];
                $info->space_used   = bytes_to_size1024($used);
                $info->space_amount = bytes_to_size1024($total);
                $info->space_ratio  = number_format(($used/$total)*100, 2);
                return $info;
            }
            else {
                $httpstatus = get_http_status($result->info['http_code']);
                $SESSION->add_error_msg($httpstatus);
                log_warn($httpstatus);
            }
        }
        else {
            $SESSION->add_error_msg('Can\'t access ownCloud via WebDAV. Incorrect user credentials.');
        }
        return $info;
    }
    
    
    /*
     * This function returns list of selected files/folders which will be displayed in a view/page.
     *
     * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
     * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
     */
    public function get_filelist($folder_id='/remote.php/webdav/', $selected=array(), $owner=null) {
        global $SESSION;

        // $folder_id equals to empty string if no file is
        // selected, so return ownCloud default root folder ...
        if ($folder_id == '') {
            $folder_id = '/remote.php/webdav/';
        }

        // Get folder contents...
        $consumer = self::get_service_consumer($owner);
        if (isset($consumer->usrprefs['token']) && !empty($consumer->usrprefs['token'])) {
            $webdavurl = parse_url($consumer->webdavurl);
            $url = $webdavurl['scheme'].'://'.$webdavurl['host'];
            $url .= implode('/', array_map('rawurlencode', explode('/', $folder_id)));
            $port = $consumer->ssl ? '443' : '80';
            $proxy = $consumer->webdavproxy;
            $header = array();
            $header[] = 'User-Agent: ownCloud API PHP Client';
            $header[] = 'Host: ' . $webdavurl['host'];
            $header[] = 'Content-Type: application/xml; charset=UTF-8';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_PROXY => $proxy,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_CUSTOMREQUEST => 'PROPFIND',
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => $consumer->usrprefs['token'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 207) { /* HTTP/1.1 207 Multi-Status */
                $xml = simplexml_load_string(substr($result->data, $result->info['header_size']));
                $namespaces = $xml->getNameSpaces(true);
                $dav = $xml->children($namespaces['d']);
                $output = array(
                    'folders' => array(),
                    'files'   => array()
                );
                $isFirst = true;
                if (!empty($dav->response)) {
                    foreach($dav->response as $artefact) {
                        $filepath = (string) $artefact->href;
                        // First entry in $dav->response holds general information
                        // about selected folder...
                        if ($isFirst) {
                            $isFirst = false;
                            $prefix = $filepath;
                            continue;
                        }
                        // In ownCloud WebDAV id basically means path...
                        $id = urldecode($filepath);
                        if (in_array($id, $selected)) {
                            $type         = (isset($artefact->propstat->prop->getcontenttype) ? 'file' : 'folder');
                            $artefactname = basename($filepath);
                            $title        = urldecode($artefactname);
                            $description  = '';
                            $size         = bytes_to_size1024((float) $artefact->propstat->prop->getcontentlength);
                            $created      = format_date(strtotime((string) $artefact->propstat->prop->getlastmodified), 'strftimedaydate');
                            if ($type == 'folder') {
                                $output['folders'][] = array(
                                    'id' => $id,
                                    'title' => $title,
                                    'description' => $description,
                                    'size' => $size,
                                    'ctime' => $created,
                                );
                            }
                            else {
                                $output['files'][] = array(
                                    'id' => $id,
                                    'title' => $title,
                                    'description' => $description,
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
            $SESSION->add_error_msg('Can\'t access ownCloud via WebDAV. Incorrect user credentials.');
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
     */
    public function get_folder_content($folder_id='/remote.php/webdav/', $options, $block=0) {
        global $SESSION;
        
        // $folder_id is globally set to '0', set it to '/'
        // as it is the ownCloud default root folder ...
        if ($folder_id == '0') {
            $folder_id = '/remote.php/webdav/';
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

       // Get folder contents...
        $consumer = self::get_service_consumer();
        if (isset($consumer->usrprefs['token']) && !empty($consumer->usrprefs['token'])) {
            $webdavurl = parse_url($consumer->webdavurl);
            $url = $webdavurl['scheme'].'://'.$webdavurl['host'];
            $url .= implode('/', array_map('rawurlencode', explode('/', $folder_id)));
            $port = $consumer->ssl ? '443' : '80';
            $proxy = $consumer->webdavproxy;
            $webdavurl = parse_url($consumer->webdavurl);
            $header = array();
            $header[] = 'User-Agent: ownCloud API PHP Client';
            $header[] = 'Host: ' . $webdavurl['host'];
            $header[] = 'Content-Type: application/xml; charset=UTF-8';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_PROXY => $proxy,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_CUSTOMREQUEST => 'PROPFIND',
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => $consumer->usrprefs['token'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 207) { /* HTTP/1.1 207 Multi-Status */
                $xml = simplexml_load_string(substr($result->data, $result->info['header_size']));
                $namespaces = $xml->getNameSpaces(true);
                $dav = $xml->children($namespaces['d']);
                $output = array();
                $count = 0;
                // Add 'parent' row entry to jQuery Datatable...
                if (strlen($folder_id) > strlen('/remote.php/webdav/')) {
                    $parentpath  = dirname($folder_id);
                    $type        = 'parentfolder';
                    $foldername  = get_string('parentfolder', 'artefact.file');
                    $icon        = '<span class="icon-level-up icon icon-lg"></span>';
                    $title       = '<a class="changefolder" href="javascript:void(0)" id="' . $parentpath . '" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '">' . $foldername . '</a>';
                    $output['data'][] = array($icon, $title, '', $type);
                }
                $isFirst = true;
                if (!empty($dav->response)) {
                    $detailspath = get_config('wwwroot') . 'artefact/cloud/blocktype/owncloud/details.php';         foreach($dav->response as $artefact) {
                        $filepath = (string) $artefact->href;
                        // First entry in $dav->response holds general information
                        // about selected folder...
                        if ($isFirst) {
                            $isFirst = false;
                            $prefix = $filepath;
                            continue;
                        }
                        // In ownCloud WebDAV id basically means path...
                        $id           = $filepath;
                        $type         = (isset($artefact->propstat->prop->getcontenttype) ? 'file' : 'folder');
                        // Get artefactname by removing parent path from beginning...
                        $artefactname = basename($filepath);
                        $title        = urldecode($artefactname);
                        if ($type == 'folder') {
                            $icon  = '<span class="icon-folder-open icon icon-lg"></span>';
                            $title    = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $title) . '">' . $title . '</a>';
                        }
                        else {
                            $icon  = '<span class="icon-file icon icon-lg"></span>';
                            $title    = '<a class="filedetails" href="' . $detailspath . '?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $title) . '">' . $title . '</a>';
                        }
                        $controls = '';
                        $selected = (in_array(urldecode($id), $artefacts) ? ' checked' : '');
                        if ($type == 'folder') {
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
            $SESSION->add_error_msg('Can\'t access ownCloud via WebDAV. Incorrect user credentials.');
        }
    }

    public function get_folder_info($folder_id='/remote.php/webdav/', $owner=null) {
        global $SESSION;
        // Fix: everything except / gets urlencoded
        $folder_id = implode('/', array_map('rawurlencode', explode('/', $folder_id)));
        $consumer = self::get_service_consumer($owner);
        if (isset($consumer->usrprefs['token']) && !empty($consumer->usrprefs['token'])) {
            $webdavurl = parse_url($consumer->webdavurl);
            $url = $webdavurl['scheme'].'://'.$webdavurl['host'].$folder_id;
            $port = $consumer->ssl ? '443' : '80';
            $proxy = $consumer->webdavproxy;
            $webdavurl = parse_url($consumer->webdavurl);
            $header = array();
            $header[] = 'User-Agent: ownCloud API PHP Client';
            $header[] = 'Host: ' . $webdavurl['host'];
            $header[] = 'Content-Type: application/xml; charset=UTF-8';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_PROXY => $proxy,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_CUSTOMREQUEST => 'PROPFIND',
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => $consumer->usrprefs['token'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 207) { /* HTTP/1.1 207 Multi-Status */
                $xml = simplexml_load_string(substr($result->data, $result->info['header_size']));
                $namespaces = $xml->getNameSpaces(true);
                $dav = $xml->children($namespaces['d']);
                // Get info about artefact (folder)
                $artefact = $dav->response;
                $filepath = (string) $artefact->href;
 
                $info = array(
                    'id'          => $filepath,
                    'parent_id'   => dirname($filepath) . '/',
                    'name'        => basename($filepath),
                    'description' => '', // ownCloud doesn't support file/folder descriptions...
                    'updated'     => format_date(strtotime((string) $artefact->propstat->prop->getlastmodified), 'strfdaymonthyearshort'),
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
            $SESSION->add_error_msg('Can\'t access ownCloud via WebDAV. Incorrect user credentials.');
        }
    }

    public function get_file_info($file_id='/remote.php/webdav/', $owner=null) {
        global $SESSION;
        // Fix: everything except / gets urlencoded
        $file_id = implode('/', array_map('rawurlencode', explode('/', $file_id)));
        $consumer = self::get_service_consumer($owner);
        if (isset($consumer->usrprefs['token']) && !empty($consumer->usrprefs['token'])) {
            $webdavurl = parse_url($consumer->webdavurl);
            $url = $webdavurl['scheme'].'://'.$webdavurl['host'].$file_id;
            $port = $consumer->ssl ? '443' : '80';
            $proxy = $consumer->webdavproxy;
            $webdavurl = parse_url($consumer->webdavurl);
            $header = array();
            $header[] = 'User-Agent: ownCloud API PHP Client';
            $header[] = 'Host: ' . $webdavurl['host'];
            $header[] = 'Content-Type: application/xml; charset=UTF-8';
            $config = array(
                CURLOPT_URL => $url,
                CURLOPT_PORT => $port,
                CURLOPT_PROXY => $proxy,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => $header,
                CURLOPT_CUSTOMREQUEST => 'PROPFIND',
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => $consumer->usrprefs['token'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
            );
            $result = mahara_http_request($config);
            if (isset($result->data) && !empty($result->data) &&
                isset($result->info) && !empty($result->info) && $result->info['http_code'] == 207) { /* HTTP/1.1 207 Multi-Status */
                $xml = simplexml_load_string(substr($result->data, $result->info['header_size']));
                $namespaces = $xml->getNameSpaces(true);
                $dav = $xml->children($namespaces['d']);
                // Get info about artefact (file)
                $artefact = $dav->response;
                $filepath = (string) $artefact->href;
                $filesize = (float) $artefact->propstat->prop->getcontentlength;

                $info = array(
                    'id'          => $filepath,
                    'parent_id'   => dirname($filepath) . '/',
                    'name'        => urldecode(basename($filepath)),
                    'bytes'       => $filesize,
                    'size'        => bytes_to_size1024($filesize), 
                    'description' => '', // ownCloud doesn't support file/folder descriptions...
                    'updated'     => format_date(strtotime((string) $artefact->propstat->prop->getlastmodified), 'strfdaymonthyearshort'),
                    'mimetype'    => (string) $artefact->propstat->prop->getcontenttype,
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
            $SESSION->add_error_msg('Can\'t access ownCloud via WebDAV. Incorrect user credentials.');
        }
    }

    public function download_file($file_id='/remote.php/webdav/', $owner=null) {
        global $SESSION;
        $consumer = self::get_service_consumer($owner);
        if (isset($consumer->usrprefs['token']) && !empty($consumer->usrprefs['token'])) {
            $webdavurl = parse_url($consumer->webdavurl);
            $download_url = $webdavurl['scheme'].'://'.$webdavurl['host'].$file_id;
            $port = $consumer->ssl ? '443' : '80';
            $proxy = $consumer->webdavproxy;
            $webdavurl = parse_url($consumer->webdavurl);
            $header = array();
            $header[] = 'User-Agent: ownCloud API PHP Client';
            $header[] = 'Host: ' . $webdavurl['host'];
            $header[] = 'Content-Type: application/xml; charset=UTF-8';

            $ch = curl_init($download_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_PORT, $port);
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
            curl_setopt($ch, CURLOPT_POST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $consumer->usrprefs['token']);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        }
        else {
            $SESSION->add_error_msg('Can\'t access ownCloud via WebDAV. Incorrect user credentials.');
        }
    }

    public function embed_file($file_id='/remote.php/webdav/', $options=array(), $owner=null) {
        // ownCloud doesn't support embedding of files, so:
        // Nothing to do!
    }

}
