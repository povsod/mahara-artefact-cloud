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

defined('INTERNAL') || die();

$string['title'] = 'File(s) from SugarSync';
$string['description'] = 'Select files from SugarSync cloud';

$string['service'] = 'SugarSync'; // Same as plugin folder name, but can be CamelCase, e.g.: SkyDrive
$string['servicename'] = 'SugarSync'; // Full service name, e.g.: Windows Live SkyDrive

$string['refreshtokennotreturned'] = 'There was no refresh token';
$string['accesstokennotreturned'] = 'There was no access token';
$string['accesstokensaved'] = 'Access token saved sucessfully';
$string['accesstokensavefailed'] = 'Failed to save access token';
$string['servererror'] = 'There was server error when downloading a file';

$string['applicationgeneral'] = 'Application information';
$string['applicationdesc'] = 'You must create %san application%s, if you wish to access and use SugarSync API.';
$string['applicationname'] = 'App name';
$string['applicationnamedesc'] = 'You must provide unique application name, e.g. the name of this site.';
$string['applicationid'] = 'App ID';
$string['applicationiddesc'] = 'When you\'ll create an app, you\'ll get an App key. Paste it here.';

$string['applicationbackend'] = 'Backend parameters';
$string['consumerkey'] = 'App key';
$string['consumerkeydesc'] = 'When you\'ll create an app, you\'ll get an App key. Paste it here.';
$string['consumersecret'] = 'App secret';
$string['consumersecretdesc'] = 'When you\'ll create an app, you\'ll get an App secret. Paste it here.';
$string['redirecturl'] = 'Redirect URL';
$string['redirecturldesc'] = 'URL to return user to, after successful authentication. Copy it and paste it to app settings.';
$string['applicationicon'] = 'App icon';
$string['applicationicondesc'] = 'You can upload favicon (16x16) and logo (100x80) for your app.';

$string['selectfiles'] = 'Select files';
$string['revokeconnection'] = 'Revoke connection to SugarSync';
$string['connecttodropsugarsync'] = 'Connect to SugarSync';

?>
