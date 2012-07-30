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
 * @subpackage blocktype-box
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

safe_require('artefact', 'cloud');
require_once(get_config('docroot') . 'artefact/cloud/lib/http-request.php');
require_once('lib/api.php'); // Box API methods


class PluginBlocktypeBox extends PluginBlocktypeCloud {

	const servicepath = 'boxpath';
	
    public static function get_title() {
        return get_string('title', 'blocktype.cloud/box');
    }

    public static function get_description() {
        return get_string('description', 'blocktype.cloud/box');
    }

    public static function get_categories() {
        return array('external');
    }

	public static function get_instance_config_javascript() {
        return array('js/configform.js');
    }

    public static function render_instance(BlockInstance $instance, $editing=false) {
        $configdata = $instance->get('configdata');
        $viewid     = $instance->get('view');
		
		$fullpath = (!empty($configdata['fullpath']) ? $configdata['fullpath'] : '0|@');
		$selected = (!empty($configdata['artefacts']) ? $configdata['artefacts'] : array());
		$display  = (!empty($configdata['display']) ? $configdata['display'] : 'list');
		$width    = (!empty($configdata['width']) ? $configdata['width'] : 466);
		$height   = (!empty($configdata['height']) ? $configdata['height'] : 400);
		$allowed  = (!empty($configdata['allowed']) ? $configdata['allowed'] : array());
		
        $smarty = smarty_core();
		switch ($display) {
			case 'embed':
				$html = '';
				$options = array(
					'width'          => $width,
					'height'         => $height,
					'allow_download' => (in_array('download', $allowed) ? 1 : 0),
					'allow_print'    => (in_array('print', $allowed) ? 1 : 0),
					'allow_share'    => (in_array('share', $allowed) ? 1 : 0)
				);
				if (!empty($selected)) {
					foreach ($selected as $artefact) {
						list($type, $id) = explode('-', $artefact);
						$html .= self::embed_file($id, $options);
					}
				}
				$smarty->assign('embed', $html);
				break;
			case 'list':
			default:
				list($folder, $path) = explode('|', $fullpath, 2);
				$data = self::get_filelist($folder, $selected);
				$smarty->assign('folders', $data['folders']);
				$smarty->assign('files', $data['files']);
		}
		$smarty->assign('viewid', $viewid);
        return $smarty->fetch('blocktype:box:' . $display . '.tpl');
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
		
		return array(
			'boxfiles' => array(
				'type'     => 'datatables',
				'title'    => get_string('selectfiles','blocktype.cloud/box'),
				'service'  => 'box',
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
				'collapsible'  => true,
				'collapsed'    => true,
				'legend'       => get_string('embedoptions', 'blocktype.cloud/box'),
				'elements'     => array(
					'width' => array(
						'type'  => 'text',
						'labelhtml' => get_string('width', 'blocktype.cloud/box'),
						'size' => 3,
						'defaultvalue' => (!empty($configdata['width']) ? hsc($configdata['width']) : 466),
						'rules' => array('minvalue' => 1, 'maxvalue' => 2000),
					),
					'height' => array(
						'type'  => 'text',
						'labelhtml' => get_string('height', 'blocktype.cloud/box'),
						'size' => 3,
						'defaultvalue' => (!empty($configdata['height']) ? hsc($configdata['height']) : 400),
						'rules' => array('minvalue' => 1, 'maxvalue' => 2000),
					),
					'allowed' => array(
						'type'  => 'checkboxes',
						'labelhtml' => get_string('allow', 'blocktype.cloud/box'),
						'elements' => array(
							array(
								'value' => 'download',
								'title' => get_string('allowdownload', 'blocktype.cloud/box'),
								'defaultvalue' => (in_array('download', $allowed) ? 'checked' : '')
							),
							array(
								'value' => 'print',
								'title' => get_string('allowprint', 'blocktype.cloud/box'),
								'defaultvalue' => (in_array('print', $allowed) ? 'checked' : '')
							),
							array(
								'value' => 'share',
								'title' => get_string('allowshare', 'blocktype.cloud/box'),
								'defaultvalue' => (in_array('share', $allowed) ? 'checked' : '')
							),
						),
					),
				),
			),
		);
    }

