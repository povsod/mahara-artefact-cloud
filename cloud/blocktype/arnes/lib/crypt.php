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
 * @subpackage blocktype-arnes
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 * Functions to encrypt/decrypt user passwords to store in DB and later
 * use to conncect/login to FTP site and retrieve data from FTP server...
 * SEE: http://stackoverflow.com/questions/1289061/best-way-to-use-php-to-encrypt-and-decrypt/1289114#1289114
 *
 */


function encrypt($string) {
    global $USER;
    $key = 'Ali-'.$USER->get('username').'-R3s potrebuje? m0cn0'.$USER->get('id').'geslo';
    return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));
}

function decrypt($string) {
    global $USER;
    $key = 'Ali-'.$USER->get('username').'-R3s potrebuje? m0cn0'.$USER->get('id').'geslo';
    return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
}

?>
