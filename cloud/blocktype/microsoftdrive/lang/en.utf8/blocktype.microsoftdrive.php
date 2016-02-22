<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-microsoftdrive
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2016 Gregor Anzelj, info@povsod.com
 *
 */

defined('INTERNAL') || die();

$string['title'] = 'OneDrive';
$string['description'] = 'Select files from OneDrive cloud';

$string['service'] = 'OneDrive';
$string['servicename'] = 'Microsoft OneDrive';

$string['basicinformation'] = 'Basic Information';
$string['applicationdesc'] = 'You must create %san application%s, if you wish to access and use OneDrive API. When you will create the application, the <code>Client ID</code> and <code>Client secret</code> will be generated. Copy and paste them here. Then copy <code>Redirect URL</code> and paste it into application settings. Save application settings and this plugin settings.';
$string['applicationname'] = 'Application name';
$string['applicationnamedesc'] = 'You must provide unique application name, e.g. the name of this site.';
$string['applicationicon'] = 'Application logo';
$string['applicationicondesc'] = 'You can upload logo for your application. The logo must be a transparent 48x48 pixel GIF or PNG. 15 KB size limit.';
$string['applicationterms'] = 'Terms of service URL';
$string['applicationprivacy'] = 'Privacy URL';

$string['apisettings'] = 'API Settings & App Settings';
$string['consumerkey'] = 'Client ID';
$string['consumerkeydesc'] = 'When you\'ll create an application, you\'ll get a Client ID. Paste it here.';
$string['consumersecret'] = 'Client secret';
$string['consumersecretdesc'] = 'When you\'ll create an application, you\'ll get a Client secret. Paste it here.';
$string['redirecturl'] = 'Redirect URL';
$string['redirecturldesc'] = 'URL to return user to, after successful authentication. Copy it and paste it to the list of Redirect URLs.';

$string['selectfiles'] = 'Select files';
$string['revokeconnection'] = 'Revoke connection to OneDrive';
$string['connecttomicrosoftdrive'] = 'Connect to OneDrive';

$string['display'] = 'Display';
$string['displaydesc'] = 'Files that cannot be embedded are displayed as file icons with links to file previews.';
$string['displaydesc2'] = 'Please note that the more files you select to embed, the more time the embedding takes.';
$string['displaylist'] = 'List of files';
$string['displayembed'] = 'Embedded files';
