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
 * @subpackage blocktype-box
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


class BoxAPI {

/**
 * Get a ticket.
 * @param string $useVerb use HTTP POST or HTTP GET
 * @param string $consumer_key obtained when you registered your app
 * @param string $consumer_secret obtained when you registered your app
 * @param string $callback callback url
 * @param bool $useHmacSha1Sig use HMAC-SHA1 signature
 * @param bool $passOAuthInHeader pass OAuth credentials in HTTP header
 * @return array of response parameters or empty array on error
 */
public function get_ticket($useVerb='POST', $cloud, $consumer, $useHmacSha1Sig=true, $passOAuthInHeader=false)
{
  $retarr = array();  // return value
  $response = array();

  $url = $cloud['baseurl'].$cloud['version'].'/rest';
  $params['action']  = 'get_ticket';
  $params['api_key'] = $consumer['key'];

  // Pass OAuth credentials in a separate header or in the query string
  if ($passOAuthInHeader) {
    $query_parameter_string = oauth_http_build_query($params, true);
    $header = build_oauth_header($params, "Box API");
    $headers[] = $header;
  } else {
    $query_parameter_string = oauth_http_build_query($params);
  }

  // POST or GET the request
  if (strtoupper($useVerb) == 'POST') {
    $request_url = $url;
    logit("getreqtok:INFO:request_url:$request_url");
    logit("getreqtok:INFO:post_body:$query_parameter_string");
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
	$port = $cloud['ssl'] ? '443' : '80';
    $response = do_post($request_url, $query_parameter_string, $port, $headers);
  } else {
    $request_url = $url . ($query_parameter_string ?
                           ('?' . $query_parameter_string) : '' );
    logit("getreqtok:INFO:request_url:$request_url");
	$port = $cloud['ssl'] ? '443' : '80';
    $response = do_get($request_url, $port, $headers);
  }

  // extract successful response
  if (! empty($response)) {
    list($info, $header, $body) = $response;
    $body_parsed = oauth_parse_xml($body);
    if (! empty($body_parsed)) {
      logit("getreqtok:INFO:response_body_parsed:");
      //print_r($body_parsed);
    }
    $retarr = $response;
    $retarr[] = $body_parsed;
  }

  return $retarr;
}


/**
 * Call Box API
 * @param string $useVerb use HTTP POST or HTTP GET
 * @param string $consumer_key obtained when you registered your app
 * @param string $consumer_secret obtained when you registered your app
 * @param string $status_message
 * @param string $access_token obtained from get_request_token
 * @param string $access_token_secret obtained from get_request_token
 * @param bool $passOAuthInHeader pass OAuth credentials in HTTP header
 * @return response string or empty array on error
 */
public function make_api_call($useVerb='POST', $call, $cloud, $consumer, $user, $userParams=array(), $options=array(), $passOAuthInHeader=true)
{
  $retarr = array();  // return value
  $response = array();

  $url = $cloud['baseurl'].$cloud['version'].'/rest';
  $params['action']     = $call;
  $params['api_key']    = $consumer['key'];
  $params['auth_token'] = $user['auth_token'];
  foreach ($userParams as $key => $value) {
    $params[$key] = $value;
  }
  $strOptions = '';
  foreach ($options as $key => $value) {
	// 'get_account_tree' method can have optional parameters like: array('nozip', 'onelevel', 'nofiles')
	// this means numerical keys... and they should be added to URL in this form: params[]=nozip&params[]=onelevel etc.
	// 'create_file_embed' method can have optional parameters like: array('width' => 300, 'height' => 200)
	// this means non-numerical keys... and they should be added to URL in this form: params[width]=300&params[height]=200 etc.
    $strOptions .= '&params[' . (is_numeric($key) ? '' : $key ) . ']=' . $value;
  }

  // Pass OAuth credentials in a separate header or in the query string
  if ($passOAuthInHeader) {
    $query_parameter_string = oauth_http_build_query($params, true);
    $header = build_oauth_header($params, "Box API");
    $headers[] = $header;
  } else {
    $query_parameter_string = oauth_http_build_query($params);
  }

  // POST or GET the request
  if (strtoupper($useVerb) == 'POST') {
    $request_url = $url;
    logit("tweet:INFO:request_url:$request_url");
    logit("tweet:INFO:post_body:$query_parameter_string");
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
	$port = $cloud['ssl'] ? '443' : '80';
    $response = do_post($request_url, $query_parameter_string, $port, $headers);
  } else {
    $request_url = $url . ($query_parameter_string ?
                           ('?' . $query_parameter_string) : '' ) . $strOptions;
    logit("tweet:INFO:request_url:$request_url");
	$port = $cloud['ssl'] ? '443' : '80';
    $response = do_get($request_url, $port, $headers);
  }

  // extract successful response
  if (! empty($response)) {
	/*
    list($info, $header, $body) = $response;
    if ($body) {
      logit("tweet:INFO:response:");
      print(json_pretty_print($body));
    }
	*/
    $retarr = $response;
  }

  return $retarr;
}

} // class end

?>
