---
layout: default
permalink: developers/setup_sms_provider/
title: Setting Up an SMS Provider
---

# Setting Up an SMS Provider

## Twilio

If you use _Twilio_ then all you have to do is add three constants to your _trust path file_. Locate your trust path by looking in _mainfile.php_ in the root of your website and checking what the path and filename are. The three constants are:

```php
define('SMS_ACCOUNT_SID', 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('SMS_AUTH_TOKEN', 'your_auth_token');
define('SMS_FROM_NUMBER', '+15551234567');
```

## Vonage/Nexmo

__This provider has not been tested!__ However, Claude Code was confident that all you need to do is add these four constants to your _trust path file_. Locate your trust path by looking in _mainfile.php_ in the root of your website and checking what the path and filename are. The four constants are:

```php
define('SMS_PROVIDER', 'Nexmo');
define('SMS_ACCOUNT_SID', 'your_api_key');
define('SMS_AUTH_TOKEN', 'your_api_secret');
define('SMS_FROM_NUMBER', 'YourBrand');  // Can be alphanumeric sender ID
```

## Other Providers

The SMS system uses a **factory pattern** to load provider-specific implementations:

```
SmsHandler (factory)
    └─> ProviderInterface (contract)
            ├─> TwilioProvider (default)
            ├─> NexmoProvider (example)
            └─> YourCustomProvider (add your own!)
```

So, to add support for a new SMS service (e.g., AWS SNS, MessageBird, etc.):

### Step 1: Create Provider Class

Create a new file: `YourServiceProvider.php`

```php
<?php
defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

require_once ICMS_ROOT_PATH . '/libraries/icms/messaging/sms/ProviderInterface.php';

class icms_messaging_sms_YourServiceProvider implements icms_messaging_sms_ProviderInterface {

    private $apiKey;
    private $apiSecret;
    private $fromNumber;
    private $errors = array();

    public function __construct() {
        // Load credentials from constants
        $this->apiKey = defined('SMS_ACCOUNT_SID') ? SMS_ACCOUNT_SID : '';
        $this->apiSecret = defined('SMS_AUTH_TOKEN') ? SMS_AUTH_TOKEN : '';
        $this->fromNumber = defined('SMS_FROM_NUMBER') ? SMS_FROM_NUMBER : '';
    }

    public function send($phone, $message) {
        // Validate configuration
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            return "SMS not configured - missing credentials";
        }

        // Your API implementation here
        // ...

        // Return false on success, error string on failure
        return false;
    }

    public function getErrors() {
        return $this->errors;
    }
}
```

### Step 2: Configure Trust Folder

Add to your _trust path file_. Locate your trust path by looking in _mainfile.php_ in the root of your website and checking what the path and filename are.

```php
define('SMS_PROVIDER', 'YourService');  // Matches filename: YourServiceProvider.php
define('SMS_ACCOUNT_SID', 'your_api_key');
define('SMS_AUTH_TOKEN', 'your_api_secret');
define('SMS_FROM_NUMBER', 'your_from_number');
```

### Step 3: Test

The factory will automatically load your provider. No other code changes needed! So try it out and see how it works.

## Configuration Constants Reference

### Required Constants

- **`SMS_ACCOUNT_SID`** - Account identifier, API key, or username
- **`SMS_AUTH_TOKEN`** - Auth token, API secret, or password
- **`SMS_FROM_NUMBER`** - Sender phone number or sender ID

### Optional Constants

- **`SMS_PROVIDER`** - Provider name (defaults to `'Twilio'`)
  - Must match provider filename without "Provider.php"
  - Examples: `'Twilio'`, `'Nexmo'`, `'YourService'`

## Provider Interface

All providers must implement these methods:

```php
interface icms_messaging_sms_ProviderInterface {
    /**
     * Send SMS message
     *
     * @param string $phone Destination phone number
     * @param string $message Message body
     * @return string|false Error message on failure, false on success
     */
    public function send($phone, $message);

    /**
     * Get error messages
     *
     * @return array Array of error messages
     */
    public function getErrors();
}
```

## Phone Number Formatting

Each provider handles phone number normalization differently:

- **Twilio**: E.164 format (+1XXXXXXXXXX)
- **Nexmo/Vonage**: Can accept various formats
- **Your provider**: Implement `normalizePhone()` as needed

Example:

```php
protected function normalizePhone($phone) {
    // Strip non-numeric
    $clean = preg_replace("/[^0-9]/", '', $phone);

    // Add country code
    if (strlen($clean) == 10) {
        $clean = "1" . $clean;
    }

    return "+" . $clean;  // E.164 format
}
```

## Error Handling

Providers should return:

- **`false`** on success
- **Error string** on failure

The error will be displayed to the user or logged.

## Testing Your Provider

1. Set up trust folder constants
2. Test via 2FA system (login with SMS option)
3. Test via notification system (set user notification preference to SMS)

