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
 * @subpackage artefact-cloud
 * @author     Gregor Anzelj
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2012 Gregor Anzelj, gregor.anzelj@gmail.com
 *
 *
 * This file incorporates work covered by the following copyright and
 * permission notice:
 *
 *    The MIT License for OAuth Examples
 *    Copyright (c) 2010 Joe Chung - joechung at yahoo dot com
 *
 *    OAuth Examples code also contains software derived from the PHP OAuth Library
 *    Copyright 2007 Andy Smith:
 *
 *    MIT License - for PHP OAuth Library
 *    Copyright © 2007 Andy Smith
 *
 *    Permission is hereby granted, free of charge, to any person obtaining a copy
 *    of this software and associated documentation files (the "Software"), to deal
 *    in the Software without restriction, including without limitation the rights
 *    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *    copies of the Software, and to permit persons to whom the Software is
 *    furnished to do so, subject to the following conditions:
 *
 *    The above copyright notice and this permission notice shall be included in
 *    all copies or substantial portions of the Software.
 *
 *    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *    THE SOFTWARE.
 *
 */

/**
 * Pretty print some JSON
 * @param string $json The packed JSON as a string
 * @param bool $html_output true if the output should be escaped
 * (for use in HTML)
 * @link http://us2.php.net/manual/en/function.json-encode.php#80339
 */
function json_pretty_print($json, $html_output=false)
{
  $spacer = '  ';
  $level = 1;
  $indent = 0; // current indentation level
  $pretty_json = '';
  $in_string = false;

  $len = strlen($json);

  for ($c = 0; $c < $len; $c++) {
    $char = $json[$c];
    switch ($char) {
    case '{':
    case '[':
      if (!$in_string) {
        $indent += $level;
        $pretty_json .= $char . "\n" . str_repeat($spacer, $indent);
      } else {
        $pretty_json .= $char;
      }
      break;
    case '}':
    case ']':
      if (!$in_string) {
        $indent -= $level;
        $pretty_json .= "\n" . str_repeat($spacer, $indent) . $char;
      } else {
        $pretty_json .= $char;
      }
      break;
    case ',':
      if (!$in_string) {
        $pretty_json .= ",\n" . str_repeat($spacer, $indent);
      } else {
        $pretty_json .= $char;
      }
      break;
    case ':':
      if (!$in_string) {
        $pretty_json .= ": ";
      } else {
        $pretty_json .= $char;
      }
      break;
    case '"':
      if ($c > 0 && $json[$c-1] != '\\') {
        $in_string = !$in_string;
      }
    default:
      $pretty_json .= $char;
      break;
    }
  }

  return ($html_output) ?
    '<pre>' . htmlentities($pretty_json) . '</pre>' :
    $pretty_json . "\n";
}

/**
 * Build a query parameter string according to OAuth Spec.
 * @param array $params an array of query parameters
 * @return string all the query parameters properly sorted and encoded
 * according to the OAuth spec, or an empty string if params is empty.
 * @link http://oauth.net/core/1.0/#rfc.section.9.1.1
 */
function oauth_http_build_query($params, $excludeOauthParams=false)
{
  $query_string = '';
  if (! empty($params)) {

    // rfc3986 encode both keys and values
    $keys = rfc3986_encode(array_keys($params));
    $values = rfc3986_encode(array_values($params));
    $params = array_combine($keys, $values);

    // Parameters are sorted by name, using lexicographical byte value ordering.
    // http://oauth.net/core/1.0/#rfc.section.9.1.1
    uksort($params, 'strcmp');

    // Turn params array into an array of "key=value" strings
    $kvpairs = array();
    foreach ($params as $k => $v) {
      if ($excludeOauthParams && substr($k, 0, 5) == 'oauth') {
        continue;
      }
      if (is_array($v)) {
        // If two or more parameters share the same name,
        // they are sorted by their value. OAuth Spec: 9.1.1 (1)
        natsort($v);
        foreach ($v as $value_for_same_key) {
          array_push($kvpairs, ($k . '=' . $value_for_same_key));
        }
      } else {
        // For each parameter, the name is separated from the corresponding
        // value by an '=' character (ASCII code 61). OAuth Spec: 9.1.1 (2)
        array_push($kvpairs, ($k . '=' . $v));
      }
    }

    // Each name-value pair is separated by an '&' character, ASCII code 38.
    // OAuth Spec: 9.1.1 (2)
    $query_string = implode('&', $kvpairs);
  }

  return $query_string;
}

/**
 * Parse a query string into an array.
 * @param string $query_string an OAuth query parameter string
 * @return array an array of query parameters
 * @link http://oauth.net/core/1.0/#rfc.section.9.1.1
 */
