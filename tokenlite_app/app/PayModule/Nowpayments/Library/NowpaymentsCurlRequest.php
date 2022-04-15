<?php

namespace App\PayModule\Nowpayments\Library;

use App\PayModule\Nowpayments\Library\NowpaymentsValidator;
use App\Models\NowpaymentsTracking;
use App\Models\PaymentMethod;

class NowpaymentsCurlRequest
{
    private $api_url = '';
    private $private_key = '';
    private $public_key = '';
    private $test_mode = false;

    public function __construct($private_key, $public_key, $test_mode)
    {
        $this->private_key = $private_key;
        $this->public_key = $public_key;
        $this->test_mode = $test_mode;

        $this->setEnvironment();
    }

    private function setEnvironment()
    {
        if ($this->test_mode) {
            $this->api_url = 'https://api.sandbox.nowpayments.io/v1/';
        } else {
            $this->api_url = 'https://api.nowpayments.io/v1/';
        }
    }

    public function get($command, array $fields = [])
    {
        return $this->execute('GET', $command, $fields);
    }

    public function post($command, array $fields = [])
    {
        return $this->execute('POST', $command, $fields);
    }

    /**
     * Executes a cURL request to the Nowpayments API.
     */
    private function execute($method, $command, array $fields = [])
    {
        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
            case 'POST':
                break;
            default:
                throw new \InvalidArgumentException("Unsupported HTTP Method: " . $method);
        }

        if (!$this->test_mode) {
            unset($fields['case']);
        }

        // TODO setup validator, for now we will disable this, but should be implemented in the future
        // Validate the passed fields
        // $validator = new NowpaymentsValidator($command, $fields);
        // $validate_fields = $validator->validate();
        // if (strpos($validate_fields, 'Error') !== FALSE) {
        //     echo $validate_fields;
        //     exit();
        // }

        $post_fields = http_build_query($fields, '', '&');
        $url = $this->api_url . $command;

        if ($method == 'GET') {
            $url .= '?' . $post_fields;
        }

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, ['x-api-key:' . $this->public_key]);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_ENCODING, '');
        curl_setopt($handle, CURLOPT_MAXREDIRS, 10);
        curl_setopt($handle, CURLOPT_TIMEOUT, 0);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);

        if ($method == 'POST') {
            curl_setopt($handle, CURLOPT_POST, TRUE);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $post_fields);
        }

        // Execute the cURL session
        $response = curl_exec($handle);
        $info = curl_getinfo($handle);
        $status_code = !empty($info['http_code']) ? $info['http_code'] : 0;

        $request_type = strtolower($method . '-' . $command);
        $request_id = NowpaymentsTracking::log($request_type, json_encode($fields), $response, $status_code);
        // Check the response of the cURL session
        if ($status_code < 400) {
            $result = false;

            // Prepare json result
            if (PHP_INT_SIZE < 8 && version_compare(PHP_VERSION, '5.4.0') >= 0) {
                // We are on 32-bit PHP, so use the bigint as string option.
                // If you are using any API calls with Satoshis it is highly NOT recommended to use 32-bit PHP
                $decoded = json_decode($response, TRUE, 512, JSON_BIGINT_AS_STRING);
            } else {
                $decoded = json_decode($response, TRUE);
            }
            // Check the json decoding and set an error in the result if it failed
            if ($decoded !== NULL && count($decoded)) {
                $result = $decoded;
            } else {
                $result = ['error' => 'Unable to parse JSON result (' . json_last_error() . ')'];
            }
        } else {
            // Throw an error if the response of the cURL session is false
            $result = [
                'error' => 'cURL error: ' . curl_error($handle) . ' ' . json_encode($fields)
            ];
        }

        if (!empty($result)) {
            $result['request_id'] = $request_id;
        }
        
        // close the cURL handle
        curl_close($handle);

        return $result;
    }
}
