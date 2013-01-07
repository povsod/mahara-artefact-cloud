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
 * @subpackage blocktype-soundcloud
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

$string['title'] = 'File(s) from Sound Cloud';
$string['description'] = 'Select files from Sound Cloud cloud';

$string['service'] = 'SoundCloud'; // Same as plugin folder name, but can be CamelCase, e.g.: SkyDrive
$string['servicename'] = 'Sound Cloud'; // Full service name, e.g.: Windows Live SkyDrive

$string['tickernotreturned'] = 'There was no ticket';
$string['accesstokennotreturned'] = 'There was no access token';
$string['accesstokensaved'] = 'Access token saved sucessfully';
$string['accesstokensavefailed'] = 'Failed to save access token';

$string['applicationdesc'] = 'You must create %san application%s, if you wish to access and use SoundCloud API.';
$string['apisettings'] = 'API settings';
$string['applicationname'] = 'App name';
$string['applicationnamedesc'] = 'You must provide unique product name, e.g. the name of this site.';
$string['applicationurl'] = 'App website';
$string['applicationurldesc'] = 'The URL where this app will be used, e.g. URL of your site.';
$string['productlogo'] = 'Product logo';
$string['productlogodesc'] = 'You must provide URL address to your product logo. Logo size must not exceed 120x60 pixels.';
$string['consumerkey'] = 'Client ID';
$string['consumerkeydesc'] = 'When you\'ll create a product, you\'ll get a Client ID. Paste it here.';
$string['consumersecret'] = 'Client secret';
$string['consumersecretdesc'] = 'When you\'ll create a product, you\'ll get an Client secret. Paste it here.';
$string['redirecturl'] = 'Redirect URI';
$string['redirecturldesc'] = 'The URL to return user to, after successful authentication. Copy it and paste it to app settings.';

$string['selectfiles'] = 'Select files';
$string['display'] = 'Display';
$string['displaydesc'] = 'Please note that the more files you select to embed, the more time consuming the embedding process becomes.';
$string['displaydesc2'] = 'Embedding is only available for Google Docs files (documents, drawings, presentations and spreadsheets).';
$string['displaylist'] = 'List of files';
$string['displayembed'] = 'Embedded files';

$string['embedoptions'] = 'Embed options';
$string['color'] = 'Color';
$string['autoplay'] = 'Auto play?';

$string['unlimited'] = 'unlimited';
$string['duration'] = 'Duration';
$string['tracks'] = 'Tracks';

?>
