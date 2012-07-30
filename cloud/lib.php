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
 * @subpackage artefact-cloud
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

class PluginArtefactCloud extends Plugin {
    
    public static function get_artefact_types() {
        return array(
			'cloud'
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
	
	
	public function get_enabled_services() {
		$sql = 'SELECT b.name
			FROM {blocktype_installed} b 
			WHERE b.artefactplugin = ? AND b.active = ?
			ORDER BY b.name ASC';
		$clouds = get_records_sql_array($sql, array('cloud', true));
		$enabled = array();
		foreach ($clouds as $cloud) {
			$object = new StdClass;
			$object->title = $cloud->name;
			$object->description = '';
			$enabled[$cloud->name] = $object;
		}
		return $enabled;
	}
	
	public function get_user_services($userid) {
		$sql = 'SELECT a.title, a.description
			FROM {artefact} a 
			WHERE a.artefacttype = ? AND a.owner = ?
			ORDER BY a.title ASC';
		$user_clouds = get_records_sql_assoc($sql, array('cloud', $userid));
		$enabled_clouds = self::get_enabled_services();
		if ($user_clouds) {
			// Enabled clouds, unused by user
			$diff_clouds = array_diff_key($enabled_clouds, $user_clouds);
			$merged = array_merge($diff_clouds, $user_clouds);
			ksort($merged);
		} else {
			$merged = $enabled_clouds;
			ksort($merged);
		}
		$return = array();
		foreach ($merged as $key => $value) {
			$return[$key] = $value;
		}
		return $return;
	}

	public function get_user_preferences($cloud, $userid) {
		// Return unserialized field 'description' from table 'artefact'
		// where artefacttype=cloud, title=$cloud and owner=$userid
		$prefs = get_field('artefact', 'description', 'artefacttype', 'cloud', 'title', $cloud, 'owner', $userid);
		return unserialize($prefs);
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
	public abstract function get_filelist($folder_id, $selected);

	/*
	 * Method for building JSON filelist data for config form
	 */
	public abstract function get_folder_content($folder_id, $options, $block, $fullpath);

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
function bytes_to_size1024($bytes, $precision=2)
{
    $unit = array('B','kB','MB','GB','TB','PB','EB');
    return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision).''.$unit[$i];
}


/**
 * Returns folder tree as options for web form select element
 * @param integer $parent_id   id of the parent folder
 * @param integer $level       level in the tree (for identation)
 */
function get_foldertree_options($parent_id=null, $level=0) {
	global $options;
	$options = array('0' => array('value' => get_string('home', 'artefact.file'), 'style' => 'padding-left:5px;'));
	get_foldertree_recursion($parent_id, $level);
	return $options;
}

// Helper function, used in above get_foldertree_options function...
function get_foldertree_recursion($parent_id=null, $level=0) {
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
			$padding = 10 * ($level+1) + 5;
		    $options[$folder->id] = array('value' => $folder->title, 'style' => 'padding-left:'. $padding .'px;');
		    // Recursion...
		    get_foldertree_recursion($folder->id, $level+1);
		}
	}
}

?>
