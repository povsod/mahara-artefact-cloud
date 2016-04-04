<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-dropbox
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2015 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

$string['title'] = 'File(s) from Dropbox';
$string['description'] = 'Select files from Dropbox cloud';

$string['service'] = 'Dropbox'; // Same as plugin folder name, but can be CamelCase, e.g.: SkyDrive
$string['servicename'] = 'Dropbox'; // Full service name, e.g.: WIndows Live SkyDrive

$string['requesttokennotreturned'] = 'There was no request token';
$string['accesstokennotreturned'] = 'There was no access token';
$string['accesstokensaved'] = 'Access token saved sucessfully';
$string['accesstokensavefailed'] = 'Failed to save access token';

$string['applicationgeneral'] = 'General information';
$string['applicationdesc'] = 'You must create %san application%s, if you wish to access and use Dropbox API.';
$string['applicationname'] = 'App name';
$string['applicationnamedesc'] = 'You must provide unique app name, e.g. the name of this site.';
$string['consumerkey'] = 'App key';
$string['consumerkeydesc'] = 'When you\'ll create an app, you\'ll get an App key. Paste it here.';
$string['consumersecret'] = 'App secret';
$string['consumersecretdesc'] = 'When you\'ll create an app, you\'ll get an App secret. Paste it here.';
$string['redirecturl'] = 'Redirect URI';
$string['redirecturldesc'] = 'URL to return user to, after successful authentication. Copy it and paste it to the list of OAuth redirect URIs.';

$string['applicationadditional'] = 'Additional information';
$string['applicationweb'] = 'Website';
$string['applicationwebdesc'] = 'The URL where this app will be used, e.g. URL of your site.';
$string['applicationicon'] = 'App icon';
$string['applicationicondesc'] = 'You can upload icons for your app in following sizes: 16x16, 64x64 and/or 128x128.';

$string['selectfiles'] = 'Select files';
$string['revokeconnection'] = 'Revoke connection to Dropbox';
$string['connecttodropbox'] = 'Connect to Dropbox';

?>
