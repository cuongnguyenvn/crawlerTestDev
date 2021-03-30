<?php

define('PROXY_URL', 'https://someapi.com/getURL');

class CurlException extends RuntimeException
{
    public function isTimeout(): bool
    {
        return $this->code === CURLE_OPERATION_TIMEOUTED;
    }
}

/**
 * Converts raw header responses into an array
 *
 * @param  string $rawHeaders
 * @return array
 */
function parseHeaders(string $rawHeaders): array
{
    $headers = [];

    // Normalize line breaks
    $rawHeaders = str_replace("\r\n", "\n", $rawHeaders);

    // There will be multiple headers if a 301 was followed
    // or a proxy was followed, etc
    $headerCollection = explode("\n\n", trim($rawHeaders));

    // We just want the last response (at the end)
    $rawHeader = array_pop($headerCollection);

    $headerComponents = explode("\n", $rawHeader);
    foreach ($headerComponents as $line) {
        if (strpos($line, ': ') !== false) {
            // Eg: Content-Type: text/html
            list($key, $value) = explode(': ', $line);
            $headers[$key] = $value;
        } elseif (strpos($line, 'HTTP/' !== false)) {
            // Eg: HTTP/2 200
            $headers['http_code'] = intval(explode(" ", $line)[1]);
        }
    }

    return $headers;
}

/**
 * Get content of url by cURL
 *
 * @param  string $url
 * @param  int    $timeout  The seconds of timeout option
 * @return array  Format [status, headers, body]
 * @throws CurlException
 */
function getUrlByCURL(string $url, int $timeout = 60): array
{
    $curlOptions = [
        CURLOPT_CONNECTTIMEOUT => $timeout > 10 ? 10 : $timeout,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_RETURNTRANSFER => true, // Follow 301 redirects
        CURLOPT_HEADER         => true, // Enable header processing
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'PHP/cURL client',
        CURLOPT_URL            => $url,
        CURLOPT_HTTPGET        => true,
    ];

    $handle = curl_init();
    curl_setopt_array($handle, $curlOptions);

    $rawResponse = curl_exec($handle);

    $errno = curl_errno($handle);
    switch ($errno) {
        case CURLE_OK:
            // All OK, no actions needed.
            break;
            // case CURLE_COULDNT_RESOLVE_PROXY:
            // case CURLE_COULDNT_RESOLVE_HOST:
            // case CURLE_COULDNT_CONNECT:
            // case CURLE_OPERATION_TIMEOUTED:
            // case CURLE_SSL_CONNECT_ERROR:
        default:
            throw new CurlException(curl_error($handle), $errno);
    }

    $headerSize = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
    curl_close($handle);

    $rawHeaders = mb_substr($rawResponse, 0, $headerSize);
    $rawBody = mb_substr($rawResponse, $headerSize);
    $headers = parseHeaders($rawHeaders);

    // echo $rawResponse;
    // echo $rawHeaders;
    // echo $rawBody;

    return [
        $headers['http_code'] ?? 200,   // status code
        $headers,                       // http headers
        $rawBody,                       // body
    ];
}

/**
 * Get content of url by ProxyAPI
 *
 * @param  string $url
 * @param  int    $timeout  The seconds of timeout option
 * @return array  Format [status, headers, body]
 * @throws CurlException
 */
function getUrlByProxy(string $url, int $timeout = 60): array
{
    $url = PROXY_URL . '?url=' . rawurlencode($url);

    return getUrlByCURL($url, $timeout);
}

/**
 * Get content of URL
 *
 * @param  string $url
 * @param  int    $max_retry
 * @return string
 */
function getURL(string $url, int $max_retry = 0): string
{
    while ($max_retry >= 0) {
        // try get HTML source with CURL
        try {
            list($status, $headers, $body) = getUrlByCURL($url, 30);
            if ($status && $status > 100 && $status < 400) {
                return $body;
            }
        } catch (CurlException $e) {
            // TODO: log
        }

        // try get HTML source with ProxyAPI
        try {
            list($status, $headers, $body) = getUrlByProxy($url, 30);
            if ($status && $status > 100 && $status < 400) {
                return $body;
            }
        } catch (CurlException $e) {
            // TODO: log
            if (!$e->isTimeout()) {
                throw $e;
            }
        }

        $max_retry -= 1;
    }

    return '';
}

// Test
// echo getURL('https://dantri.com.vn', 2);
