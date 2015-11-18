<?php
/**
 *
 * @package    mahara
 * @subpackage blocktype-microsoftdrive
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012-2015 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

$string['title'] = 'File(s) from OneDrive';
$string['description'] = 'Select files from OneDrive cloud';

$string['service'] = 'OneDrive'; // Same as plugin folder name, but can be CamelCase, e.g.: OneDrive
$string['servicename'] = 'Microsoft OneDrive'; // Full service name, e.g.: Micorsoft OneDrive

$string['refreshtokennotreturned'] = 'There was no refresh token';
$string['accesstokennotreturned'] = 'There was no access token';
$string['accesstokensaved'] = 'Access token saved sucessfully';
$string['accesstokensavefailed'] = 'Failed to save access token';
$string['servererror'] = 'There was server error when downloading a file';

$string['basicinformation'] = 'Basic Information';
$string['applicationdesc'] = 'You must create %san application%s, if you wish to access and use Live Connect API.';
$string['applicationname'] = 'Application name';
$string['applicationnamedesc'] = 'You must provide unique application name, e.g. the name of this site.';
$string['applicationicon'] = 'Application logo';
$string['applicationicondesc'] = 'You can upload logo for your application. The logo must be a transparent 48x48 pixel GIF or PNG. 15 KB size limit.';
$string['applicationterms'] = 'Terms of service URL';
$string['applicationprivacy'] = 'Privacy URL';

$string['apisettings'] = 'API Settings';
$string['consumerkey'] = 'Client ID';
$string['consumerkeydesc'] = 'When you\'ll create an application, you\'ll get a Client ID. Paste it here.';
$string['consumersecret'] = 'Client secret';
$string['consumersecretdesc'] = 'When you\'ll create an application, you\'ll get a Client secret. Paste it here.';
$string['redirecturl'] = 'Redirect domain';
$string['redirecturldesc'] = 'Live Connect enforces this domain in your OAuth 2.0 redirect URI that exchanges tokens, data, and messages with your application. You only need to enter the domain.';

$string['selectfiles'] = 'Select files';
$string['revokeconnection'] = 'Revoke connection to OneDrive';
$string['connecttomicrosoftdrive'] = 'Connect to OneDrive';

$string['display'] = 'Display';
$string['displaydesc'] = 'Currently only Excel Worksheets and PowerPoint Presentations can be embedded. Other files are embedded as file icons with links to file previews.';
$string['displaydesc2'] = 'Please note that the more files you select to embed, the more time consuming the embedding process becomes.';
$string['displaylist'] = 'List of files';
//$string['displayicon'] = 'Icons of files';
$string['displayembed'] = 'Embedded files';

?>
