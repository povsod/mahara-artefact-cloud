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
 * @subpackage blocktype-zotero
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/http-request.php');
require_once('lib/api.php'); // Zotero API methods


class PluginBlocktypeZotero extends PluginBlocktypeCloud {
	
	const servicepath = 'zoteropath';
	
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/zotero');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/zotero');
    }

    public static function get_categories() {
        return array('external');
    }

    public static function render_instance(BlockInstance $instance, $editing=false) {
        $configdata = $instance->get('configdata');
        $viewid     = $instance->get('view');

		// User selected single collection of references for display...
		if ($configdata['artefacts'][0] != '0') {
			// $connection = $data[1] which will actually hold the ID of collection
			$data = explode('-', $configdata['artefacts'][0]);
			$collection = $data[1];
		}
		// User selected to display all items in the library...
		else {
			$collection = '0';
		}
		$bibstyle = $configdata['bibstyle'];
		
		$result = self::get_filelist($collection, $bibstyle);
		return $result;
    }

    public static function has_instance_config() {
        return true;
    }

    public static function instance_config_form($instance) {
		$instanceid = $instance->get('id');
        $configdata = $instance->get('configdata');
        safe_require('artefact', 'cloud');
        $instance->set('artefactplugin', 'cloud');
		
		return array(
			'zoterorefs' => array(
				'type'     => 'datatables',
				'title'    => get_string('selectreferences','blocktype.cloud/zotero'),
				'service'  => 'zotero',
				'block'    => $instanceid,
				'fullpath' => (isset($configdata['fullpath']) ? $configdata['fullpath'] : null),
				'options'  => array(
					'showFolders'    => true,
					'showFiles'      => false,
					'selectFolders'  => true,
					'selectFiles'    => false,
					'selectMultiple' => false
				),
			),
			'bibstyle' => array(
				'type'         => 'select',
				'title'        => get_string('bibliographystyle','blocktype.cloud/zotero'),
				'options'      => get_zotero_bibstyles(),
				'defaultvalue' => (isset($configdata['bibstyle']) ? $configdata['bibstyle'] : null),
			),
		);
    }

    public static function instance_config_save($values) {
		global $_SESSION;
		// Folder and file IDs (and other values) are returned as JSON/jQuery serialized string.
		// We have to parse that string and urldecode it (to correctly convert square brackets)
		// in order to get cloud folder and file IDs - they are stored in $artefacts array.
		parse_str(urldecode($values['zoterorefs']));
		if (!isset($artefacts) || empty($artefacts)) {
			$artefacts = array();
		}
		
		$values = array(
			'title'       => $values['title'],
			'fullpath'    => $_SESSION[self::servicepath],
			'artefacts'   => $artefacts,
			// Bibliography style
			'bibstyle'    => $values['bibstyle']
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
			'value' => get_string('applicationdesc', 'blocktype.cloud/zotero', '<a href="http://www.zotero.org/oauth/apps" target="_blank">', '</a>'),
		);
        $elements['applicationgeneral'] = array(
			'type' => 'fieldset',
			'legend' => get_string('applicationgeneral', 'blocktype.cloud/zotero'),
			'elements' => array(
				'applicationname' => array(
					'type'         => 'text',
					'title'        => get_string('applicationname', 'blocktype.cloud/zotero'),
					'defaultvalue' => get_config('sitename'),
					'description'  => get_string('applicationnamedesc', 'blocktype.cloud/zotero'),
					'readonly'     => true,
				),
				'consumerkey' => array(
					'type'         => 'text',
					'title'        => get_string('consumerkey', 'blocktype.cloud/zotero'),
					'defaultvalue' => get_config_plugin('blocktype', 'zotero', 'consumerkey'),
					'description'  => get_string('consumerkeydesc', 'blocktype.cloud/zotero'),
					'rules' => array('required' => true),
				),
				'consumersecret' => array(
					'type'         => 'text',
					'title'        => get_string('consumersecret', 'blocktype.cloud/zotero'),
					'defaultvalue' => get_config_plugin('blocktype', 'zotero', 'consumersecret'),
					'description'  => get_string('consumersecretdesc', 'blocktype.cloud/zotero'),
					'rules' => array('required' => true),
				),
				'applicationtype' => array(
					'type'         => 'radio',
					'title'        => get_string('applicationtype', 'blocktype.cloud/zotero'),
					'defaultvalue' => 'browser',
					'description'  => get_string('applicationtypedesc', 'blocktype.cloud/zotero'),
					'options'      => array(
						'client'  => 'Client',
						'browser' => 'Browser',
					),
					'disabled'     => true,
				),
				'applicationweb' => array(
					'type'         => 'text',
					'title'        => get_string('applicationweb', 'blocktype.cloud/zotero'),
					'defaultvalue' => get_config('wwwroot'),
					'description'  => get_string('applicationwebdesc', 'blocktype.cloud/zotero'),
					'size'         => 50,
					'readonly'     => true,
				),
				'redirecturl' => array(
					'type'         => 'text',
					'title'        => get_string('redirecturl', 'blocktype.cloud/zotero'),
					'defaultvalue' => get_config('wwwroot') . 'artefact/cloud/blocktype/zotero/callback.php',
					'description'  => get_string('redirecturldesc', 'blocktype.cloud/zotero'),
					'size'         => 70,
					'readonly'     => true,
					'rules' => array('required' => true),
				),
			)
		);
        return array(
            'elements' => $elements,
        );

    }

    public static function save_config_options($values) {
        set_config_plugin('blocktype', 'zotero', 'consumerkey', $values['consumerkey']);
        set_config_plugin('blocktype', 'zotero', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

	/********************************************
	 * Methods & stuff for accessing Zotero API *
	 ********************************************/
	
	public function cloud_info() {
		return array(
			'ssl'        => true,
			'version'    => '',
			'baseurl'    => 'https://api.zotero.org/',
			//'contenturl' => 'https://api.zotero.org/',
			'wwwurl'     => 'https://www.zotero.org/',
		);
	}
	
	public function consumer_tokens() {
		return array(
			'key'      => get_config_plugin('blocktype', 'zotero', 'consumerkey'),
			'secret'   => get_config_plugin('blocktype', 'zotero', 'consumersecret'),
			'callback' => get_config('wwwroot') . 'artefact/cloud/blocktype/zotero/callback.php'
		);
	}
	
	public function user_tokens($userid) {
		return ArtefactTypeCloud::get_user_preferences('zotero', $userid);
	}
	
	/*
	 * SEE: http://www.zotero.org/support/dev/server_api/read_api
	 */
	public function service_list() {
		global $USER, $SESSION;
		$cloud    = self::cloud_info();
		$consumer = self::consumer_tokens();
		$usertoken = self::user_tokens($USER->get('id'));
		if (!empty($consumer['key']) && !empty($consumer['secret'])) {
			$request = ZoteroAPI::make_api_call('GET', 'users/'.$usertoken['userID'].'/keys/'.$usertoken['oauth_token'], $cloud, $consumer, $usertoken, array(), array());
			if (!empty($request)) {
				list($info, $headers, $body) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					return array(
						'service_name'  => 'zotero',
						'service_url'   => 'http://www.zotero.com',
						'service_auth'  => true,
						//'revoke_access' => false,
					);
				} else {
					return array(
						'service_name'  => 'zotero',
						'service_url'   => 'http://www.zotero.com',
						'service_auth'  => false,
						//'revoke_access' => false,
					);
				}
			}
		} else {
			throw new ConfigException('Can\'t find Zotero consumer key and/or consumer secret.');
		}
	}
	
	public function request_token() {
		global $USER;
		$cloud    = self::cloud_info();
		$consumer = self::consumer_tokens();
		if (!empty($consumer['key']) && !empty($consumer['secret'])) {
			$request = ZoteroAPI::get_request_token('POST', $cloud, $consumer);
			if (!empty($request)) {
				list($info, $headers, $body, $body_parsed) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					// Store request_token (oauth_token) and request_token_secret (outh_token_secret)
					// We'll need it later...
					$prefs = oauth_parse_str($body);
					ArtefactTypeCloud::set_user_preferences('zotero', $USER->get('id'), $prefs);
					redirect($cloud['wwwurl'].$cloud['version'].'/oauth/authorize?'.rfc3986_decode($body).'&oauth_callback='.$consumer['callback']);
				} else {
					$SESSION->add_error_msg(get_string('requesttokennotreturned', 'blocktype.cloud/zotero'));
				}
			}
		} else {
			throw new ConfigException('Can\'t find Zotero consumer key and/or consumer secret.');
		}
	}

	public function access_token($params) {
		global $USER, $SESSION;
		$cloud    = self::cloud_info();
		$consumer = self::consumer_tokens();
		if (!empty($consumer['key']) && !empty($consumer['secret'])) {
			$request = ZoteroAPI::get_access_token('POST', $cloud, $consumer, $params);
			if (!empty($request)) {
				list($info, $headers, $body, $body_parsed) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					// Store access_token (oauth_token) and access_token_secret (outh_token_secret)
					// We'll need it for all API calls...
					$prefs = oauth_parse_str($body);
					ArtefactTypeCloud::set_user_preferences('zotero', $USER->get('id'), $prefs);
				} else {
					$SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/zotero'));
				}
			}
		} else {
			throw new ConfigException('Can\'t find Zotero consumer key and/or consumer secret.');
		}
	}

	public function delete_token() {
		global $USER;
		ArtefactTypeCloud::set_user_preferences('zotero', $USER->get('id'), null);
	}
	
	public function revoke_access() {
		// Zotero API doesn't allow programmatical access revoking, so:
		// Nothing to do!
	}
	
	/*
	 * SEE: http://www.zotero.org/support/dev/server_api/read_api
	 */
	public function account_info() {
		global $USER;
		$cloud     = self::cloud_info();
		$consumer  = self::consumer_tokens();
		$usertoken = self::user_tokens($USER->get('id'));
		if (!empty($consumer['key']) && !empty($consumer['secret'])) {
			$request = ZoteroAPI::make_api_call('GET', 'users/'.$usertoken['userID'].'/keys/'.$usertoken['oauth_token'], $cloud, $consumer, $usertoken, array(), array());
			if (!empty($request)) {
				list($info, $headers, $body) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					$data = json_decode($body);
					return array(
						'service_name' => 'zotero',
						'service_auth' => true,
						'user_id'      => $usertoken['userID'],
						'user_name'    => $usertoken['username'],
						'user_email'   => '',
						'space_used'   => null,
						'space_amount' => null,
						'space_ratio'  => null,
					);
				} else {
					return array(
						'service_name' => 'zotero',
						'service_auth' => false,
						'user_id'      => null,
						'user_name'    => null,
						'user_email'   => null,
						'space_used'   => null,
						'space_amount' => null,
						'space_ratio'  => null,
					);
				}
			} else {
				return array(
					'service_name' => 'zotero',
					'service_auth' => false,
					'user_id'      => null,
					'user_name'    => null,
					'user_email'   => null,
					'space_used'   => null,
					'space_amount' => null,
					'space_ratio'  => null,
				);
			}
 		} else {
			throw new ConfigException('Can\'t find Zotero consumer key and/or consumer secret.');
		}
	}
	
	/*
	 * $folder_id   integer   ID of the collection (on Cloud Service), which items we wish to retrieve
	 * $output      array     Function returns formatted bibliography of the items in that collection
	 *
	 * SEE: https://www.zotero.com/developers/reference/api#metadata
	 *      get_filelist basically corresponds to get_formatted_list_of_references_in_collection!
	 */
	public function get_filelist($folder_id='0', $style='apa') {
		global $USER, $SESSION;
		$cloud     = self::cloud_info();
		$consumer  = self::consumer_tokens();
		$usertoken = self::user_tokens($USER->get('id'));
		$params    = array(
			'key'     => $usertoken['oauth_token'],
			'format'  => 'bib',
			//'content' => 'bib',
			'style' => $style
		);
		if (!empty($consumer['key']) && !empty($consumer['secret'])) {
			// Use different path for top items/collections
			// than for sub-items/sub-collections...
			if ($folder_id == '0') {
				$rootpath = '';
			} else {
				$rootpath = '/collections/'.$folder_id;
			}
			
			// Get all items in given collection...
			$request = ZoteroAPI::make_api_call('GET', 'users/'.$usertoken['userID'].$rootpath.'/items', $cloud, $consumer, $usertoken, $params, array());
			if (!empty($request) && strlen($folder_id) > 0) {
				//log_debug($request);
				list($info, $headers, $body) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					$bibliography = substr($body, strlen('<?xml version="1.0"?>'));
					return $bibliography;
				} else {
					$SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/zotero'));
				}
			} else {
				throw new ParameterException('Missing Zotero collection id');
			}
		} else {
			throw new ConfigException('Can\'t find Zotero consumer key and/or consumer secret.');
		}
	}
	
	/*
	 * This functiona is basically the same as above function 'get_folder', except
	 * it is intended to get collection contents and formats it, so it can be used
	 * in blocktype config form (Pieform element).
	 * That means there is less data about sub-collections and items of given
	 * collection and the checbox or radio form elements are added, to allow user
	 * to select single/multiple items and/or collections.
	 *
	 * $folder_id   integer   ID of the collection (on Cloud Service), which contents we wish to retrieve
	 * $options     integer   List of 5 integers (booleans) to indicate (for all 6 options) if option is used or not
	 * $block       integer   ID of the block in given Mahara view/page
	 * $fullpath    string    Fullpath to the collection (on Cloud Service), last opened by user
	 *
	 * $output      array     Function returns JSON encoded array of values that is suitable to feed jQuery Datatables with.
	                          jQuery Datatable than draw an enriched HTML table according to values, contained in $output.
	 * PLEASE NOTE: For jQuery Datatable to work, the $output array must be properly formatted and JSON encoded.
	 *              Please see: http://datatables.net/usage/server-side (Reply from the server)!
	 *
	 * SEE: https://www.zotero.com/developers/reference/api#metadata
	 */
	public function get_folder_content($folder_id='0', $options, $block=0, $fullpath='0|@') {
		global $USER, $THEME;

		// Get selected artefacts (folders and/or files)
		if ($block > 0) {
			$data = unserialize(get_field('block_instance', 'configdata', 'id', $block));
			if (!empty($data)) {
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
				$folder_id = '0';
			}
		} else {
			if ($folder_id != 'parent') {
				// Go to child folder...
				if (strlen($folder_id) > 1) {
					list($current, $path) = explode('|', $_SESSION[self::servicepath], 2);
					if (strcmp($current, $folder_id) != 0) {
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
		$params    = array(
			'key'     => $usertoken['oauth_token'],
			'format'  => 'atom',
			'content' => 'json'
		);
		if (!empty($consumer['key']) && !empty($consumer['secret'])) {
			// Use different path for top items/collections
			// than for sub-items/sub-collections...
			if ($folder_id == '0') {
				$rootpath = '';
			} else {
				$rootpath = '/collections/'.$folder_id;
			}

			// Get all sub-collections of  given collection.
			$request = ZoteroAPI::make_api_call('GET', 'users/'.$usertoken['userID'].$rootpath.'/collections', $cloud, $consumer, $usertoken, $params, array());
			if (!empty($request)) {
				//log_debug($request);
				list($info, $headers, $body) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					$xml = simplexml_load_string($body);
					//Use that namespace
					$namespaces = $xml->getNameSpaces(true);
					//Now we don't have the URL hard-coded
					$zapi = $xml->children($namespaces['zapi']);
					$total = $zapi->totalResults;

					$output = array();
					$count = 0;

					// Add 'parent' row entry to jQuery Datatable...
					if (strlen($_SESSION[self::servicepath]) > 3) {
						$type        = 'parentfolder';
						$foldername  = get_string('parentfolder', 'artefact.file');
						$title       = '<a class="changefolder" href="javascript:void(0)" id="parent" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '"><img src="' . get_config('wwwroot') . 'artefact/cloud/theme/raw/static/images/parentfolder.png"></a>';
						$output['aaData'][] = array('', $title, '', $type);
					}
					// or 'library items' (= items in the top level of Library) row entry to jQuery Datatable...
					else {
						$type  = 'parentfolder';
						$icon  = '<img src="' . $THEME->get_url('images/folder.gif') . '">';
						$title = '<span class="changefolder">'. get_string('allreferences', 'blocktype.cloud/zotero') . '</span>';
								if ($selectFolders) {
									$selected = (in_array('0', $artefacts) ? ' checked' : '');
									$controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="0"' . $selected . '>';
								} else {
									$controls = '';
								}
						$output['aaData'][] = array($icon, $title, $controls, $type);
					}

					for ($i=0; $i<$total; $i++) {
						if ($showFolders && isset($xml->entry[$i]->content) && !empty($xml->entry[$i]->content)) {
							$content = json_decode($xml->entry[$i]->content);
							$parent_id = $content->parent;
							if (!$parent_id) {
								$parent_id = '0';
							}
							if (isset($xml->entry[$i]->id) && $parent_id == $folder_id) {
								$id          = basename($xml->entry[$i]->id);
								$type        = 'folder';
								$icon        = '<img src="' . $THEME->get_url('images/folder.gif') . '">';
								$title       = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $content->name) . '">' . $content->name . '</a>';
								if ($selectFolders) {
									$selected = (in_array('folder-'.$id, $artefacts) ? ' checked' : '');
									$controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="folder-' . $id . '"' . $selected . '>';
								} else {
									$controls = '';
								}
								$output['aaData'][] = array($icon, $title, $controls, $type);
								$count++;
							} else { }
						}
					}
					$output['iTotalRecords'] = $count;
					$output['iTotalDisplayRecords'] = $count;
					
					return json_encode($output);
				}
			}
 		} else {
			throw new ConfigException('Can\'t find Zotero consumer key and/or consumer secret.');
		}
	}
	
	/*
	 * SEE: http://www.zotero.org/support/dev/server_api/read_api
	 *      get_folder_info basically corresponds to get_collection_info!
	 */
	public function get_folder_info($folder_id='0') {
		global $USER, $SESSION;
		$cloud     = self::cloud_info();
		$consumer  = self::consumer_tokens();
		$usertoken = self::user_tokens($USER->get('id'));
		$params    = array(
			'key'     => $usertoken['oauth_token'],
			'format'  => 'atom',
			'content' => 'json'
		);
		if (!empty($consumer['key']) && !empty($consumer['secret'])) {
			// Init collection title and data variables...
			$collectionTitle = null;
			$collectionData  = array();
			
			// Get collection title...
			$request = ZoteroAPI::make_api_call('GET', 'users/'.$usertoken['userID'].'/collections/'.$folder_id, $cloud, $consumer, $usertoken, $params, array());
			if (!empty($request) && strlen($folder_id) > 0) {
				//log_debug($request);
				list($info, $headers, $body) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					$xml = simplexml_load_string($body);
					$content = json_decode($xml->content);
					$collectionTitle = $content->name;
				} else {
					$SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/zotero'));
				}
			} else {
				throw new ParameterException('Missing Zotero collection id');
			}
			
			// Get collection data...
			$request = ZoteroAPI::make_api_call('GET', 'users/'.$usertoken['userID'].'/collections/'.$folder_id.'/items', $cloud, $consumer, $usertoken, $params, array());
			if (!empty($request) && strlen($folder_id) > 0) {
				//log_debug($request);
				list($info, $headers, $body) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					$xml = simplexml_load_string($body);
					//Use that namespace
					$namespaces = $xml->getNameSpaces(true);
					//Now we don't have the URL hard-coded
					$zapi = $xml->children($namespaces['zapi']);
					$total = $zapi->totalResults;

					for ($i=0; $i<$total; $i++) {
						$content  = json_decode($xml->entry[$i]->content);
						$creators = $content->creators;
						$author = '';
						// Count all the authors...
						$count = 0;
						foreach ($creators as $creator) {
							if ($creator->creatorType == 'author' || $creator->creatorType == 'contributor') $count++;
						}
						// Find first author...
						foreach ($creators as $creator) {
							if ($creator->creatorType == 'author' || $creator->creatorType == 'contributor') {
								if (!empty($creator->firstName) && !empty($creator->lastName)) {
									$author = $creator->lastName . ', ' . $creator->firstName;
								} elseif (!empty($creator->name)) {
									$author = $creator->name;
								}
								// Add suffix if there are more than 1 author
								if ($count > 1) {
									$author .= ' et al.';
								}
								break;
							}
						}

						$collectionData[] = array(
							'id' => basename($xml->entry[$i]->id),
							'title' => $content->title,
							'author' => $author,
						);
					}
					return array('title' => $collectionTitle, 'items' => $collectionData);
				} else {
					$SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/zotero'));
				}
			} else {
				throw new ParameterException('Missing Zotero collection id');
			}
		} else {
			throw new ConfigException('Can\'t find Zotero consumer key and/or consumer secret.');
		}
	}
	
	/*
	 * SEE: http://www.zotero.org/support/dev/server_api/read_api
	 *      get_file_info basically corresponds to get_item_info!
	 */
	public function get_file_info($file_id='0') {
		global $USER, $SESSION;
		$cloud     = self::cloud_info();
		$consumer  = self::consumer_tokens();
		$usertoken = self::user_tokens($USER->get('id'));
		$params    = array(
			'key'     => $usertoken['oauth_token'],
			'format'  => 'atom',
			'content' => 'json'
		);
		if (!empty($consumer['key']) && !empty($consumer['secret'])) {
			$request = ZoteroAPI::make_api_call('GET', 'users/'.$usertoken['userID'].'/items/'.$file_id, $cloud, $consumer, $usertoken, $params, array());
			if (!empty($request) && strlen($file_id) > 0) {
				//log_debug($request);
				list($info, $headers, $body) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					$xml = simplexml_load_string($body);
					$content = json_decode($xml->content);

					$creatorsList = array();
					foreach ($content->creators as $creator) {
						$creatorsList[] = array(
							'creatorType' => (isset($creator->creatorType) ? $creator->creatorType : 'author'),
							'firstName' => (isset($creator->firstName) ? $creator->firstName : ''),
							'lastName'  => (isset($creator->lastName) ? $creator->lastName : ''),
							'name'      => (isset($creator->name) ? $creator->name : '')
						);
					}
					$info = array(
						'id'           => basename($xml->id),
						'updated'      => format_date(strtotime($xml->updated), 'strfdaymonthyearshort'),
						'created'      => format_date(strtotime($xml->published), 'strfdaymonthyearshort'),
						'title'        => $content->title,
						'creators'     => $creatorsList,
						'type'         => $content->itemType,
						'abstract'     => (isset($content->abstractNote) ? $content->abstractNote : ''),
						'series'       => (isset($content->series) ? $content->series : ''),
						'seriesNumber' => (isset($content->seriesNumber) ? $content->seriesNumber : ''),
						'volume'       => (isset($content->volume) ? $content->volume : ''),
						'numVolumes'   => (isset($content->numberOfVolumes) ? $content->numberOfVolumes : ''),
						'publisher'    => (isset($content->publisher) ? $content->publisher : ''),
						'edition'      => (isset($content->edition) ? $content->edition : ''),
						'place'        => (isset($content->place) ? $content->place : ''),
						'date'         => (isset($content->date) ? format_date(strtotime($content->date), 'strfdaymonthyearshort') : ''),
						'numPages'     => (isset($content->numPages) ? $content->numPages : ''),
						'language'     => (isset($content->language) ? $content->language : ''),
						'ISBN'         => (isset($content->ISBN) ? $content->ISBN : ''),
						'ISSN'         => (isset($content->ISSN) ? $content->ISSN : ''),
						'url'          => (isset($content->url) ? $content->url : ''),
						'accessDate'   => (!empty($content->accessDate) ? format_date(strtotime($content->accessDate), 'strfdaymonthyearshort') : ''),
					);
					return $info;
				} else {
					$SESSION->add_error_msg(get_string('accesstokennotreturned', 'blocktype.cloud/zotero'));
				}
			} else {
				throw new ParameterException('Missing Zotero item id');
			}
		} else {
			throw new ConfigException('Can\'t find Zotero consumer key and/or consumer secret.');
		}
	}
	
	public function download_file($file_id='0') {
		// Zotero API doesn't support downloading of references, so:
		// Nothing to do!
	}
	
	public function embed_file($file_id='0', $options=array()) {
		// Zotero API doesn't support embedding of references, so:
		// Nothing to do!
	}

}

function get_zotero_bibstyles() {
    $styles = array(
        'american-anthropological-association',
        'apa5th',
        'apa',
        'chicago-author-date',
        'chicago-fullnote-bibliography',
        'chicago-note-bibliography',
        'elsevier-with-titles',
        'harvard1',
        'ieee',
        'iso690-author-date-en',
        'iso690-numeric-en',
        'mhra',
        'mla',
        'mla-url',
        'nature',
        'turabian-author-date',
        'turabian-fullnote-bibliography',
        'vancouver',
    );

    foreach ($styles as $s) {
        $bibstyles[$s] = get_string("style.{$s}", 'blocktype.cloud/zotero');
    };
    uasort($bibstyles, 'strcoll');
    return $bibstyles;
}

?>
