<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-dropbox
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2017 Gregor Anzelj, info@povsod.com
 *
 */

defined('INTERNAL') || die();

$string['title'] = 'Dropbox';
$string['description'] = 'Select files from Dropbox cloud';

$string['service'] = 'Dropbox';
$string['servicename'] = 'Dropbox';

$string['applicationgeneral'] = 'Settings';
$string['applicationdesc'] = 'You must create %san application%s, if you wish to access and use Dropbox API. When you will create the application, the <code>App key</code> and <code>App secret</code> will be generated. Copy and paste them here. Then copy <code>Redirect URI</code> and paste it into application settings. Save application settings and this plugin settings.';
$string['permissiontype'] = 'Permission type';
$string['fulldropbox'] = 'Full Dropbox';
$string['consumerkey'] = 'App key';
$string['consumerkeydesc'] = 'When you\'ll create an app, you\'ll get an App key. Paste it here.';
$string['consumersecret'] = 'App secret';
$string['consumersecretdesc'] = 'When you\'ll create an app, you\'ll get an App secret. Paste it here.';
$string['redirecturl'] = 'Redirect URI';
$string['redirecturldesc'] = 'URL to return user to, after successful authentication. Copy it and paste it to the list of OAuth 2 redirect URIs.';

$string['applicationadditional'] = 'Branding';
$string['applicationname'] = 'App name';
$string['applicationnamedesc'] = 'You must provide unique app name, e.g. the name of this site.';
$string['applicationweb'] = 'App website';
$string['applicationwebdesc'] = 'The URL where this app will be used, e.g. URL of your site.';
$string['applicationicon'] = 'App icons';
$string['applicationicondesc'] = 'You can upload icons for your app in following sizes: 64x64 and/or 256x256.';

$string['selectfiles'] = 'Select files';
$string['revokeconnection'] = 'Revoke connection to Dropbox';
$string['connecttodropbox'] = 'Connect to Dropbox';
