<?php

define('PROXY_API', "https://someapi.com/getURL?url=");

function getURL($url, $max_retry = 0) {
  // First try to use basic CURL to get content 
  $res = do_crawl($url);

  // Second try if CURL does not work for that URL (mean return no content or got error (4xx / 5xx statuses) then try to use ProxyAPI (someapi.com).
  $tried_time = 0;
  while($res['error'] && $tried_time <= $max_retry) {
    $tried_time += 1;
    $res = do_crawl(PROXY_API . urlencode($url));

    // If got timeed out (for 30 seconds) then give the next attempts for max max_retry times until to get valid response.
    if($res['error'] == CURLE_OPERATION_TIMEDOUT) {
      // sleep(30); // un-comment this for get waiting ultil the timeed out is gone
      $max_retry += 1;
    }
  }

  // Return body of response
  return $res['error'] ? "Error code: ". $res['error'] : $res['response'];
}

function do_crawl($url) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_HEADER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // Set timeout for 30 seconds
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Macintosh; Intel Mac OS X x.y; rv:42.0) Gecko/20100101 Firefox/42.0');

  $response = curl_exec($ch);
  $error = curl_errno($ch);

  curl_close($ch);
  return ['error' => $error, 'response' => $response];
}

// For Check
// $resurt = getURL('http://localhost:8001/timeout', 0);
// $resurt = getURL('http://localhost:8001/', 0);
// $resurt = getURL('http://404.localhost:8001', 0);

// echo '<pre>';
// print_r(htmlspecialchars($resurt));
// echo '</pre>';