function oauth_parse_str($query_string)
{
  $query_array = array();

  if (isset($query_string)) {

    // Separate single string into an array of "key=value" strings
    $kvpairs = explode('&', $query_string);

    // Separate each "key=value" string into an array[key] = value
    foreach ($kvpairs as $pair) {
      list($k, $v) = explode('=', $pair, 2);

      // Handle the case where multiple values map to the same key
      // by pulling those values into an array themselves
      if (isset($query_array[$k])) {
        // If the existing value is a scalar, turn it into an array
        if (is_scalar($query_array[$k])) {
          $query_array[$k] = array($query_array[$k]);
        }
        array_push($query_array[$k], $v);
      } else {
        $query_array[$k] = $v;
      }
    }
  }

  return $query_array;
}

/**
 * Parse a xml string into an array.
 * @param string $xml_string an OAuth xml string response
 * @return array an array of parameters
 */
function oauth_parse_xml($xml_string)
{
  $xml = simplexml_load_string($xml_string);
  $json = json_encode($xml);
  $array = json_decode($json, true);
    
  return $array;
}

/**
 * Build an OAuth header for API calls
 * @param array $params an array of query parameters
 * @return string encoded for insertion into HTTP header of API call
 */
function build_oauth_header($params, $realm='')
{
  $header = 'Authorization: OAuth realm="' . $realm . '"';
  foreach ($params as $k => $v) {
    if (substr($k, 0, 5) == 'oauth') {
      $header .= ',' . rfc3986_encode($k) . '="' . rfc3986_encode($v) . '"';
    }
  }
  return $header;
}

/**
 * Compute an OAuth PLAINTEXT signature
 * @param string $consumer_secret
 * @param string $token_secret
 */
function oauth_compute_plaintext_sig($consumer_secret, $token_secret)
{
  return ($consumer_secret . '&' . $token_secret);
}

/**
 * Compute an OAuth HMAC-SHA1 signature
 * @param string $http_method GET, POST, etc.
 * @param string $url
 * @param array $params an array of query parameters for the request
 * @param string $consumer_secret
 * @param string $token_secret
 * @return string a base64_encoded hmac-sha1 signature
 * @see http://oauth.net/core/1.0/#rfc.section.A.5.1
 */
function oauth_compute_hmac_sig($http_method, $url, $params, $consumer_secret, $token_secret)
{
  global $debug;

  $base_string = signature_base_string($http_method, $url, $params);
  $signature_key = rfc3986_encode($consumer_secret) . '&' . rfc3986_encode($token_secret);
  $sig = base64_encode(hash_hmac('sha1', $base_string, $signature_key, true));

  return $sig;
}

/**
 * Make the URL conform to the format scheme://host/path
 * @param string $url
 * @return string the url in the form of scheme://host/path
 */
function normalize_url($url)
{
  $parts = parse_url($url);

  $scheme = $parts['scheme'];
  $host = $parts['host'];
  $port = (isset($parts['port'])) ? $parts['port'] : null;
  $path = $parts['path'];

  if (! $port) {
    $port = ($scheme == 'https') ? '443' : '80';
  }
  if (($scheme == 'https' && $port != '443')
      || ($scheme == 'http' && $port != '80')) {
    $host = "$host:$port";
  }

  return "$scheme://$host$path";
}

/**
 * Returns the normalized signature base string of this request
 * @param string $http_method
 * @param string $url
 * @param array $params
 * The base string is defined as the method, the url and the
 * parameters (normalized), each urlencoded and the concated with &.
 * @see http://oauth.net/core/1.0/#rfc.section.A.5.1
 */
function signature_base_string($http_method, $url, $params)
{
  // Decompose and pull query params out of the url
  $query_str = parse_url($url, PHP_URL_QUERY);
  if ($query_str) {
    $parsed_query = oauth_parse_str($query_str);
    // merge params from the url with params array from caller
    $params = array_merge($params, $parsed_query);
  }

  // Remove oauth_signature from params array if present
  if (isset($params['oauth_signature'])) {
    unset($params['oauth_signature']);
  }

  // Create the signature base string. Yes, the $params are double encoded.
  $base_string = rfc3986_encode(strtoupper($http_method)) . '&' .
                 rfc3986_encode(normalize_url($url)) . '&' .
                 rfc3986_encode(oauth_http_build_query($params));

  return $base_string;
}

/**
 * Encode input per RFC 3986
 * @param string|array $raw_input
 * @return string|array properly rfc3986 encoded raw_input
 * If an array is passed in, rfc3896 encode all elements of the array.
 * @link http://oauth.net/core/1.0/#encoding_parameters
 */
function rfc3986_encode($raw_input)
{
  if (is_array($raw_input)) {
    return array_map('rfc3986_encode', $raw_input);
  } else if (is_scalar($raw_input)) {
    return str_replace('%7E', '~', rawurlencode($raw_input));
  } else {
    return '';
  }
}

function rfc3986_decode($raw_input)
{
  return rawurldecode($raw_input);
}


?>
