<?php
/**
 * PhilSmsSender
 * 
 * Production-ready class for sending SMS via PhilSMS API (POST /api/v3/sms/send).
 * Provides a drop-in sendSms(numbers, message, senderName) interface compatible with previous usage.
 * 
 * Usage:
 *   $result = PhilSmsSender::sendSms(['+639998887777'], 'Hello world');
 *   // $result = ['success' => true/false, 'response' => ..., 'error' => ...]
 */

class PhilSmsSender
{
    /**
     * Send SMS via PhilSMS API.
     *
     * @param string|array $numbers     Recipient number(s) as string (comma-separated or single) or array. Prefer E.164 +63 format.
     * @param string       $message     The SMS message.
     * @param string|null  $senderName  Optional sender ID (overrides default from config).
     * @return array ['success' => bool, 'response' => mixed, 'error' => string|null]
     */
    public static function sendSms($numbers, $message, $senderName = null)
    {
        // [SMS DEBUG] Entered sendSms
        error_log('[SMS DEBUG] Entered PhilSmsSender::sendSms | numbers=' . json_encode($numbers) . ' | message=' . json_encode($message) . ' | senderName=' . json_encode($senderName)); // DEBUG LOG

        // Load config
        $configPath = realpath(__DIR__ . '/../../config/philsms.php');
        error_log('[SMS DEBUG] Loading PhilSMS config file: ' . $configPath); // DEBUG LOG

        $config = @include __DIR__ . '/../../config/philsms.php';
        if ($config === false || !is_array($config)) {
            error_log('[SMS DEBUG] Failed to load PhilSMS config file or config is not an array.'); // DEBUG LOG
            $config = [];
        } else {
            error_log('[SMS DEBUG] PhilSMS config file loaded successfully.'); // DEBUG LOG
            // Do not log full config to avoid leaking token
        }

        $apiToken = isset($config['api_token']) ? trim((string)$config['api_token']) : '';
        $defaultSenderId = isset($config['default_sender_id']) ? trim((string)$config['default_sender_id']) : '';

        // Validate required config
        if ($apiToken === '') {
            error_log('[SMS DEBUG] Validation failed: PhilSMS API token missing'); // DEBUG LOG
            return [
                'success' => false,
                'response' => null,
                'error' => 'PhilSMS API token must be configured.'
            ];
        }

        // Prepare numbers into comma-separated E.164 +63 format string
        if (is_array($numbers)) {
            $list = array_filter(array_map(function ($n) {
                $n = trim((string)$n);
                // Ensure starts with + if numeric 63...
                if ($n !== '' && preg_match('/^639\d{9}$/', $n)) {
                    $n = '+' . $n;
                } elseif ($n !== '' && preg_match('/^09\d{9}$/', $n)) {
                    $n = '+63' . substr($n, 1);
                }
                return $n;
            }, $numbers), function ($n) { return $n !== ''; });
            $recipient = implode(',', $list);
        } else {
            $recipient = trim((string)$numbers);
            if ($recipient !== '') {
                // Normalize simple cases
                if (preg_match('/^639\d{9}$/', $recipient)) {
                    $recipient = '+' . $recipient;
                } elseif (preg_match('/^09\d{9}$/', $recipient)) {
                    $recipient = '+63' . substr($recipient, 1);
                }
            }
        }

        // Validate recipient and message quickly
        $message = trim((string)$message);
        if ($recipient === '' || $message === '') {
            return [
                'success' => false,
                'response' => null,
                'error' => 'Recipient and message are required.'
            ];
        }

        // Resolve sender_id
        $senderId = null;
        if ($senderName !== null) {
            $senderName = trim((string)$senderName);
            if ($senderName !== '') {
                $senderId = $senderName;
            }
        }
        if ($senderId === null || $senderId === '') {
            $senderId = $defaultSenderId !== '' ? $defaultSenderId : 'PhilSMS';
        }

        // Build payload according to PhilSMS API
        $payload = [
            'sender_id' => $senderId,
            'recipient' => $recipient,
            'message'   => $message,
        ];

        $url = 'https://app.philsms.com/api/v3/sms/send';

        // [SMS DEBUG] Prepared payload (without token)
        error_log('[SMS DEBUG] PhilSMS URL: ' . $url . ' | recipient=' . $recipient . ' | sender_id=' . $senderId); // DEBUG LOG

        // cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        // [SMS DEBUG] Executing cURL request to PhilSMS API
        error_log('[SMS DEBUG] Executing cURL request to PhilSMS API'); // DEBUG LOG

        $output = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // [SMS DEBUG] cURL result
        error_log('[SMS DEBUG] PhilSMS cURL result: httpCode=' . $httpCode . ' | curlErr=' . json_encode($curlErr) . ' | output=' . substr((string)$output, 0, 500)); // DEBUG LOG

        if ($curlErr) {
            return [
                'success' => false,
                'response' => null,
                'error' => 'cURL error: ' . $curlErr
            ];
        }

        $response = null;
        if (is_string($output) && $output !== '') {
            $decoded = json_decode($output, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $response = $decoded;
            } else {
                $response = $output; // not JSON (e.g., "Unauthenticated.")
            }
        }

        // Determine success
        $success = ($httpCode >= 200 && $httpCode < 300);
        $errorMsg = null;

        // Refine using response body if possible
        if (is_array($response)) {
            // Common patterns: { status: true/false, message: '...' }
            if (isset($response['status'])) {
                $statusVal = $response['status'];
                if (is_bool($statusVal)) {
                    $success = $success && $statusVal;
                } elseif (is_string($statusVal)) {
                    $success = $success && (strtolower($statusVal) === 'success');
                }
            }
            if (!$success) {
                if (isset($response['message']) && is_string($response['message'])) {
                    $errorMsg = $response['message'];
                } elseif (isset($response['error']) && is_string($response['error'])) {
                    $errorMsg = $response['error'];
                }
            }
        } else {
            // Non-JSON string response
            if (!$success) {
                $trim = trim((string)$response);
                if ($trim !== '') {
                    $errorMsg = $trim;
                }
            }
        }

        if (!$success && !$errorMsg) {
            $errorMsg = 'PhilSMS API request failed with HTTP ' . $httpCode;
        }

        // [SMS DEBUG] Final result
        error_log('[SMS DEBUG] PhilSmsSender result: success=' . ($success ? 'true' : 'false') . ' | error=' . json_encode($errorMsg)); // DEBUG LOG

        return [
            'success' => $success,
            'response' => $response !== null ? $response : $output,
            'error' => $success ? null : $errorMsg
        ];
    }
}