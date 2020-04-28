<?php

namespace Treblle;

class Treblle {
    
    /**
     * Get your Treblle API key by registering for a FREE account (https://treblle.com/register)
     * @var string
     */
    private $api_key;

    /**
     * Get your Treblle project ID by registering for a FREE account (https://treblle.com/register)
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
     * Create a new Treblle instance
     * @param string $api_key 
     * @return void
     */
    public function __construct($api_key = null, $project_id = null) {

        // TURN ON ERROR REPORTING
        error_reporting(E_ALL);

        if(is_null($api_key)) {
            throw new \Exception('Please provide a valid Treblle API key.');
        }

        if(is_null($project_id)) {
            throw new \Exception('Please provide a valid Treblle Project ID.');
        }

        $this->api_key = $api_key;
        $this->project_id = $project_id;

        if(!class_exists('\GuzzleHttp\Client')) {
            throw new \Exception('Treblle needs the Guzzle HTTP client to work. Please run: composer require guzzlehttp/guzzle');
        }

        $this->guzzle = new \GuzzleHttp\Client;

        $this->payload = array(
            'api_key' => $this->api_key,
            'project_id' => $this->project_id,
            'version' => 0.3,
            'data' => array(
                'server' => array(
                	'timezone' => $this->getTimezone(),
                    'os' => php_uname(),
                    'language' => 'php-'.phpversion(),
                    'sapi' => PHP_SAPI,
                    'software' => $this->getServerVariable('SERVER_SOFTWARE'),
                    'signature' => $this->getServerVariable('SERVER_SIGNATURE'),
                    'protocol' => $this->getServerVariable('SERVER_PROTOCOL')
                ),
                'request' => array(
                    'timestamp' => $this->getTimestamp(),
                    'ip' => $this->getClientIpAddress(),
                    'url' => $this->getEndpointUrl(),
                    'user_agent' => $this->getServerVariable('HTTP_USER_AGENT'),
                    'method' => $this->getServerVariable('REQUEST_METHOD'),
                    'headers' => getallheaders(),
                    'body' => $this->maskFields($_REQUEST),
                    'raw' => $this->maskFields(json_decode(file_get_contents('php://input'), true))
                ),
                'response' => array(
                    'code' => http_response_code(),
                    'size' => 0,
                    'load_time' => 0,
                    'body' => null
                ),
                'errors' => array(),
                'git' => $this->getGitCommit(),
                'meta' => null
            )
        );

        ob_start();

        set_error_handler(array($this, 'onError'));
        set_exception_handler(array($this, 'onException'));
        register_shutdown_function(array($this, 'onShutdown'));
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
            array(
                'source' => 'onError',
                'type' => $this->translateErrorType($type),
                'message' => $message,
                'file' => $file,
                'line' => $line
            )
        );
    }

    /**
     * Capture PHP exceptions
     * @param type $exception 
     * @return void
     */
    public function onException($exception) {

        array_push($this->payload['data']['errors'],
            array(
                'source' => 'onException',
                'type' => 'UNHANDLED_EXCEPTION',
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            )
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

        if(!is_null($error)) {
            if($error['type'] == E_ERROR || $error['type'] == E_PARSE) {
                array_push($this->payload['data']['errors'],
                    array(
                        'source' => 'onShutdown',
                        'type' => $this->translateErrorType($error['type']),
                        'message' => $error['message'],
                        'file' => $error['file'],
                        'line' => $error['line']
                    )
                );
            }
        }

        if($response_size >= 2000000) {

            array(
                'source' => 'onShutdown',
                'type' => 'E_USER_ERROR',
                'message' => 'JSON response size is over 2MB',
                'file' => null,
                'line' => null
            );

        } else {

            $decoded_response = json_decode(ob_get_flush());

            if(json_last_error() == JSON_ERROR_NONE) {

                $this->payload['data']['response']['body'] = $decoded_response;
                $this->payload['data']['response']['size'] = $response_size;
            } else {
                array_push($this->payload['data']['errors'],
                    array(
                        'source' => 'onShutdown',
                        'type' => 'INVALID_JSON',
                        'message' => 'Invalid JSON format',
                        'file' => null,
                        'line' => null
                    )
                );
            }

        }

        $this->guzzle->request('POST', 'https://rocknrolla.treblle.com', [
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key
            ], 
            'body' => json_encode(
                array(
                    'body' => $this->payload
                )
            )
        ]);
    }

    /**
     * Get the IP address of the requester
     * @return string
     */
    public function getClientIpAddress() {

        if(!empty( $_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } else if(!empty( $_SERVER['HTTP_X_FORWARDED_FOR'])) {
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
     * return @array
     */
    public function getServerVariable($variable) {

        if(isset($_SERVER[$variable])) {
            return $_SERVER[$variable];
        } else {
            return null;
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
     * Get git commit information
     * return @array
     */
    public function getGitCommit() {

        exec('git rev-list --format=%B --max-count=1 HEAD', $commit);

        if(!empty($commit)) {
            return array(
                'commit' => trim(ltrim($commit[0], 'commit')),
                'message' => $commit[1]
          );  
        } else {
            return null;
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

    public function getTimezone() {
    	
    	$timezone = 'UTC';

    	if (ini_get('date.timezone')) {
    		$timezone = ini_get('date.timezone');
    	}

    	return $timezone;
    }


    /**
     * Set log meta data
     * @return void
     */
    public function addMeta($meta_1 = null, $meta_2 = null) {

        if(!is_array($this->payload['data']['meta'])) {
            $this->payload['data']['meta'] = array();
        }

        if(!is_null($meta_1) && !is_null($meta_2)) {
            $this->payload['data']['meta'][$meta_1] = $meta_2;
        }
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

        $fields = ['password', 'pwd',  'secret', 'password_confirmation'];
    
        if(!is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {

            foreach ($fields as $field) {
                
                if(preg_match('/'.$field.'/mi', $key)) {
                    $data[$key] = str_repeat('*', strlen($value));
                    continue;
                }

                if(is_array($value)) {
                    $this->maskFields($data[$key]);
                }
            }
        }

        return $data;
    }



}
