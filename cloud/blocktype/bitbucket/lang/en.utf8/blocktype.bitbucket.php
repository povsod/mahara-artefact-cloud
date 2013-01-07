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
 * @subpackage blocktype-bitbucket
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

$string['title'] = 'File(s) from Bitbucket';
$string['description'] = 'Select files from Bitbucket cloud';

$string['service'] = 'Bitbucket'; // Same as plugin folder name, but can be CamelCase, e.g.: SkyDrive
$string['servicename'] = 'Bitbucket'; // Full service name, e.g.: Windows Live SkyDrive

$string['tickernotreturned'] = 'There was no ticket';
$string['accesstokennotreturned'] = 'There was no access token';
$string['accesstokensaved'] = 'Access token saved sucessfully';
$string['accesstokensavefailed'] = 'Failed to save access token';

$string['consumergeneral'] = 'Consumer information';
$string['consumerdesc'] = 'You must create %sa consumer%s, if you wish to access and use Bitbucket API.';
$string['consumerdesc2'] = 'First, you need to provide a valid Bitbucket username, than you have to save configuration and refresh this page.';
$string['consumername'] = 'Consumer name';
$string['consumernamedesc'] = 'You must provide unique consumer name, e.g. the name of this site.';
$string['consumeruser'] = 'Bitbucket username';
$string['consumeruserdesc'] = 'You must provide username to create consumer, linked to that username.';

$string['consumerbackend'] = 'Consumer parameters';
$string['consumerkey'] = 'Consumer key';
$string['consumerkeydesc'] = 'When you\'ll create a consumer, you\'ll get a Consumer key. Paste it here.';
$string['consumersecret'] = 'Consumer secret';
$string['consumersecretdesc'] = 'When you\'ll create a consumer, you\'ll get a Consumer secret. Paste it here.';

$string['unlimited'] = 'unlimited'; // As in: You have used 123.45kB of your unlimited quota.

$string['selectfiles'] = 'Select files';
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

$string['repositories'] = 'repositories';
$string['following'] = 'following';
$string['followers'] = 'followers';

?>
