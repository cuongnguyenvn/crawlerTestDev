# crawler Dev test
# README #

## This is what you need to do:

Assume we have API to get a URL HTML content: API is here: https://someapi.com/getURL?url=

To get HTML content of a URL we will call GET method to https://someapi.com/getURL with input parameter is encoded `url`.

So to get HTML content of a URL we have *two ways*:

1. Use cURL library by PHP
2. Use ProxyAPI provided by `someapi.com`

You need to create new `getURL` function with parameters (string $url, int $max_retry):


```
function getURL($url, $max_retry = 0) {
	...
    return string of html content when view source HTML of url on browser
}
```

This function need to work in the following way

1. First try to use basic CURL to get content

2. Second try if CURL does not work for that URL (mean return no content or got error (4xx / 5xx statuses) then try to use ProxyAPI (`someapi.com`).

3. If got timeed out (for 30 seconds) then give the next attempts for max `max_retry` times until to get valid response.

This function will look like this
```
Loop
  try get HTML source with CURL
  if fail then try get HTML source with ProxyAPI
  If get valid response then return HTML source
Until reach max_retry
```

### You need to commit: single PHP file with name in format: getURL_{your_bitbucket_accountname}.php
Please create new branch for your commit. Thanks!

