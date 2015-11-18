<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-box
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2015 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

$string['title'] = 'File(s) from Box';
$string['description'] = 'Select files from Box cloud';

$string['service'] = 'Box'; // Same as plugin folder name, but can be CamelCase, e.g.: SkyDrive
$string['servicename'] = 'Box'; // Full service name, e.g.: Windows Live SkyDrive

$string['tickernotreturned'] = 'There was no ticket';
$string['accesstokennotreturned'] = 'There was no access token';
$string['accesstokensaved'] = 'Access token saved sucessfully';
$string['accesstokensavefailed'] = 'Failed to save access token';

$string['applicationgeneral'] = 'General information';
$string['applicationdesc'] = 'You must create %san application%s, if you wish to access and use Box API.';
$string['applicationname'] = 'Application name';
$string['applicationnamedesc'] = 'You must provide unique application name, e.g. the name of this site.';
$string['applicationweb'] = 'Website';
$string['applicationwebdesc'] = 'The URL where this app will be used, e.g. URL of your site.';

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
$string['revokeconnection'] = 'Revoke connection to Box';
$string['connecttobox'] = 'Connect to Box';

$string['display'] = 'Display';
$string['displaydesc'] = 'Please note that the more files you select to embed, the more time consuming the embedding process becomes.';
$string['displaylist'] = 'List of files';
$string['displayembed'] = 'Embedded files';

$string['embedoptions'] = 'Embed options';
$string['width'] = 'Width';
$string['height'] = 'Height';
$string['allowdownload'] = 'Allow download';
$string['allowprint'] = 'Allow print';
$string['allowshare'] = 'Allow share';
$string['allow'] = 'Which actions are allowed?'; // Katera dejanja so dovoljena?

?>
