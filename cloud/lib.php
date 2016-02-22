<?php
/**
 *
 * @package    mahara
 * @subpackage artefact-cloud
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2016 Gregor Anzelj, info@povsod.com
 *
 */

defined('INTERNAL') || die();

class PluginArtefactCloud extends PluginArtefact {
    
    public static function get_artefact_types() {
        return array(
            'cloud',
        );
    }
    
    public static function get_block_types() {
        return array(); 
    }

    public static function get_plugin_name() {
        return 'cloud';
    }

    public static function menu_items() {
        return array(
            'content/clouds' => array(
                'path' => 'content/clouds',
                'title' => get_string('clouds', 'artefact.cloud'),
                'url' => 'artefact/cloud/',
                // Just one more than Files, so the 'Clouds'
                // tab will show just after 'Files' tab
                'weight' => 31,
            ),
        );
    }

    public static function postinst($prevversion) {

        if ($prevversion < 2014062600) {
            global $SESSION;
            // Windows Live SkyDrive became Microsoft One Drive,
         // so we need to:

            // 1. Update/convert artefact types
            if ($artefacts = get_records_array('artefact', 'title', 'skydrive')) {
                foreach ($artefacts as $artefact) {
                    update_record('artefact', array('title' => 'microsoftdrive'), array('id' => $artefact->id));
                }
            }

            // 2. Remove records from blocktype_installed* tables
            delete_records('blocktype_installed_category', 'blocktype', 'skydrive');
            delete_records('blocktype_installed_viewtype', 'blocktype', 'skydrive');
            delete_records('blocktype_config', 'plugin', 'skydrive');
            delete_records('blocktype_installed', 'name', 'skydrive');

            // 3. Delete 'skydrive' blocktype folder
            $folder = get_config('docroot') . 'artefact/cloud/blocktype/skydrive/';
            if (file_exists($folder) && is_dir($folder)) {
                if (unlink($folder . 'version.php')) {
                    recursive_skydrive_folder_delete($folder);
                } else {
                    $SESSION->add_error_msg("Could not delete 'skydrive' folder. Delete it manually.");
                }
            }
        }
    }
    
}

/*
 * Helper function to recursively delete all files and 
 * sub-folders in selected folder (with the folder itself)
 *
 * This is used in 'postinst' function above
 */
function recursive_skydrive_folder_delete($path) {
    $files = scandir($path);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            if (is_dir($path . $file)) {
                recursive_folder_delete($path . $file . '/');
            } else {
                unlink($path . $file);
            }
        }
    }
    rmdir($path);
}

class ArtefactTypeCloud extends ArtefactType {

    public static function get_icon($options=null) {}

    public function __construct($id=0, $data=array()) {
        if (empty($id)) {
            $data['title'] = get_string($this->get_artefact_type(), 'artefact.cloud'); // ???
        }
        parent::__construct($id, $data);
    }
    
    public static function is_singular() {
        return false; // ??? We can have more than one cloud?
    }

    public static function format_child_data($artefact, $pluginname) {
        $a = new StdClass;
        $a->id         = $artefact->id;
        $a->isartefact = true;
        $a->title      = ''; // ???
        $a->text       = get_string($artefact->artefacttype, 'artefact.cloud'); // $artefact->title; // ???
        $a->container  = (bool) $artefact->container;
        $a->parent     = $artefact->id;
        return $a;
    }

    public static function get_links($id) { }

    /**
     * Default render method for resume fields - show their description
     */
    public function render_self($options) {
        return array('html' => clean_html($this->description));
    }
    
    public function get_user_services($userid) {
        $sql = "SELECT bi.name AS title
                FROM {blocktype_installed} bi
                WHERE bi.active = 1 AND bi.artefactplugin = 'cloud'";
        $services = get_records_sql_array($sql, null);
        return $services;
    }

