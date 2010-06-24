<?php

define("_ERR_SEARCH","Search");
define("_ERR_SEARCH_OUR_SITE","Search our site:");
define("_ERR_ADVANCED_SEARCH","Advanced Search");
define("_ERR_START_AGAIN","Start again from the <a href='" . XOOPS_URL . "'>home page</a>.");
define("_ERR_CONTACT","Contact the <a href='mailto:%s'>webmaster</a> to report this error.");
define("_ERR_NO", "Error %u");

define("_ERR_400_TITLE", "Bad Request");
define("_ERR_400_DESC", "The request contains bad syntax or cannot be fulfilled.");
define("_ERR_401_TITLE", "Forbidden");
define("_ERR_401_DESC", "");
define("_ERR_402_TITLE", "Payment Required");
define("_ERR_402_DESC", "");
define("_ERR_403_TITLE", "Forbidden");
define("_ERR_403_DESC", "");
define("_ERR_404_TITLE", "Not Found");
define("_ERR_404_DESC", "The page you are trying to access could not be found.");

/*
 *  // TODO: Define all the other errors constants (4xx and 5xx). Can be found in "http://en.wikipedia.org/wiki/List_of_HTTP_status_codes"
4xx Client Error

The request contains bad syntax or cannot be fulfilled.

The 4xx class of status code is intended for cases in which the client seems to have erred. Except when responding to a HEAD request, the server should include an entity containing an explanation of the error situation, and whether it is a temporary or permanent condition. These status codes are applicable to any request method. User agents should display any included entity to the user. These are typically the most common codes encountered while online.

400 Bad Request
    The request contains bad syntax or cannot be fulfilled.
401 Unauthorized
    Similar to 403 Forbidden, but specifically for use when authentication is possible but has failed or not yet been provided. See Basic access authentication and Digest access authentication.
402 Payment Required
    The original intention was that this code might be used as part of some form of digital cash or micropayment scheme, but that has not happened, and this code has never been used.
403 Forbidden
    The request was a legal request, but the server is refusing to respond to it. Unlike a 401 Unauthorized response, authenticating will make no difference.
404 Not Found
405 Method Not Allowed
    A request was made to a URL using a request method not supported by that URL; for example, using GET on a form which requires data to be presented via POST, or using PUT on a read-only resource
406 Not Acceptable
407 Proxy Authentication Required
408 Request Timeout
    Client failed to continue the request - except during playing Adobe Flash videos where it just means the user closed the video window or moved on to another video. ref
409 Conflict
410 Gone
    Indicates that the resource requested is no longer available and will not be available again. This should be used when a resource has been intentionally removed; however, in practice, a 404 Not Found is often issued instead.
411 Length Required
412 Precondition Failed
413 Request Entity Too Large
414 Request-URI Too Long
415 Unsupported Media Type
416 Requested Range Not Satisfiable
    The client has asked for a portion of the file, but the server cannot supply that portion (for example, if the client asked for a part of the file that lies beyond the end of the file).
417 Expectation Failed
422 Unprocessable Entity (WebDAV) (RFC 4918)
    The request was well-formed but was unable to be followed due to semantic errors.
423 Locked (WebDAV) (RFC 4918)
    The resource that is being accessed is locked
424 Failed Dependency (WebDAV) (RFC 4918)
    The request failed due to failure of a previous request (e.g. a PROPPATCH).
425 Unordered Collection
    Defined in drafts of WebDav Advanced Collections, but not present in "Web Distributed Authoring and Versioning (WebDAV) Ordered Collections Protocol" (RFC 3648).
426 Upgrade Required (RFC 2817)
    The client should switch to TLS/1.0.
449 Retry With
    A Microsoft extension: The request should be retried after doing the appropriate action.

[edit] 5xx Server Error

The server failed to fulfill an apparently valid request.

Response status codes beginning with the digit "5" indicate cases in which the server is aware that it has erred or is incapable of performing the request. Except when responding to a HEAD request, the server should include an entity containing an explanation of the error situation, and whether it is a temporary or permanent condition. User agents should display any included entity to the user. These response codes are applicable to any request method.

500 Internal Server Error
501 Not Implemented
502 Bad Gateway
503 Service Unavailable
504 Gateway Timeout
505 HTTP Version Not Supported
506 Variant Also Negotiates (RFC 2295)
507 Insufficient Storage (WebDAV) (RFC 4918)
509 Bandwidth Limit Exceeded
    This status code, while used by many servers, is not an official HTTP status code.
510 Not Extended (RFC 2774)
*/
?>