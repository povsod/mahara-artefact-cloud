<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-sugarsync
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2014 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

define('INTERNAL', 1);
define('MENUITEM', 'content/clouds');
define('SECTION_PLUGINTYPE', 'artefact');
define('SECTION_PLUGINNAME', 'cloud');
define('SECTION_PAGE', 'index');

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/init.php');
define('TITLE', get_string('servicename', 'blocktype.cloud/sugarsync'));
require_once('lib.php');

$action = param_alpha('action', 'info');


switch ($action) {
    case 'login':
        $consentform = pieform(array(
            'name'   => 'consent',
            'autofocus'  => false,
            'elements' => array(
                'username' => array(
                    'type' => 'text',
                    'title' => get_string('email'),
                ),
                'password' => array(
                    'type' => 'password',
                    'title' => get_string('password'),
                ),
                'submitcancel' => array(
                    'type' => 'submitcancel',
                    'value' => array(get_string('allow', 'artefact.cloud'), get_string('deny', 'artefact.cloud')),
                    'goto' => get_config('wwwroot') . 'artefact/cloud/',
                ),
            ),
        ));
        $smarty = smarty();
        $smarty->assign('service', 'sugarsync');
        $smarty->assign('sitename', get_config('sitename'));
        $smarty->assign('servicename', get_string('servicename', 'blocktype.cloud/sugarsync'));
        $smarty->assign('form', $consentform);
        $smarty->display('artefact:cloud:consent.tpl');
        break;
    case 'logout':
        PluginBlocktypeSugarsync::revoke_access();
        PluginBlocktypeSugarsync::delete_token();
        redirect(get_config('wwwroot').'artefact/cloud');
        break;
    default:
        $account = PluginBlocktypeSugarsync::account_info();
        $smarty = smarty();
        //$smarty->assign('PAGEHEADING', TITLE);
        $smarty->assign('account', $account);
        $smarty->display('artefact:cloud:account.tpl');
}

function consent_submit(Pieform $form, $values) {
    global $USER, $SESSION;
    $username = $values['username'];
    $password = $values['password'];

    $consumer = PluginBlocktypeSugarsync::get_service_consumer();
    $appid  = $consumer->appid;
    $key    = $consumer->key;
    $secret = $consumer->secret;
    
    $request_body = <<< XML
<?xml version="1.0" encoding="UTF-8" ?>
<appAuthorization>
 <username>$username</username>
 <password>$password</password>
 <application>$appid</application>
 <accessKeyId>$key</accessKeyId>
 <privateAccessKey>$secret</privateAccessKey>
</appAuthorization>
XML;

    if (!empty($consumer->key) && !empty($consumer->secret)) {
        // SugarSync doesn't have API version yet, so...
        $url = $consumer->appauthurl;
        $port = $consumer->ssl ? '443' : '80';
        $header = array();
        $header[] = 'User-Agent: SugarSync API PHP Client';
        $header[] = 'Host: api.sugarsync.com';
        $header[] = 'Content-Length: ' . strlen($request_body);
        $header[] = 'Content-Type: application/xml; charset=UTF-8';
        $config = array(
            CURLOPT_URL => $url,
            CURLOPT_PORT => $port,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request_body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CAINFO => get_config('docroot').'artefact/cloud/cert/cacert.crt'
        );
        $result = mahara_http_request($config);
        if ($result->info['http_code'] == 201 /* HTTP/1.1 201 Created */ && !empty($result->data)) {
            $matches = array();
            preg_match('#app-authorization\/([A-Za-z0-9]+)#', $result->data, $matches);
            $refresh_token = $matches[1];
            ArtefactTypeCloud::set_user_preferences('sugarsync', $USER->get('id'), array('refresh_token' => $refresh_token));
            redirect(get_config('wwwroot') . 'artefact/cloud/');
        } else {
            $SESSION->add_error_msg(get_string('refreshtokennotreturned', 'blocktype.cloud/sugarsync'));
        }
    } else {
        throw new ConfigException('Can\'t find SugarSync consumer key and/or consumer secret.');
    }
}


?>