    public static function instance_config_save($values) {
		global $_SESSION;
		// Folder and file IDs (and other values) are returned as JSON/jQuery serialized string.
		// We have to parse that string and urldecode it (to correctly convert square brackets)
		// in order to get cloud folder and file IDs - they are stored in $artefacts array.
		parse_str(urldecode($values['boxfiles']));
		if (!isset($artefacts) || empty($artefacts)) {
			$artefacts = array();
		}
		
		$values = array(
			'title'     => $values['title'],
			'fullpath'  => $_SESSION[self::servicepath],
			'artefacts' => $artefacts,
			'display'   => $values['display'],
			'width'     => $values['width'],
			'height'    => $values['height'],
			'allowed'   => $values['allowed'],
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
			'value' => get_string('applicationdesc', 'blocktype.cloud/box', '<a href="https://www.box.com/developers/services" target="_blank">', '</a>'),
		);
        $elements['applicationgeneral'] = array(
			'type' => 'fieldset',
			'legend' => get_string('applicationgeneral', 'blocktype.cloud/box'),
			'elements' => array(
				'applicationname' => array(
					'type'         => 'text',
					'title'        => get_string('applicationname', 'blocktype.cloud/box'),
					'defaultvalue' => get_config('sitename'),
					'description'  => get_string('applicationnamedesc', 'blocktype.cloud/box'),
					'readonly'     => true,
				),
				'applicationweb' => array(
					'type'         => 'text',
					'title'        => get_string('applicationweb', 'blocktype.cloud/box'),
					'defaultvalue' => get_config('wwwroot'),
					'description'  => get_string('applicationwebdesc', 'blocktype.cloud/box'),
					'size'         => 68,
					'readonly'     => true,
				),
			)
		);
        $elements['applicationbackend'] = array(
			'type' => 'fieldset',
			'legend' => get_string('applicationbackend', 'blocktype.cloud/box'),
			'elements' => array(
				'consumerkey' => array(
					'type'         => 'text',
					'title'        => get_string('consumerkey', 'blocktype.cloud/box'),
					'defaultvalue' => get_config_plugin('blocktype', 'box', 'consumerkey'),
					'description'  => get_string('consumerkeydesc', 'blocktype.cloud/box'),
					'size' => 40,
					'rules' => array('required' => true),
				),
				/*
				'consumersecret' => array(
					'type'         => 'text',
					'title'        => get_string('consumersecret', 'blocktype.cloud/box'),
					'defaultvalue' => get_config_plugin('blocktype', 'box', 'consumersecret'),
					'description'  => get_string('consumersecretdesc', 'blocktype.cloud/box'),
					'size' => 40,
					'rules' => array('required' => true),
				),
				*/
				'redirecturl' => array(
					'type'         => 'text',
					'title'        => get_string('redirecturl', 'blocktype.cloud/box'),
					'defaultvalue' => get_config('wwwroot') . 'artefact/cloud/blocktype/box/callback.php',
					'description'  => get_string('redirecturldesc', 'blocktype.cloud/box'),
					'size'         => 70,
					'readonly'     => true,
					'rules' => array('required' => true),
				),
				'applicationicon' => array(
					'type'         => 'html',
					'title'        => get_string('applicationicon', 'blocktype.cloud/box'),
					'value'        => '<table border="0"><tr style="text-align:center">
					                   <td style="vertical-align:bottom"><img src="'.get_config('wwwroot').'artefact/cloud/icons/016x016.jpg" border="0" style="border:1px solid #ccc"><br>16x16</td>
					                   <td style="vertical-align:bottom"><img src="'.get_config('wwwroot').'artefact/cloud/icons/100x080.jpg" border="0" style="border:1px solid #ccc"><br>100x80</td>
									   </table>',
					'description'  => get_string('applicationicondesc', 'blocktype.cloud/box'),
				),
			)
		);
        return array(
            'elements' => $elements,
        );

    }

