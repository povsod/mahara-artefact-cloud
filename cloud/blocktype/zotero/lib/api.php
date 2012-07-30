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
 * @subpackage blocktype-zotero
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


class ZoteroAPI {

/**
 * Get a request token.
 * @param string $useVerb which HTTP verb to use (POST or GET)
 * @param string $consumer_key obtained when you registered your app
 * @param string $consumer_secret obtained when you registered your app
 * @param string $callback callback url
 * @param bool $useHmacSha1Sig use HMAC-SHA1 signature
 * @param bool $passOAuthInHeader pass OAuth credentials in HTTP header
 * @return array of response parameters or empty array on error
 */
public function get_request_token($useVerb='POST', $cloud, $consumer, $useHmacSha1Sig=true, $passOAuthInHeader=false)
{
  $retarr = array();  // return value
  $response = array();

  // Zotero doesn't have API version yet, so...
  //$url = $cloud['baseurl'].$cloud['version'].'/oauth/request_token';
  $url = $cloud['wwwurl'].'oauth/request';

  $params['oauth_version'] = '1.0';
  $params['oauth_nonce'] = mt_rand();
  $params['oauth_timestamp'] = time();
  $params['oauth_consumer_key'] = $consumer['key'];
  $params['oauth_callback'] = $consumer['callback'];

  // compute signature and add it to the params list
  if ($useHmacSha1Sig) {
    $params['oauth_signature_method'] = 'HMAC-SHA1';
    $params['oauth_signature'] =
      oauth_compute_hmac_sig($useVerb, $url, $params, $consumer['secret'], null);
  } else {
    $params['oauth_signature_method'] = 'PLAINTEXT';
    $params['oauth_signature'] =
      oauth_compute_plaintext_sig($consumer['secret'], null);
  }

  // Pass OAuth credentials in a separate header or in the query string
  if ($passOAuthInHeader) {
    $query_parameter_string = oauth_http_build_query($params, true);
    $header = build_oauth_header($params, "Zotero API");
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
    $body_parsed = oauth_parse_str($body);
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
 * Get an access token using a request token and OAuth Verifier.
 * @param string $useVerb which HTTP verb to use (POST or GET)
 * @param string $consumer_key obtained when you registered your app
 * @param string $consumer_secret obtained when you registered your app
 * @param string $request_token obtained from getreqtok
 * @param string $request_token_secret obtained from getreqtok
 * @param string $oauth_verifier obtained from twitter oauth/authorize
 * @param bool $useHmacSha1Sig use HMAC-SHA1 signature
 * @param bool $passOAuthInHeader pass OAuth credentials in HTTP header
 * @return array of response parameters or empty array on error
 */
public function get_access_token($useVerb='POST', $cloud, $consumer, $token, $useHmacSha1Sig=true, $passOAuthInHeader=true)
{
  $retarr = array();  // return value
  $response = array();

  // Zotero doesn't have API version yet, so...
  //$url = $cloud['baseurl'].$cloud['version'].'/oauth/access_token';
  $url = $cloud['wwwurl'].'oauth/access';

  $params['oauth_version'] = '1.0';
  $params['oauth_nonce'] = mt_rand();
  $params['oauth_timestamp'] = time();
  $params['oauth_consumer_key'] = $consumer['key'];
  $params['oauth_token']= $token['oauth_token'];
  $params['oauth_verifier'] = $token['oauth_verifier'];

  // compute signature and add it to the params list
  if ($useHmacSha1Sig) {
    $params['oauth_signature_method'] = 'HMAC-SHA1';
    $params['oauth_signature'] =
      oauth_compute_hmac_sig($useVerb, $url, $params, $consumer['secret'], $token['oauth_token_secret']);
  } else {
    $params['oauth_signature_method'] = 'PLAINTEXT';
    $params['oauth_signature'] =
      oauth_compute_plaintext_sig($consumer['secret'], $token['oauth_token_secret']);
  }

  // Pass OAuth credentials in a separate header or in the query string
  if ($passOAuthInHeader) {
    $query_parameter_string = oauth_http_build_query($params, true);
    $header = build_oauth_header($params, "Zotero API");
    $headers[] = $header;
  } else {
    $query_parameter_string = oauth_http_build_query($params);
  }

  // POST or GET the request
  if (strtoupper($useVerb) == 'POST') {
    $request_url = $url;
    logit("getacctok:INFO:request_url:$request_url");
    logit("getacctok:INFO:post_body:$query_parameter_string");
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
	$port = $cloud['ssl'] ? '443' : '80';
    $response = do_post($request_url, $query_parameter_string, $port, $headers);
  } else {
    $request_url = $url . ($query_parameter_string ?
                           ('?' . $query_parameter_string) : '' );
    logit("getacctok:INFO:request_url:$request_url");
	$port = $cloud['ssl'] ? '443' : '80';
    $response = do_get($request_url, $port, $headers);
  }

  // extract successful response
  if (! empty($response)) {
    list($info, $header, $body) = $response;
    $body_parsed = oauth_parse_str($body);
    if (! empty($body_parsed)) {
      logit("getacctok:INFO:response_body_parsed:");
      print_r($body_parsed);
    }
    $retarr = $response;
    $retarr[] = $body_parsed;
  }

  return $retarr;
}


/**
 * Call Zotero API
 * @param string $useVerb which HTTP verb to use (POST or GET)
 * @param string $consumer_key obtained when you registered your app
 * @param string $consumer_secret obtained when you registered your app
 * @param string $status_message
 * @param string $access_token obtained from get_request_token
 * @param string $access_token_secret obtained from get_request_token
 * @param bool $passOAuthInHeader pass OAuth credentials in HTTP header
 * @return response string or empty array on error
 */
public function make_api_call($useVerb='POST', $call, $cloud, $consumer, $user, $params, $options, $contentAPI=false, $passOAuthInHeader=true)
{
  $retarr = array();  // return value
  $response = array();
  
  /*
  if ($contentAPI) {
    $url = $cloud['contenturl'].$cloud['version'].$call;
	if (isset($options['root']) && isset($options['path'])) {
		$url .= '/'.$options['root'];
		// Split path to file/folder to parts (seprated by / sign),
		// rawurlencode each part and joint hem again to path.
		$parts = explode('/', ltrim($options['path'],'/'));
		foreach($parts as $part) {
			$url .= '/'.rawurlencode($part);
		}
	}
  } else {
    $url = $cloud['baseurl'].$cloud['version'].$call;
	if (isset($options['root']) && isset($options['path'])) {
		$url .= '/'.$options['root'];
		// Split path to file/folder to parts (seprated by / sign),
		// rawurlencode each part and joint hem again to path.
		$parts = explode('/', ltrim($options['path'],'/'));
		foreach($parts as $part) {
			$url .= '/'.rawurlencode($part);
		}
	}
  }
  */
  $url = $cloud['baseurl'] . $call;

  $params['oauth_version'] = '1.0';
  $params['oauth_nonce'] = mt_rand();
  $params['oauth_timestamp'] = time();
  $params['oauth_consumer_key'] = $consumer['key'];
  $params['oauth_token'] = $user['oauth_token'];

  // compute hmac-sha1 signature and add it to the params list
  $params['oauth_signature_method'] = 'HMAC-SHA1';
  $params['oauth_signature'] =
      oauth_compute_hmac_sig($useVerb, $url, $params, $consumer['secret'], $user['oauth_token_secret']);
	  
  // Add user-defined parameters
  foreach ($params as $key => $value) {
    $params[$key] = $value;
  }

  // Pass OAuth credentials in a separate header or in the query string
  if ($passOAuthInHeader) {
    $query_parameter_string = oauth_http_build_query($params, true);
    $header = build_oauth_header($params, "Zotero API");
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
                           ('?' . $query_parameter_string) : '' );
    logit("tweet:INFO:request_url:$request_url");
	$port = $cloud['ssl'] ? '443' : '80';
    $response = do_get($request_url, $port, $headers);
  }

  // extract successful response
  if (! empty($response)) {
    list($info, $header, $body) = $response;
    if ($body) {
      logit("tweet:INFO:response:");
      //print(json_pretty_print($body));
    }
    $retarr = $response;
  }

  return $retarr;
}

} // class end

?>
