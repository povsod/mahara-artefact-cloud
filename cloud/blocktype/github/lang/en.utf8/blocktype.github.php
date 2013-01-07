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
 * @subpackage blocktype-github
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 */

defined('INTERNAL') || die();

$string['title'] = 'Repo(s) from GitHub';
$string['description'] = 'Select repos from GitHub cloud';

$string['service'] = 'GitHub'; // Same as plugin folder name, but can be CamelCase, e.g.: SkyDrive
$string['servicename'] = 'GitHub'; // Full service name, e.g.: Windows Live SkyDrive

$string['refreshtokennotreturned'] = 'There was no refresh token';
$string['accesstokennotreturned'] = 'There was no access token';
$string['accesstokensaved'] = 'Access token saved sucessfully';
$string['accesstokensavefailed'] = 'Failed to save access token';
$string['servererror'] = 'There was server error when downloading a file';

$string['applicationdesc'] = 'You must create %san application%s, if you wish to access and use GitHub API.';
$string['apisettings'] = 'API Settings';
$string['consumerkey'] = 'Client ID';
$string['consumerkeydesc'] = 'When you\'ll create an application, you\'ll get a Client ID. Paste it here.';
$string['consumersecret'] = 'Client secret';
$string['consumersecretdesc'] = 'When you\'ll create an application, you\'ll get a Client secret. Paste it here.';
$string['applicationname'] = 'Application name';
$string['applicationnamedesc'] = 'You must provide unique application name, e.g. the name of this site.';
$string['applicationurl'] = 'Application URL';
$string['applicationurldesc'] = 'The URL where this app will be used, e.g. URL of your site. Copy it and paste it to app settings.';
$string['redirecturl'] = 'Callback URL';
$string['redirecturldesc'] = 'URL to return user to, after successful authentication. Copy it and paste it to app settings.';

$string['selectfiles'] = 'Select files';

$string['public_repos'] = 'repositories';
$string['following'] = 'following';
$string['followers'] = 'followers';


?>
