<?php

namespace Treblle;

class Treblle {
    
    /**
     * Create a FREE Treblle account => https://treblle.com/register
     * @var string
     */
    private $api_key;

    /**
     * Your Treblle Project ID
     * @var string
     */
    private $project_id;

    /**
     * @var array
     */
    private $payload;

    /**
     * Guzzle instance
     * @var GuzzleHttp\Client
     */
    private $guzzle;

    /**
     * Default fields that will be masked
     * @var array
     */
    private $masked = [
        'password', 'pwd',  'secret', 'password_confirmation', 'cc', 'card_number', 'ccv', 'ssn',
            'credit_score'
    ];

    /**
     * Create a new Treblle instance
     * @param string $api_key 
     * @param string $project 
     * @param array $custom_fields 
     * @return void
     */
    public function __construct($api_key = null, $project_id = null, $custom_fields = null) {

        error_reporting(E_ALL);

        if(is_null($api_key)) {
            throw new \Exception('Please provide a valid Treblle API key.');
        }

        if(is_null($project_id)) {
            throw new \Exception('Please provide a valid Treblle Project ID.');
        }

        if(!class_exists('\GuzzleHttp\Client')) {
            throw new \Exception('Treblle needs the Guzzle HTTP client to work. Please run: composer require guzzlehttp/guzzle');
        }

        $this->api_key = $api_key;
        $this->project_id = $project_id;
        $this->guzzle = new \GuzzleHttp\Client;

        if(is_array($custom_fields)) {
            if(!empty($custom_fields)) {
                $this->masked = array_unique(array_merge($this->masked, $custom_fields));
            }
        }        

        $this->payload = [
            'api_key' => $this->api_key,
            'project_id' => $this->project_id,
            'version' => 0.8,
            'sdk' => 'php',
            'data' => [
                'server' => [
                    'ip' => $this->getServerVariable('SERVER_ADDR'),
                    'timezone' => $this->getTimezone(),
                    'os' => [
                        'name' => php_uname('s'),
                        'release' => php_uname('r'),
                        'architecture' => php_uname('m')
                    ],
                    'software' => $this->getServerVariable('SERVER_SOFTWARE'),
                    'signature' => $this->getServerVariable('SERVER_SIGNATURE'),
                    'protocol' => $this->getServerVariable('SERVER_PROTOCOL'),
                    'encoding' => $this->getServerVariable('HTTP_ACCEPT_ENCODING')
                ],
                'language' => [
                    'name' => 'php',
                    'version' => phpversion(),
                    'expose_php' => $this->getIniValue('expose_php'),
                    'display_errors' => $this->getIniValue('display_errors')
                ],
                'request' => [
                    'timestamp' => $this->getTimestamp(),
                    'ip' => $this->getClientIpAddress(),
                    'url' => $this->getEndpointUrl(),
                    'user_agent' => $this->getServerVariable('HTTP_USER_AGENT'),
                    'method' => $this->getServerVariable('REQUEST_METHOD'),
                    'headers' => getallheaders(),
                    'body' => $this->maskFields($_REQUEST),
                    'raw' => $this->maskFields(json_decode(file_get_contents('php://input'), true))
                ],
                'response' => [
                    'code' => http_response_code(),
                    'headers' => $this->getResponseHeaders(),
                    'size' => 0,
                    'load_time' => 0,
                    'body' => null
                ],
                'errors' => []
            ]
        ];

        ob_start();

        set_error_handler([$this, 'onError']);
        set_exception_handler([$this, 'onException']);
        register_shutdown_function([$this, 'onShutdown']);
    }


    /**
     * Capture PHP errors
     * @param type $type 
     * @param type $message 
     * @param type $file 
     * @param type $line 
     * @return void
     */
    public function onError($type, $message, $file, $line) {

        array_push($this->payload['data']['errors'],
            [
                'source' => 'onError',
                'type' => $this->translateErrorType($type),
                'message' => $message,
                'file' => $file,
                'line' => $line
            ]
        );
    }

    /**
     * Capture PHP exceptions
     * @param type $exception 
     * @return void
     */
    public function onException($exception) {

        array_push($this->payload['data']['errors'],
            [
                'source' => 'onException',
                'type' => 'UNHANDLED_EXCEPTION',
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]
        );      

    }