    public function get_user_preferences($cloud, $userid) {
        // Return unserialized field 'description' from table 'artefact'
        // where artefacttype=cloud, title=$cloud and owner=$userid
        $prefs = get_field('artefact', 'description', 'artefacttype', 'cloud', 'title', $cloud, 'owner', $userid);
        if ($prefs) {
            $data = unserialize($prefs);
            // Add data about when this record was created and modified...
            $record = get_record('artefact', 'artefacttype', 'cloud', 'title', $cloud, 'owner', $userid);
            $data['record_ctime'] = $record->ctime;
            $data['record_mtime'] = $record->mtime;
        }
        else {
            $data = null;
        }

        return $data;
    }

    public function set_user_preferences($cloud, $userid, $values) {
        $where = array(
            'artefacttype' => 'cloud',
            'owner' => $userid,
            'title' => $cloud,
        );
        $dbnow = db_format_timestamp(time());
        $data = array(
            'artefacttype' => 'cloud',
            'owner' => $userid,
            'title' => $cloud,
            'description' => serialize($values),
            'author' => $userid,
            'ctime' => $dbnow,
            'mtime' => $dbnow,
            'atime' => $dbnow
        );
        ensure_record_exists('artefact', $where, $data);
    }

}

/*
 *
 * This class extends the base plugin blocktype class.
 *
 * It implements additional methods for use with clouds.
 * All cloud blocktype classes *should* extend this one.
 *
 */
 
require_once(get_config('docroot') . 'blocktype/lib.php');

abstract class PluginBlocktypeCloud extends PluginBlocktype {

    /*
     * Method that returns data about cloud,
     * needed in 'index.php' (cloud list).
     */
    public abstract function service_list();
    
    /*
     * Method for requesting request_token
     */
    public abstract function request_token();

    /*
     * Method for requesting access_token
     */
    public abstract function access_token($params);
    
    /*
     * Method for deleting token(s)
     */
    public abstract function delete_token();
    
    /*
     * Method for programmatical access revoking
     */
    public abstract function revoke_access();
    
    /*
     * Method for displaying account info
     */
    public abstract function account_info();

    /*
     * Method for building filelist data for views/pages
     */
    public abstract function get_filelist($folder_id, $selected, $ownerid);

    /*
     * Method for building JSON filelist data for config form
     */
    public abstract function get_folder_content($folder_id, $options, $block);

    /*
     * Method for getting info about folder (on Cloud Service)
     */
    public abstract function get_folder_info($folder_id);

    /*
     * Method for getting info about file (on Cloud Service)
     */
    public abstract function get_file_info($file_id);

    /*
     * Method for downloading file (on Cloud Service)
     */
    public abstract function download_file($file_id);

    /*
     * Method for embedding file (on Cloud Service)
     */
    public abstract function embed_file($file_id, $options);

}

/**
 * Converts bytes into human readable format (use powers of 1024)
 * @param integer $bytes
 * @param integer $precision  when rounding the value
 * @return string float value rounded according to precision with correct suffix
 * @link http://codeaid.net/php/convert-size-in-bytes-to-a-human-readable-format-%28php%29
 */
function bytes_to_size1024($bytes, $precision=2) {
    $unit = array('B','KB','MB','GB','TB','PB','EB');
    return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision).''.$unit[$i];
}

/**
 * Converts seconds into human readable format (HH:MM:SS),
 * even if there are more than 24 hours...
 * @param integer $seconds
 * @return string time formated as HH:MM:SS
 * @link http://bytes.com/topic/php/answers/3917-seconds-converted-hh-mm-ss
 */
function seconds_to_hms($seconds) {
    if ($seconds <= 0) return '00:00:00';
    $minutes = (int)($seconds / 60);
    $seconds = $seconds % 60;
    $hours = (int)($minutes / 60);
    $minutes = $minutes % 60;
    $time = array(
        str_pad($hours, 2, "0", STR_PAD_LEFT),
        str_pad($minutes, 2, "0", STR_PAD_LEFT),
        str_pad($seconds, 2, "0", STR_PAD_LEFT),
    );
    return implode(':', $time);
}

/**
 * Returns folder tree as options for web form select element
 * @param integer $parent_id   id of the parent folder
 * @param integer $level       level in the tree (for identation)
 */
function get_foldertree_options($parent_id=null, $level='/') {
    global $options;
    $current = $level . get_string('home', 'artefact.file');
    $options = array('0' => $current);
    get_foldertree_recursion($parent_id, $current);
    return $options;
}

