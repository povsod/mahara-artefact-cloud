<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-owncloud
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2014 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

$string['title'] = 'File(s) from ownCloud';
$string['description'] = 'Select files from ownCloud cloud';

$string['service'] = 'ownCloud'; // Same as plugin folder name, but can be CamelCase, e.g.: SkyDrive
$string['servicename'] = 'ownCloud'; // Full service name, e.g.: Windows Live SkyDrive

$string['owncloudgeneral'] = 'ownCloud Settings';
$string['servicetitle'] = 'Service title';
$string['servicetitledesc'] = 'Enter the title of your ownCloud service in case you have renamed it, otherwise leave this empty.';
$string['webdavurl'] = 'WebDAV URL';
$string['webdavurldesc'] = 'Enter WebDAV URL to allow access to your ownCloud service. It should be something like <i>https://my.owncloud.com/remote.php/webdav/</i>';

$string['AAIlogin'] = 'If you are accessing ownCloud via AAI login, then you must %sset password%s since WebDAV is not accessible via AAI login. Then enter your username and password in the form below.';

$string['selectfiles'] = 'Select files';
$string['revokeconnection'] = 'Revoke connection to ownCloud';
$string['connecttodropowncloud'] = 'Connect to ownCloud';

?>