    public static function save_config_options($values) {
        set_config_plugin('blocktype', 'box', 'consumerkey', $values['consumerkey']);
        //set_config_plugin('blocktype', 'box', 'consumersecret', $values['consumersecret']);
    }

    public static function default_copy_type() {
        return 'shallow';
    }

	/*****************************************
	 * Methods & stuff for accessing Box API *
	 *****************************************/
	
	public function cloud_info() {
		return array(
			'ssl'        => true,
			'version'    => '1.0',
			'baseurl'    => 'https://www.box.net/api/',
			'contenturl' => 'https://upload.box.net/api/',
			//'wwwurl'     => 'https://www.box.net/api/',
		);
	}
	
	public function consumer_tokens() {
		return array(
			'key'      => get_config_plugin('blocktype', 'box', 'consumerkey'),
			//'secret'   => get_config_plugin('blocktype', 'box', 'consumersecret'),
			'callback' => get_config('wwwroot') . 'artefact/cloud/blocktype/box/callback.php'
		);
	}
	
	public function user_tokens($userid) {
		return ArtefactTypeCloud::get_user_preferences('box', $userid);
	}
	
	public function service_list() {
		global $USER;
		$consumer   = self::consumer_tokens();
		$usertoken  = self::user_tokens($USER->get('id'));
		if (!empty($consumer['key'])) {
			if (isset($usertoken['auth_token']) && !empty($usertoken['auth_token'])) {
				return array(
					'service_name'   => 'box',
					'service_url'    => 'http://www.box.com',
					'service_auth'   => true,
					'service_manage' => true,
					//'revoke_access'  => true,
				);
			} else {
				return array(
					'service_name'   => 'box',
					'service_url'    => 'http://www.box.com',
					'service_auth'   => false,
					'service_manage' => false,
					//'revoke_access'  => false,
				);
			}
		} else {
			throw new ConfigException('Can\'t find Box consumer key.');
		}
	}
	