// Helper function, used in above get_foldertree_options function...
function get_foldertree_recursion($parent_id=null, $level='/') {
    global $USER, $options;
    if (is_null($parent_id)) {
        $folders = get_records_sql_array('
            SELECT a.id, a.title, a.parent
            FROM {artefact} a
            WHERE a.artefacttype = ? AND a.owner = ? AND a.parent IS NULL
            ORDER BY a.title', array('folder', $USER->get('id')));
        } else {
        $folders = get_records_sql_array('
            SELECT a.id, a.title, a.parent
            FROM {artefact} a
            WHERE a.artefacttype = ? AND a.owner = ? AND a.parent = ?
            ORDER BY a.title', array('folder', $USER->get('id'), $parent_id));
        }
    if ($folders) {
        foreach ($folders as $folder) {
            $current = $level . '/' . $folder->title;
            $options[$folder->id] = $current;
            // Recursion...
            get_foldertree_recursion($folder->id, $current);
        }
    }
}

// Function to find nearest value (in array of values) to given value
// e.g.: user defined thumbnail width is 75, abvaliable picasa thumbnails are array(32, 48, 64, 72, 104, 144, 150, 160)
//         so this function should return 72 (which is nearest form available values)
// Function found at http://www.sitepoint.com/forums/showthread.php?t=537541
function find_nearest($values, $item) {
    if (in_array($item,$values)) {
        $out = $item;
    }
    else {
        sort($values);
        $length = count($values);
        for ($i=0; $i<$length; $i++) {
            if ($values[$i] > $item) {
                if ($i == 0) {
                    return $values[$i];
                }
                $out = ($item - $values[$i-1]) > ($values[$i]-$item) ? $values[$i] : $values[$i-1];
                break;
            }
        }
    }
    if (!isset($out)) {
        $out = end($values);
    }
    return $out;
}

// Function to print out HTTP response status code and description
function get_http_status($code) {
    switch (intval($code)) {
        case 100: $msg = 'HTTP status: 100 Continue'; break;
        case 101: $msg = 'HTTP status: 101 Switching Protocols'; break;
        case 102: $msg = 'HTTP status: 102 Processing'; break;
        case 200: $msg = 'HTTP status: 200 OK'; break;
        case 201: $msg = 'HTTP status: 201 Created'; break;
        case 202: $msg = 'HTTP status: 202 Accepted'; break;
        case 203: $msg = 'HTTP status: 203 Non-Authoritative Information'; break;
        case 204: $msg = 'HTTP status: 204 No Content'; break;
        case 205: $msg = 'HTTP status: 205 Reset Content'; break;
        case 206: $msg = 'HTTP status: 206 Partial Content'; break;
        case 207: $msg = 'HTTP status: 207 Multi-Status'; break;
        case 208: $msg = 'HTTP status: 208 Already Reported'; break;
        case 226: $msg = 'HTTP status: 226 IM Used'; break;
        case 300: $msg = 'HTTP status: 300 Multiple Choices'; break;
        case 301: $msg = 'HTTP status: 301 Moved Permanently'; break;
        case 302: $msg = 'HTTP status: 302 Found'; break;
        case 303: $msg = 'HTTP status: 303 See Other'; break;
        case 304: $msg = 'HTTP status: 304 Not Modified'; break;
        case 305: $msg = 'HTTP status: 305 Use Proxy'; break;
        case 306: $msg = 'HTTP status: 306 Switch Proxy'; break;
        case 307: $msg = 'HTTP status: 307 Temporary Redirect'; break;
        case 308: $msg = 'HTTP status: 308 Permanent Redirect; Resume Incomplete (Google)'; break;
        case 400: $msg = 'HTTP status: 400 Bad Request'; break;
        case 401: $msg = 'HTTP status: 401 Unauthorized'; break;
        case 402: $msg = 'HTTP status: 402 Payment Required'; break;
        case 403: $msg = 'HTTP status: 403 Forbidden'; break;
        case 404: $msg = 'HTTP status: 404 Not Found'; break;
        case 405: $msg = 'HTTP status: 405 Method Not Allowed'; break;
        case 406: $msg = 'HTTP status: 406 Not Acceptable'; break;
        case 407: $msg = 'HTTP status: 407 Proxy Authentication Required'; break;
        case 408: $msg = 'HTTP status: 408 Request Timeout'; break;
        case 409: $msg = 'HTTP status: 409 Conflict'; break;
        case 410: $msg = 'HTTP status: 410 Gone'; break;
        case 411: $msg = 'HTTP status: 411 Length Required'; break;
        case 412: $msg = 'HTTP status: 412 Precondition Failed'; break;
        case 413: $msg = 'HTTP status: 413 Payload Too Large'; break;
        case 414: $msg = 'HTTP status: 414 Request-URI Too Long'; break;
        case 415: $msg = 'HTTP status: 415 Unsupported Media Type'; break;
        case 416: $msg = 'HTTP status: 416 Requested Range Not Satisfiable'; break;
        case 417: $msg = 'HTTP status: 417 Expectation Failed'; break;
        case 419: $msg = 'HTTP status: 419 Authentication Timeout'; break;
        case 421: $msg = 'HTTP status: 421 Misdirected Request'; break;
        case 422: $msg = 'HTTP status: 422 Unprocessable Entity'; break;
        case 423: $msg = 'HTTP status: 423 Locked'; break;
        case 424: $msg = 'HTTP status: 424 Failed Dependency'; break;
        case 426: $msg = 'HTTP status: 426 Upgrade Required'; break;
        case 428: $msg = 'HTTP status: 428 Precondition Required'; break;
        case 429: $msg = 'HTTP status: 429 Too Many Requests'; break;
        case 431: $msg = 'HTTP status: 431 Request Header Fields Too Large'; break;
        case 440: $msg = 'HTTP status: 440 Login Timeout'; break;
        case 444: $msg = 'HTTP status: 444 No Response'; break;
        case 449: $msg = 'HTTP status: 449 Retry With'; break;
        case 450: $msg = 'HTTP status: 450 Blocked by Windows Parental Controls'; break;
        case 451: $msg = 'HTTP status: 451 Unavailable For Legal Reasons; Redirect (Microsoft)'; break;
        case 494: $msg = 'HTTP status: 494 Request Header Too Large'; break;
        case 495: $msg = 'HTTP status: 495 Cert Error'; break;
        case 496: $msg = 'HTTP status: 496 No Cert'; break;
        case 497: $msg = 'HTTP status: 497 HTTP to HTTPS'; break;
        case 498: $msg = 'HTTP status: 498 Token expired/invalid'; break;
        case 499: $msg = 'HTTP status: 499 Client Closed Request (Nginx); Token required (Esri)'; break;
        case 500: $msg = 'HTTP status: 500 Internal Server Error'; break;
        case 501: $msg = 'HTTP status: 501 Not Implemented'; break;
        case 502: $msg = 'HTTP status: 502 Bad Gateway'; break;
        case 503: $msg = 'HTTP status: 503 Service Unavailable'; break;
        case 504: $msg = 'HTTP status: 504 Gateway Timeout'; break;
        case 505: $msg = 'HTTP status: 505 HTTP Version Not Supported'; break;
        case 506: $msg = 'HTTP status: 506 Variant Also Negotiates'; break;
        case 507: $msg = 'HTTP status: 507 Insufficient Storage'; break;
        case 508: $msg = 'HTTP status: 508 Loop Detected'; break;
        case 509: $msg = 'HTTP status: 509 Bandwidth Limit Exceeded'; break;
        case 510: $msg = 'HTTP status: 510 Not Extended'; break;
        case 511: $msg = 'HTTP status: 511 Network Authentication Required'; break;
        case 520: $msg = 'HTTP status: 520 Unknown Error'; break;
        case 522: $msg = 'HTTP status: 522 Origin Connection Time-out'; break;
        case 598: $msg = 'HTTP status: 598 Network read timeout error'; break;
        case 599: $msg = 'HTTP status: 599 Network connect timeout error'; break;
        default:  $msg = 'HTTP status: Unknown status with code ' . $code;
    }
    return $msg;
}
