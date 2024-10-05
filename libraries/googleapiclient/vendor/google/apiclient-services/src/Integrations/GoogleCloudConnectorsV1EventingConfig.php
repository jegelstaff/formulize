<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\Integrations;

class GoogleCloudConnectorsV1EventingConfig extends \Google\Collection
{
  protected $collection_key = 'additionalVariables';
  protected $additionalVariablesType = GoogleCloudConnectorsV1ConfigVariable::class;
  protected $additionalVariablesDataType = 'array';
  protected $authConfigType = GoogleCloudConnectorsV1AuthConfig::class;
  protected $authConfigDataType = '';
  protected $encryptionKeyType = GoogleCloudConnectorsV1ConfigVariable::class;
  protected $encryptionKeyDataType = '';
  /**
   * @var bool
   */
  public $enrichmentEnabled;
  /**
   * @var bool
   */
  public $privateConnectivityEnabled;
  /**
   * @var string
   */
  public $publicEventsListenerEndpoint;
  protected $registrationDestinationConfigType = GoogleCloudConnectorsV1DestinationConfig::class;
  protected $registrationDestinationConfigDataType = '';

  /**
   * @param GoogleCloudConnectorsV1ConfigVariable[]
   */
  public function setAdditionalVariables($additionalVariables)
  {
    $this->additionalVariables = $additionalVariables;
  }
  /**
   * @return GoogleCloudConnectorsV1ConfigVariable[]
   */
  public function getAdditionalVariables()
  {
    return $this->additionalVariables;
  }
  /**
   * @param GoogleCloudConnectorsV1AuthConfig
   */
  public function setAuthConfig(GoogleCloudConnectorsV1AuthConfig $authConfig)
  {
    $this->authConfig = $authConfig;
  }
  /**
   * @return GoogleCloudConnectorsV1AuthConfig
   */
  public function getAuthConfig()
  {
    return $this->authConfig;
  }
  /**
   * @param GoogleCloudConnectorsV1ConfigVariable
   */
  public function setEncryptionKey(GoogleCloudConnectorsV1ConfigVariable $encryptionKey)
  {
    $this->encryptionKey = $encryptionKey;
  }
  /**
   * @return GoogleCloudConnectorsV1ConfigVariable
   */
  public function getEncryptionKey()
  {
    return $this->encryptionKey;
  }
  /**
   * @param bool
   */
  public function setEnrichmentEnabled($enrichmentEnabled)
  {
    $this->enrichmentEnabled = $enrichmentEnabled;
  }
  /**
   * @return bool
   */
  public function getEnrichmentEnabled()
  {
    return $this->enrichmentEnabled;
  }
  /**
   * @param bool
   */
  public function setPrivateConnectivityEnabled($privateConnectivityEnabled)
  {
    $this->privateConnectivityEnabled = $privateConnectivityEnabled;
  }
  /**
   * @return bool
   */
  public function getPrivateConnectivityEnabled()
  {
    return $this->privateConnectivityEnabled;
  }
  /**
   * @param string
   */
  public function setPublicEventsListenerEndpoint($publicEventsListenerEndpoint)
  {
    $this->publicEventsListenerEndpoint = $publicEventsListenerEndpoint;
  }
  /**
   * @return string
   */
  public function getPublicEventsListenerEndpoint()
  {
    return $this->publicEventsListenerEndpoint;
  }
  /**
   * @param GoogleCloudConnectorsV1DestinationConfig
   */
  public function setRegistrationDestinationConfig(GoogleCloudConnectorsV1DestinationConfig $registrationDestinationConfig)
  {
    $this->registrationDestinationConfig = $registrationDestinationConfig;
  }
  /**
   * @return GoogleCloudConnectorsV1DestinationConfig
   */
  public function getRegistrationDestinationConfig()
  {
    return $this->registrationDestinationConfig;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudConnectorsV1EventingConfig::class, 'Google_Service_Integrations_GoogleCloudConnectorsV1EventingConfig');
