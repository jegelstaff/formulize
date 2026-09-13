<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################
/**
 * Transport neutral exception for the shared data access API.
 *
 * The shared core in include/readentries.php throws this. Each transport maps it
 * to whatever its own callers expect: the Public API turns it into an HTTP status
 * code plus a JSON error envelope, while the MCP server rethrows it as a
 * FormulizeMCPException carrying the same type string. The core deliberately does
 * not throw FormulizeMCPException itself, because that would make the module
 * depend on /mcp.
 *
 * The type strings are the same vocabulary FormulizeMCPException uses, so the two
 * maps line up. The status codes here are NOT the same as MCP's: MCP deliberately
 * returns 200 for things like form_not_found so the model sees the explanation as
 * an ordinary tool result, whereas an HTTP API has to answer 404.
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

class FormulizeApiException extends Exception {

    private string $type;
    private array $context;

    public static $typeToHTTPStatusCode = array(
        'authentication_error' => 401,
        'permission_denied' => 403,
        'form_not_found' => 404,
        'method_not_found' => 404,
        'method_not_allowed' => 405,
        'unknown_element' => 400,
        'invalid_arguments' => 400,
        'invalid_data' => 400,
        'server_disabled' => 503,
        'internal_formulize_error' => 500,
    );

    /**
     * @param string message A human readable explanation, safe to show a caller
     * @param string type One of the keys of $typeToHTTPStatusCode
     * @param array context Optional extra detail included in the error envelope
     */
    public function __construct(string $message, string $type, array $context = array(), ?Throwable $previous = null) {
        parent::__construct($message, 0, $previous);
        $this->type = $type;
        $this->context = $context;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getContext(): array {
        return $this->context;
    }

    /**
     * @param int default Status to use for a type that is not in the map
     * @return int The HTTP status code this exception should produce
     */
    public function toHTTPStatusCode($default = 400): int {
        return isset(self::$typeToHTTPStatusCode[$this->type]) ? self::$typeToHTTPStatusCode[$this->type] : $default;
    }

    /**
     * @return array The error object to serialize into the JSON response envelope
     */
    public function toErrorArray(): array {
        $error = array(
            'code' => $this->type,
            'message' => $this->getMessage(),
        );
        if (!empty($this->context)) {
            $error['context'] = $this->context;
        }
        return $error;
    }
}