    /**
     * Process the log when PHP is finished processing
     * @return void
     */
    public function onShutdown() {

        $this->payload['data']['response']['load_time'] = $this->getLoadTime();
        $response_size = ob_get_length();

        $error = error_get_last();

        if(! is_null($error)) {
            if($error['type'] == E_ERROR || $error['type'] == E_PARSE) {
                array_push($this->payload['data']['errors'],
                    [
                        'source' => 'onShutdown',
                        'type' => $this->translateErrorType($error['type']),
                        'message' => $error['message'],
                        'file' => $error['file'],
                        'line' => $error['line']
                    ]
                );
            }
        }

        if($response_size >= 2000000) {

            array_push($this->payload['data']['errors'],
                [
                    'source' => 'onShutdown',
                    'type' => 'E_USER_ERROR',
                    'message' => 'JSON response size is over 2MB',
                    'file' => null,
                    'line' => null
                ]
            );

        } else {

            $decoded_response = json_decode(ob_get_flush());

            if(json_last_error() == JSON_ERROR_NONE) {

                $this->payload['data']['response']['body'] = $decoded_response;
                $this->payload['data']['response']['size'] = $response_size;
            } else {
                array_push($this->payload['data']['errors'],
                    [
                        'source' => 'onShutdown',
                        'type' => 'INVALID_JSON',
                        'message' => 'Invalid JSON format',
                        'file' => null,
                        'line' => null
                    ]
                );
            }

        }

        $this->guzzle->request('POST', 'https://rocknrolla.treblle.com', [
            'connect_timeout' => 3,
            'timeout' => 3,
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key
            ], 
            'body' => json_encode($this->payload)
        ]);
    }

    /**
     * Get the IP address of the requester
     * @return string
     */
    public function getClientIpAddress() {

        if(! empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } else if(! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }
    
        return $ip_address;

    }

    /**
     * Get the current endpoint url
     * @return string
     */
    public function getEndpointUrl() {

        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            $is_secure = 'https://';
        } else {
            $is_secure = 'http://';
        }

        return $is_secure.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }

    /**
     * Get PHP global variables
     * return @string
     */
    public function getServerVariable($variable) {

        if(isset($_SERVER[$variable])) {
            if($_SERVER[$variable]) {
                return $_SERVER[$variable];
            }
        }

        return null;
    }

    /**
     * Get PHP configuration variables
     * return @string
     */
    public function getIniValue($variable) {

        $bool_value = filter_var(ini_get($variable), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if(is_bool($bool_value)) {

            if(ini_get($variable)) {
                return 'On';
            } else {
                return 'Off';
            }

        } else {
            return ini_get($variable);
        }
    }

    /**
     * Calculate the execution time for the script
     * @return float
     */
    public function getLoadTime() {
        if(isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            return (float) microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        } else {
            return (float) 0.0000;
        }
    }

    /**
     * Get current timestamp
     * 
     * return @string
     */
    public function getTimestamp() {

        $now = new \DateTime('UTC');
        return $now->format('Y-m-d H:i:s');
    }

    /**
     * Get current timezone
     * 
     * return @string
     */
    public function getTimezone() {
        
        $timezone = 'UTC';

        if (ini_get('date.timezone')) {
            $timezone = ini_get('date.timezone');
        }

        return $timezone;
    }


    /**
     * Get response headers
     * 
     * return @array
     */
    public function getResponseHeaders() {
        
        $data = [];
        $headers = headers_list();

        if(is_array($headers) && ! empty($headers)) {
            foreach ($headers as $header) {
                $header = explode(':', $header);
                $data[array_shift($header)] = trim(implode(':', $header));
            }
        }

        if(empty($data)) {
            return null;
        }

        return $data;
    }

    /**
     * Translate error type
     * @return string
     */
    public function translateErrorType($type) {

        switch($type) {
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
        }

        return null;
    }

    /**
     * Mask fields
     * @return array
     */
    public function maskFields($data) {
    
        if(!is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {

            if(is_array($value)) {
                $this->maskFields($data[$key]);
            } else {
                foreach ($this->masked as $field) {
                    
                    if(preg_match('/\b'.$field.'\b/mi', $key)) {
                        $data[$key] = str_repeat('*', strlen($value));
                        continue;
                    }
                }
            }
        }

        return $data;
    }

}
