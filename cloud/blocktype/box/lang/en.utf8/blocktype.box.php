<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-box
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2016 Gregor Anzelj, info@povsod.com
 *
 */

defined('INTERNAL') || die();

$string['title'] = 'Box';
$string['description'] = 'Select files from Box cloud';

$string['service'] = 'Box';
$string['servicename'] = 'Box';

$string['applicationgeneral'] = 'General Information';
$string['applicationdesc'] = 'You must create %san application%s, if you wish to access and use Box API. When you will create the application, the <code>client_id</code> and <code>client_secret</code> will be generated. Copy and paste them here. Then copy <code>redirect_uri</code> and paste it into application settings. Save application settings and this plugin settings.';
$string['applicationname'] = 'Application name';
$string['applicationnamedesc'] = 'You must provide unique application name, e.g. the name of this site.';
$string['applicationweb'] = 'Website URL (optional):';
$string['applicationwebdesc'] = 'The URL where this app will be used, e.g. URL of your site.';

$string['applicationbackend'] = 'OAuth2 Parameters';
$string['consumerkey'] = 'client_id';
$string['consumerkeydesc'] = 'When you\'ll create an app, you\'ll get an client_id. Paste it here.';
$string['consumersecret'] = 'client_secret';
$string['consumersecretdesc'] = 'When you\'ll create an app, you\'ll get an client_secret. Paste it here.';
$string['redirecturl'] = 'redirect_uri';
$string['redirecturldesc'] = 'URL to return user to, after successful authentication. Copy it and paste it to app settings.';
$string['applicationicon'] = 'App icon';
$string['applicationicondesc'] = 'You can upload favicon (16x16), directory icon (64x64) and application description icon (100x80) for your app.';

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