	public function request_token() {
		global $USER;
		$cloud    = self::cloud_info();
		$consumer = self::consumer_tokens();
		if (!empty($consumer['key'])) {
			$request = BoxAPI::get_ticket('POST', $cloud, $consumer);
			if (!empty($request)) {
			
				list($info, $headers, $body, $body_parsed) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					// Get ticket and finnish authentication process
					$ticket = $body_parsed['ticket'];
					redirect($cloud['baseurl'].$cloud['version'].'/auth/'.$ticket);
				} else {
					$SESSION->add_error_msg(get_string('ticketnotreturned', 'blocktype.cloud/box'));
				}
			}
		} else {
			throw new ConfigException('Can\'t find Box consumer key.');
		}
	}

	public function access_token($params) {
		// Web applications don't need to implement this.
		// This is only needed for desktop applications.
	}

	public function delete_token() {
		global $USER;
		ArtefactTypeCloud::set_user_preferences('box', $USER->get('id'), null);
	}
	
	/*
	 * SEE: http://developers.box.net/w/page/12923939/ApiFunction_logout
	 */
	public function revoke_access() {
		global $USER;
		$cloud     = self::cloud_info();
		$consumer  = self::consumer_tokens();
		$usertoken = self::user_tokens($USER->get('id'));
		if (!empty($consumer['key'])) {
			// Programmatially logout (revoke access) user
			$request = BoxAPI::make_api_call('POST', 'logout', $cloud, $consumer, $usertoken, array(), array());
		} else {
			throw new ConfigException('Can\'t find Box consumer key.');
		}
	}
	
	/*
	 * SEE: http://developers.box.net/w/page/12923928/ApiFunction_get_account_info
	 * SEE: http://developers.box.net/w/page/42307455/ApiFunction_get_user_info
	 */
	public function account_info() {
		global $USER;
		$cloud     = self::cloud_info();
		$consumer  = self::consumer_tokens();
		$usertoken = self::user_tokens($USER->get('id'));
		if (!empty($consumer['key'])) {
			$request = BoxAPI::make_api_call('POST', 'get_account_info', $cloud, $consumer, $usertoken, array(), array());
			if (!empty($request)) {
				list($info, $headers, $body) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					$data = oauth_parse_xml($body);
					//log_debug($data);
					$request2 = BoxAPI::make_api_call('POST', 'get_user_info', $cloud, $consumer, $usertoken, array('user_id' => $data['user']['user_id']), array());
					$user_name  = '';
					$user_email = '';
					if (!empty($request2)) {
						list($info, $headers, $body) = $request2;
						if ($info['http_code'] == 200 && !empty($body)) {
							$user = oauth_parse_xml($body);
							//log_debug($user);
							$user_name  = $user['user_name'];
							$user_email = $user['user_email'];
						}
					}
					//$data = json_decode($body);
					return array(
						'service_name' => 'box',
						'service_auth' => true,
						'user_id'      => $data['user']['user_id'],
						'user_name'    => $user_name,
						'user_email'   => $user_email,
						'space_used'   => bytes_to_size1024(floatval($data['user']['space_used'])),
						'space_amount' => bytes_to_size1024(floatval($data['user']['space_amount'])),
						'space_ratio'  => number_format((floatval($data['user']['space_used'])/floatval($data['user']['space_amount']))*100, 2),
					);
				} else {
					return array(
						'service_name' => 'box',
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
					'service_name' => 'box',
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
			throw new ConfigException('Can\'t find Box consumer key.');
		}
	}
	
	/*
	 * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
	 * $output      array     Function returns array, used to generate list of files/folders to show in Mahara view/page
	 *
	 * SEE: http://developers.box.net/w/page/12923929/ApiFunction_get_account_tree
	 *
	 */
	public function get_filelist($folder_id=0, $selected=array()) {
		global $USER, $THEME;

		// Get folder contents...
		$cloud     = self::cloud_info();
		$consumer  = self::consumer_tokens();
		$usertoken = self::user_tokens($USER->get('id'));
		$params    = array('folder_id' => $folder_id);
		// Parameter 'nozip' is absolutely crucial!
		// Without it, the response is base64-like encoded, but base64_decode won't work!
		//$options   = array('nozip', 'onelevel', 'simple');
		$options   = array('nozip', 'onelevel');
		if (!empty($consumer['key'])) {
			$request = BoxAPI::make_api_call('GET', 'get_account_tree', $cloud, $consumer, $usertoken, $params, $options);
			if (!empty($request)) {
				list($info, $headers, $body) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					$data = oauth_parse_xml($body);
					//log_debug($data['tree']);
					$output = array(
						'folders' => array(),
						'files'   => array()
					);
					if (isset($data['tree']['folder']['folders']) && !empty($data['tree']['folder']['folders'])) {
						$folders = $data['tree']['folder']['folders']['folder'];
						foreach ($folders as $folder) {
							if (isset($folder['@attributes']) && isset($folder['@attributes']['id'])) {
								$id = $folder['@attributes']['id'];
								if (in_array('folder-'.$id, $selected)) {
									//$type        = 'folder';
									$icon        = $THEME->get_url('images/folder.gif');
									$title       = $folder['@attributes']['name'];
									$description = (isset($folder['@attributes']['description']) ? $folder['@attributes']['description'] : '');
									$size        = bytes_to_size1024($folder['@attributes']['size']);
									$created     = format_date($folder['@attributes']['created'], 'strftimedaydate');
									$output['folders'][] = array('iconsrc' => $icon, 'id' => $id, 'title' => $title, 'description' => $description, 'size' => $size, 'ctime' => $created);
								}
							} elseif (isset($folder['id'])) {
								$id = $folder['id'];
								if (in_array('folder-'.$id, $selected)) {
									//$type        = 'folder';
									$icon        = $THEME->get_url('images/folder.gif');
									$title       = $folder['name'];
									$description = (isset($folder['description']) ? $folder['description'] : '');
									$size        = bytes_to_size1024($folder['size']);
									$created     = format_date($folder['created'], 'strftimedaydate');
									$output['folders'][] = array('iconsrc' => $icon, 'id' => $id, 'title' => $title, 'description' => $description, 'size' => $size, 'ctime' => $created);
								}
							} else { }
						}
					}
					if (isset($data['tree']['folder']['files']) && !empty($data['tree']['folder']['files'])) {
						$files = $data['tree']['folder']['files']['file'];
						foreach ($files as $file) {
							if (isset($file['@attributes']) && isset($file['@attributes']['id'])) {
								$id = $file['@attributes']['id'];
								if (in_array('file-'.$id, $selected)) {
									//$type        = 'file';
									$icon        = $THEME->get_url('images/file.gif');
									$title       = $file['@attributes']['file_name'];
									$description = (isset($file['@attributes']['description']) ? $file['@attributes']['description'] : '');
									$size        = bytes_to_size1024($file['@attributes']['size']);
									$created     = format_date($file['@attributes']['created'], 'strftimedaydate');
									$output['files'][] = array('iconsrc' => $icon, 'id' => $id, 'title' => $title, 'description' => $description, 'size' => $size, 'ctime' => $created);
								}
							} elseif (isset($file['id'])) {
								$id = $file['id'];
								if (in_array('file-'.$id, $selected)) {
									//$type        = 'file';
									$icon        = $THEME->get_url('images/file.gif');
									$title       = $file['file_name'];
									$description = (isset($file['description']) ? $file['description'] : '');
									$size        = bytes_to_size1024($file['size']);
									$created     = format_date($file['created'], 'strftimedaydate');
									$output['files'][] = array('iconsrc' => $icon, 'id' => $id, 'title' => $title, 'description' => $description, 'size' => $size, 'ctime' => $created);
								}
							} else { }
						}
					}
					
					return $output;
				}
			} else {
				return array();
			}
 		} else {
			throw new ConfigException('Can\'t find Box consumer key.');
		}
	}

	/*
	 * This functiona is basically the same as above function 'get_folder', except
	 * it is intended to get folder contents and formats it, so it can be used in
	 * blocktype config form (Pieform element).
	 * That means there is less data about sub-folders and files of given folder
	 * and the checbox or radio form elements are added, to allow user to select
	 * single/multiple files and/or folders.
	 *
	 * $folder_id   integer   ID of the folder (on Cloud Service), which contents we wish to retrieve
	 * $options     integer   List of 6 integers (booleans) to indicate (for all 6 options) if option is used or not
	 * $block       integer   ID of the block in given Mahara view/page
	 * $fullpath    string    Fullpath to the folder (on Cloud Service), last opened by user
	 *
	 * $output      array     Function returns JSON encoded array of values that is suitable to feed jQuery Datatables with.
	                          jQuery Datatable than draw an enriched HTML table according to values, contained in $output.
	 * PLEASE NOTE: For jQuery Datatable to work, the $output array must be properly formatted and JSON encoded.
	 *              Please see: http://datatables.net/usage/server-side (Reply from the server)!
	 *
	 * SEE: http://developers.box.net/w/page/12923929/ApiFunction_get_account_tree
	 *
	 */
	public function get_folder_content($folder_id=0, $options, $block=0, $fullpath='0|@') {
		global $USER, $THEME, $_SESSION;
		
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

		// Set/get return path...
		if ($folder_id == 'init') {
			if (strlen($fullpath) > 3) {
				list($current, $path) = explode('|', $fullpath, 2);
				$_SESSION[self::servicepath] = $current . '|' . $path;
				$folder_id = $current;
			} else {
				// Full path equals path to root folder
				$_SESSION[self::servicepath] = '0|@';
				$folder_id = 0;
			}
		} else {
			if ($folder_id != 'parent') {
				// Go to child folder...
				if (intval($folder_id) > 0) {
					list($current, $path) = explode('|', $_SESSION[self::servicepath], 2);
					if (intval($current) != intval($folder_id)) {
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
		$params    = array('folder_id' => $folder_id);
		// Parameter 'nozip' is absolutely crucial!
		// Without it, the response is base64-like encoded, but base64_decode won't work!
		//$options   = array('nozip', 'onelevel', 'simple');
		$options   = array('nozip', 'onelevel');
		if (!empty($consumer['key'])) {
			$request = BoxAPI::make_api_call('GET', 'get_account_tree', $cloud, $consumer, $usertoken, $params, $options);
			if (!empty($request)) {
				list($info, $headers, $body) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					$data = oauth_parse_xml($body);
					//log_debug($data['tree']);
					$output = array();
					$count = 0;
					// Add 'parent' row entry to jQuery Datatable...
					if (strlen($_SESSION[self::servicepath]) > 3) {
						$type        = 'parentfolder';
						$foldername  = get_string('parentfolder', 'artefact.file');
						$title       = '<a class="changefolder" href="javascript:void(0)" id="parent" title="' . get_string('gotofolder', 'artefact.file', $foldername) . '"><img src="' . get_config('wwwroot') . 'artefact/cloud/theme/raw/static/images/parentfolder.png"></a>';
						$output['aaData'][] = array('', $title, '', $type);
					}
					if ($showFolders && isset($data['tree']['folder']['folders']) && !empty($data['tree']['folder']['folders'])) {
						$folders = $data['tree']['folder']['folders']['folder'];
						foreach ($folders as $folder) {
							if (isset($folder['@attributes']) && isset($folder['@attributes']['id'])) {
								$id          = $folder['@attributes']['id'];
								$type        = 'folder';
								$icon        = '<img src="' . $THEME->get_url('images/folder.gif') . '">';
								$title       = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $folder['@attributes']['name']) . '">' . $folder['@attributes']['name'] . '</a>';
								if ($selectFolders) {
									$selected = (in_array('folder-'.$id, $artefacts) ? ' checked' : '');
									$controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="folder-' . $id . '"' . $selected . '>';
								} else {
									$controls = '';
								}
								$output['aaData'][] = array($icon, $title, $controls, $type);
								$count++;
							} elseif (isset($folder['id'])) {
								$id          = $folder['id'];
								$type        = 'folder';
								$icon        = '<img src="' . $THEME->get_url('images/folder.gif') . '">';
								$title       = '<a class="changefolder" href="javascript:void(0)" id="' . $id . '" title="' . get_string('gotofolder', 'artefact.file', $folder['name']) . '">' . $folder['name'] . '</a>';
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
					if ($showFiles && isset($data['tree']['folder']['files']) && !empty($data['tree']['folder']['files'])) {
						$files = $data['tree']['folder']['files']['file'];
						foreach ($files as $file) {
							if (isset($file['@attributes']) && isset($file['@attributes']['id'])) {
								$id          = $file['@attributes']['id'];
								$type        = 'file';
								$icon        = '<img src="' . $THEME->get_url('images/file.gif') . '">';
								$title       = '<a class="filedetails" href="details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $file['@attributes']['file_name']) . '">' . $file['@attributes']['file_name'] . '</a>';
								if ($selectFiles && !$manageButtons) {
									$selected = (in_array('file-'.$id, $artefacts) ? ' checked' : '');
									$controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="file-' . $id . '"' . $selected . '>';
								} elseif ($manageButtons) {
									$controls  = '<a class="btn" href="download.php?id=' . $id . '&save=1">' . get_string('save', 'artefact.cloud') . '</a>';
									$controls .= '<a class="btn" href="download.php?id=' . $id . '">' . get_string('download', 'artefact.cloud') . '</a>';
								} else {
									$controls = '';
								}
								$output['aaData'][] = array($icon, $title, $controls, $type);
								$count++;
							} elseif (isset($file['id'])) {
								$id          = $file['id'];
								$type        = 'file';
								$icon        = '<img src="' . $THEME->get_url('images/file.gif') . '">';
								$title       = '<a class="filedetails" href="details.php?id=' . $id . '" title="' . get_string('filedetails', 'artefact.cloud', $file['@attributes']['file_name']) . '">' . $file['@attributes']['file_name'] . '</a>';
								if ($selectFiles && !$manageButtons) {
									$selected = (in_array('file-'.$id, $artefacts) ? ' checked' : '');
									$controls = '<input type="' . ($selectMultiple ? 'checkbox' : 'radio') . '" name="artefacts[]" id="artefacts[]" value="file-' . $id . '"' . $selected . '>';
								} elseif ($manageButtons) {
									$controls  = '<a class="btn" href="download.php?id=' . $id . '&save=1">' . get_string('save', 'artefact.cloud') . '</a>';
									$controls .= '<a class="btn" href="download.php?id=' . $id . '">' . get_string('download', 'artefact.cloud') . '</a>';
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
			} else {
				return array();
			}
 		} else {
			throw new ConfigException('Can\'t find Box consumer key.');
		}
	}

	/*
	 * SEE: http://developers.box.net/w/page/12923929/ApiFunction_get_account_tree
	 */
	public function get_folder_info($folder_id=0) {
		global $USER;
		$cloud     = self::cloud_info();
		$consumer  = self::consumer_tokens();
		$usertoken = self::user_tokens($USER->get('id'));
		$params    = array('folder_id' => $folder_id);
		// Parameter 'nozip' is absolutely crucial!
		// Without it, the response is base64-like encoded, but base64_decode won't work!
		$options   = array('nozip', 'onelevel', 'nofiles');
		if (!empty($consumer['key'])) {
			$request = BoxAPI::make_api_call('GET', 'get_account_tree', $cloud, $consumer, $usertoken, $params, $options);
			if (!empty($request)) {
				list($info, $headers, $body) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					$data = oauth_parse_xml($body);
					//log_debug($data['tree']);
					if (isset($data['status']) && $data['status'] == 'listing_ok') {
						if (isset($data['tree']['folder']['@attributes']) && isset($data['tree']['folder']['@attributes']['id'])) {
							$info = array(
								'id'          => $data['tree']['folder']['@attributes']['id'],
								'name'        => $data['tree']['folder']['@attributes']['name'],
								'shared'      => $data['tree']['folder']['@attributes']['shared'],
								'description' => $data['tree']['folder']['@attributes']['description'],
								'created'     => format_date($data['tree']['folder']['@attributes']['created'], 'strfdaymonthyearshort'),
								'updated'     => format_date($data['tree']['folder']['@attributes']['updated'], 'strfdaymonthyearshort'),
							);
						} elseif (isset($data['tree']['folder']['id'])) {
							$info = array(
								'id'          => $data['tree']['folder']['id'],
								'name'        => $data['tree']['folder']['name'],
								'shared'      => $data['tree']['folder']['shared'],
								'description' => $data['tree']['folder']['description'],
								'created'     => format_date($data['tree']['folder']['created'], 'strfdaymonthyearshort'),
								'updated'     => format_date($data['tree']['folder']['updated'], 'strfdaymonthyearshort'),
							);
						} else {
							$info = array();
						}
						return $info;
					} else {
						throw new AccessDeniedException(get_string('folderaccessdenied', 'artefact.cloud'));
					}
				}
			} else {
				return null;
			}
 		} else {
			throw new ConfigException('Can\'t find Box consumer key.');
		}
	}

	/*
	 * SEE: http://developers.box.net/w/page/12923934/ApiFunction_get_file_info
	 */
	public function get_file_info($file_id=0) {
		global $USER;
		$cloud     = self::cloud_info();
		$consumer  = self::consumer_tokens();
		$usertoken = self::user_tokens($USER->get('id'));
		$params    = array('file_id' => $file_id);
		if (!empty($consumer['key'])) {
			$request = BoxAPI::make_api_call('GET', 'get_file_info', $cloud, $consumer, $usertoken, $params);
			if (!empty($request)) {
				list($info, $headers, $body) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					$data = oauth_parse_xml($body);
					//log_debug($data['info']);
					if (isset($data['status']) && $data['status'] == 's_get_file_info') {
						$info = array(
							'id'          => $data['info']['file_id'],
							'name'        => $data['info']['file_name'],
							'bytes'       => $data['info']['size'],
							'size'        => bytes_to_size1024($data['info']['size']),
							'shared'      => $data['info']['shared'],
							'description' => $data['info']['description'],
							'created'     => format_date($data['info']['created'], 'strfdaymonthyearshort'),
							'updated'     => format_date($data['info']['updated'], 'strfdaymonthyearshort'),
						);
						return $info;
					} else {
						throw new AccessDeniedException(get_string('fileaccessdenied', 'artefact.cloud'));
					}
				}
			} else {
				return null;
			}
 		} else {
			throw new ConfigException('Can\'t find Box consumer key.');
		}
	}

	/*
	 * SEE: http://developers.box.net/w/page/12923951/ApiFunction_Upload%20and%20Download
	 */
	public function download_file($file_id=0) {
		global $USER;
		$cloud     = self::cloud_info();
		$usertoken = self::user_tokens($USER->get('id'));

		// Construct download, to download file...
		// e.g.: https://www.box.net/api/1.0/download/<auth_token>/<file_id>
		$download_url = $cloud['baseurl'] . $cloud['version'] . '/download/' . $usertoken['auth_token'] . '/' . $file_id;
		
		$result = '';
		if ($file_id > 0) {
			$ch = curl_init($download_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
			$result = curl_exec($ch);
			curl_close($ch);
		}
		return $result;
	}

	/*
	 * SEE: http://developers.box.net/w/page/50509454/create_file_embed
	 */
	public function embed_file($file_id=0, $options=array()) {
		global $USER;
		$cloud     = self::cloud_info();
		$consumer  = self::consumer_tokens();
		$usertoken = self::user_tokens($USER->get('id'));
		$params    = array('file_id' => $file_id);
		if (!empty($consumer['key'])) {
			//  File must be flagged as shared before it can be embedded, so make it public...
			self::make_public('file', $file_id);
			$request = BoxAPI::make_api_call('GET', 'create_file_embed', $cloud, $consumer, $usertoken, $params, $options);
			if (!empty($request)) {
				list($info, $headers, $body) = $request;
				if ($info['http_code'] == 200 && !empty($body)) {
					$data = oauth_parse_xml($body);
					//log_debug($data);
					if (isset($data['status']) && $data['status'] == 's_create_file_embed') {
						$html = $data['file_embed_html'];
					} else {
						$html = '';
					}
					return $html;
				}
			} else {
				return null;
			}
 		} else {
			throw new ConfigException('Can\'t find Box consumer key.');
		}
	}

	/*
	 * IMPORTANT: Used by embed_file method above!
	 *
	 * SEE: http://developers.box.net/w/page/12923943/ApiFunction_public_share
	 */
	public function make_public($target, $target_id) {
		global $USER;
		$cloud     = self::cloud_info();
		$consumer  = self::consumer_tokens();
		$usertoken = self::user_tokens($USER->get('id'));
		$params    = array('target' => $target, 'target_id' => $target_id, 'password' => null, 'message' => null, 'emails' => null);
		$options   = array();
		if (!empty($consumer['key'])) {
			$request = BoxAPI::make_api_call('POST', 'public_share', $cloud, $consumer, $usertoken, $params, $options);
 		} else {
			throw new ConfigException('Can\'t find Box consumer key.');
		}
	}

}

?>
