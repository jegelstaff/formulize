<?php
/*
 * Copyright (c) 2012 Desire2Learn Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the license at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

require_once 'ID2LAppContext.php';
require_once 'D2LConstants.php';
require_once 'D2LUserContext.php';
require_once 'UserOpSecurityParameters.php';
require_once 'D2LSigner.php';
require_once 'D2LHostSpec.php';

/**
 * D2LAppContext instances encapsulate a Desire2Learn LMS application security
 * context.
 */
class D2LAppContext implements ID2LAppContext {

    /*  These constants help explicate some auth query parameter names and values. */
    const APP_ID_PARAMETER = "x_a";
    const APP_KEY_PARAMETER = "x_b";
    const CALLBACK_URL_PARAMETER = "x_target";
    const TYPE_PARAMETER = "type";
    const USER_ID_CALLBACK_PARAMETER = "x_a";
    const USER_KEY_CALLBACK_PARAMETER = "x_b";

    private $_appId;
    private $_appKey;

    /* Build a new app context with provided app ID and app Key. */
    public function __construct($appId, $appKey) {
        $this->_appId = $appId;
        $this->_appKey = $appKey;
    }

	/* Wrapper for createUrlForAuthentication */
	public function createUrlForAuthenticationFromHostSpec($hostSpec, $resultUri) {
		assert($hostSpec->Scheme() == 'http' || $hostSpec->Scheme() == 'https');
		$encryptOperations = ( $hostSpec->Scheme() == 'https' );
		return $this->createUrlForAuthentication($hostSpec->Host(), $hostSpec->Port(), $resultUri, $encryptOperations);
	}

    /* Implements ID2LAppContext.createUrlForAuthentication(host,port,resultUri) */
    public function createUrlForAuthentication($host, $port, $resultUri, $encryptOperations = true) {
        if( $encryptOperations ) {
			$uri  = D2LConstants::URI_SECURE_SCHEME . '://' . $host;
        } else {
			$uri = D2LConstants::URI_UNSECURE_SCHEME . '://' .$host;
		}
		$uri .= ':' . $port;
        $uri .= D2LConstants::AUTHENTICATION_SERVICE_URI_PATH;
        $uri .= '?' . $this->buildAuthenticationUriQueryString($resultUri);

        return $uri;
    }

	/* Wrapper for createUserContext. */
	public function createUserContextFromHostSpec($hostSpec, $userId = null, $userKey = null, $callbackUri = null) {
		assert($hostSpec->Scheme() == 'http' || $hostSpec->Scheme() == 'https');
		$encryptOperations = ($hostSpec->Scheme() == 'https');
		return $this->createUserContext($hostSpec->Host(), $hostSpec->Port(), $encryptOperations, $userId, $userKey, $callbackUri);
	}

    /* Implements ID2LAppContext.createUserContext(host,port,encryptOperations,userId,userKey,callbackUri) */
    public function createUserContext($hostName, $port, $encryptOperations, $userId = null, $userKey = null, $callbackUri = null) {
        // If callback URI wasn't specified, take user ID and key as specified
        if ($callbackUri === null) {
            $parameters = $this->CompileOperationSecurityParameters($userId, $userKey, $hostName, $port, $encryptOperations);
            return new D2LUserContext($parameters);
        } else {
            // Get the user ID and key from the callback URI query string
            $queryString = parse_url($callbackUri, PHP_URL_QUERY);
            if (!$queryString) {
                return null;
            }
            parse_str($queryString, $parsingResult);
            $userId = $parsingResult[D2LAppContext::USER_ID_CALLBACK_PARAMETER];
            $userKey = $parsingResult[D2LAppContext::USER_KEY_CALLBACK_PARAMETER];
            if($userId && $userKey) {
                $parameters = $this->CompileOperationSecurityParameters( $userId, $userKey, $hostName, $port, $encryptOperations);
                //print_r($parameters);
                return new D2LUserContext($parameters);
            } else {
                return null;
            }
        }
    }

    private function buildAuthenticationUriQueryString($callbackUriString) {
        $uriHash = D2LSigner::GetBase64HashString($this->_appKey, $callbackUriString);
        $result = D2LAppContext::APP_ID_PARAMETER . '=' . $this->_appId;
        $result .= '&' . D2LAppContext::APP_KEY_PARAMETER . '=' . $uriHash;
        $result .= '&' . D2LAppContext::CALLBACK_URL_PARAMETER . '=' . urlencode($callbackUriString);
        return $result;
    }

    private function compileOperationSecurityParameters($userId, $userKey, $hostName, $port, $encryptOperations) {
        return new UserOpSecurityParameters($userId, $userKey, $this->_appId,
                                            $this->_appKey, $hostName, $port, $encryptOperations);
    }

}
?>
