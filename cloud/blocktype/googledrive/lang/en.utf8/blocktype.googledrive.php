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
 * @subpackage blocktype-googledrive
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

$string['title'] = 'File(s) from Google Drive';
$string['description'] = 'Select files from Google Drive cloud';

$string['service'] = 'GoogleDrive'; // Same as plugin folder name, but can be CamelCase, e.g.: SkyDrive
$string['servicename'] = 'Google Drive'; // Full service name, e.g.: Windows Live SkyDrive

$string['tickernotreturned'] = 'There was no ticket';
$string['accesstokennotreturned'] = 'There was no access token';
$string['accesstokensaved'] = 'Access token saved sucessfully';
$string['accesstokensavefailed'] = 'Failed to save access token';

$string['applicationdesc'] = 'You must register your application as an API Project in %sGoogle\'s APIs Console%s, and enable the "Drive API" in the "API Access" tab of a project (on the left).';
$string['brandinginformation'] = 'Branding information';
$string['productname'] = 'Product name';
$string['productnamedesc'] = 'You must provide unique product name, e.g. the name of this site.';
$string['productlogo'] = 'Product logo';
$string['productlogodesc'] = 'You must provide URL address to your product logo. Logo size must not exceed 120x60 pixels.';
$string['webappsclientid'] = 'Client ID for web applications ';
$string['consumerkey'] = 'Client ID';
$string['consumerkeydesc'] = 'When you\'ll create a product, you\'ll get a Client ID. Paste it here.';
$string['consumersecret'] = 'Client secret';
$string['consumersecretdesc'] = 'When you\'ll create a product, you\'ll get an Client secret. Paste it here.';
$string['redirecturl'] = 'Redirect URI';
$string['redirecturldesc'] = 'URL to return user to, after successful authentication. Copy it and paste it to the list of Authorized Redirect URIs.';

$string['selectfiles'] = 'Select files';
$string['display'] = 'Display';
$string['displaydesc'] = 'Please note that the more files you select to embed, the more time consuming the embedding process becomes.';
$string['displaydesc2'] = 'Embedding is only available for Google Docs files (documents, drawings, presentations and spreadsheets).';
$string['displaylist'] = 'List of files';
$string['displayembed'] = 'Embedded files';

$string['embedoptions'] = 'Embed options';
$string['size'] = 'Size';
$string['sizesmall'] = 'Small';
$string['sizemedium'] = 'Medium';
$string['sizelarge'] = 'Large';
$string['sizecustom'] = 'Custom';
$string['width'] = 'Width';
$string['height'] = 'Height';
$string['allowdownload'] = 'Allow download';
$string['allowprint'] = 'Allow print';
$string['allowshare'] = 'Allow share';
$string['allow'] = 'Which actions are allowed?'; // Katera dejanja so dovoljena?

// Export text formats
$string['text/html'] = 'HTML';
$string['text/plain'] = 'Plain Text';
$string['image/jpeg'] = 'JPEG image';
$string['image/png'] = 'PNG image';
$string['image/svg+xml'] = 'SVG image';
$string['application/rtf'] = 'Rich Text';
$string['application/pdf'] = 'Adobe PDF';
$string['application/vnd.oasis.opendocument.text'] = 'OpenDocument Text';
$string['application/x-vnd.oasis.opendocument.text'] = 'OpenDocument Text';
$string['application/msword'] = 'Microsoft Word';
$string['application/vnd.openxmlformats-officedocument.wordprocessingml.document'] = 'Microsoft Word';
$string['application/vnd.oasis.opendocument.spreadsheet'] = 'OpenDocument Spreadsheet';
$string['application/x-vnd.oasis.opendocument.spreadsheet'] = 'OpenDocument Spreadsheet';
$string['application/vnd.ms-excel'] = 'Microsoft Excel';
$string['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'] = 'Microsoft Excel';
$string['application/vnd.oasis.opendocument.presentation'] = 'OpenDocument Presentation';
$string['application/x-vnd.oasis.opendocument.presentation'] = 'OpenDocument Presentation';
$string['application/vnd.openxmlformats-officedocument.presentationml.presentation'] = 'Microsoft PowerPoint';

?>
