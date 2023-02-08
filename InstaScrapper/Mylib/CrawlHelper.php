<?php
// Added multi cURL Class
namespace Mylib;
require_once 'ParallelCurl.php';



/* class start */
/**
 * Instagram PHPClass File
 * This is main Class file to get all Instagram information
 * The Instagram Class required open file url & openssl OR Curl
 * if that's requirement doesn't meet, you wont able to run this program
 *
 * @version: 2.2
 * @build: PHP Class
 * @date: 8 August 2017
 * @author: Neeraj Singh
 */
/* class start */
class CrawlHelper
{
    /**
     * Here all urls which use for action in InstaGram
     * @var array
     */
    public $endpoint = array(
        'category'                         => 'http://chibepazam.ir/category/{category}/',
        'category_next_call'               => 'http://chibepazam.ir/category/{category}/page/{page_id}/',
    );
    /**
     * Set your data scrape fetch mode
     * OPTIONS - PHP_ARRAY or JSON
     * JSON, returns data in ajax response as json object
     * PHP_ARRAY, returns data in ajax response as html content
     * @var string
     */
    protected $scrap_mode = 'JSON';
    /**
     * Default action request
     * OPTIONS: get_user_info|search_info
     * @var array
     */
    protected $default_request_action = array(
        'get_category_list',
    );
    /**
     * The request action
     * @var string
     */
    public $request_action = 'get_category_list';
    /**
     * Response for Request
     * @var string
     */
    public $curl_response_data = null;
    /**
     * Collect all inks or link or #hahtag or name
     * @var array
     */
    private $users_input = array();
    /**
     * Instagram link input element name
     * @var string
     */
    private $input_key = 'keyword';
    /**
     * Collection of all regex string and json format
     * @var string
     */
    protected $regex_pattern = array(
        // Get  '<script></script>' tag from source code, html
        'script'          => '/<script[^>](.*)_sharedData(.*)<\/script>/',
        // This is RegxPattern to extract JavaScript variables from html source code
        'json'            => '/(?i)<script[[:space:]]+type="text\/JavaScript"(.*?)>([^\a]+?)<\/script>/si',
        // This is RegexPattern to get only json object for php
        'object'          => '/(window\.\_sharedData \=|\;)/',
        // short call json data __a=1
        'filter_response' => 'window._sharedData={"entry_data":{"ProfilePage":[{source}]}};',
        // If in case any error, return this json in ajax response
        'error_response'  => 'window._sharedData = {"error": "{error}"};',
        // This RegexPattern to detect if response is null/empty
        'empty_error'     => '"entry_data": {}',
    );
    /**
     * Class constructor function
     * @param string $request_action
     */
    public function initialize($request_action = 'get_category_list', $request_data = array())
    {
        // validate request
        if ($this->validateRequestType() === true) {
            $this->request_action = $request_action;
            // set data in class property
            if (!empty($request_data) && is_array($request_data)) {
                // set link or search array data
                $this->users_input = $request_data;
                // return response
                return true;
            } else {
                // set error response
                $this->exitErrorString('request');
            }
        } else {
            // set error response
            $this->exitErrorString('request');
        }
    }
    /**
     * Check what type of request we got
     * it is search or simple account info
     */
    private function validateRequestType()
    {
        if (in_array($this->request_action, $this->default_request_action)) {
            return true;
        } else {
            $this->exitErrorString('request');
        }
    }
    /**
     * Set action as per request
     * to proceed further orations
     */
    public function setRequest($give_meta = false)
    {
        if (1) {
            // check action type & build request url according to request
            if ($this->request_action === 'get_category_list') {
                // build request url
                if (isset($this->users_input['page_id'])) {
                    $this->users_input = str_replace(array('{category}', '{page_id}'), array($this->users_input[$this->input_key], $this->users_input['page_id']), $this->endpoint['category_next_call']);
                } else {
                    $this->users_input = str_replace('{category}', $this->users_input[$this->input_key], $this->endpoint['category']);
                }
                // user's account information requested, bulk mode
                // account name information, single user
                if ($give_meta){
                    if ($this->sendRequest() ) {
                        // return response
                        return $this->curl_response_data;
                    }
                } else {
                    if ($this->sendRequest() && $this->buildResultFromCurl()) {
                        // return response
                        return $this->getResponse();
                    }
                }

            } elseif ($this->request_action === 'search_info') {
                // check if hash tag or account name
                if (trim($this->users_input[$this->input_key])) {
                    // build request url
                    $this->users_input = str_replace('{tag}', $this->users_input[$this->input_key], $this->endpoint['search_tags_json']);
                    // hash tag search
                    if ($this->sendRequest() && $this->buildResultFromCurl()) {
                        // return response
                        return $this->getResponse();
                    }
                }
            } else {
                // error request
                $this->exitErrorString('request');
            }
        } else {
            return $this->getResponse();
        }
    }
    /**
     * Proceed Instagram Scraping
     */
    private function sendRequest()
    {
        // check allow_url_fopen settings
        if (ini_get('allow_url_fopen') && extension_loaded('openssl')) {
            // get source html data
            $this->curl_response_data = @file_get_contents($this->users_input);
            // check return data status
            if ($this->curl_response_data) {
                // return status
                return true;
            }
        }
        // make sure cURL enable
        elseif (function_exists('curl_version')) {
            // execute multi curl
            $parallelcurl = new \ParallelCurl();
            // set curl option
            $parallelcurl->setOptions($this->get_curl_std_options());
            // send link and get response by callback
            $parallelcurl->startRequest($this->users_input, function ($data) {
                // set response data
                $this->curl_response_data = $data;
            });
            // get response
            $parallelcurl->finishAllRequests();
            // check return data status
            if ($this->curl_response_data && !$this->curl_response_data) {
                // return status
                return true;
            }
        } else {
            // show error
            $this->exitErrorString('curl');
        }
    }
    /**
     * Check if array has valid urls or url
     * @param array $request
     */
    private function validateUrls($key, $request = array())
    {
        // extract array key as php vars
        extract($request);
        // url validation & data assign
        if ((isset($$key) && is_array($$key) && filter_var_array($$key, FILTER_VALIDATE_URL)) || isset($$key) && filter_input(INPUT_POST, $key, FILTER_VALIDATE_URL)) {
            return true;
        } else {
            $this->exitErrorString('url');
        }
    }
    /**
     * @return mixed
     */
    private function buildResultFromCurl()
    {
        //echo "<pre>", print_r($this->curl_response_data), "</pre>";
        //echo print_r($this->curl_response_data);
        //echo $this->curl_response_data;


        // get <script></script> tag
        preg_match_all($this->regex_pattern['script'], $this->curl_response_data, $filter);
        // get script inside json data
        preg_match($this->regex_pattern['json'], array_shift($filter[0]), $output);
        //echo "<pre>", print_r($output), "</pre>";
// if source get check scrape mode
        if ($this->scrap_mode === 'PHP_ARRAY' && !empty($output)) {
            // if want to PHP array
            if ($this->request_action === 'get_user_info') {
                // check empty response
                if (strpos($output[2], $this->regex_pattern['empty_error']) !== false) {
                    // remove JavaScript var from data & build result array, you have to manage html view by array
                    $this->curl_response_data = $this->buildJsonToPhpArray(json_decode(preg_replace($this->regex_pattern['object'], '', $output[2]), true));
                } else {
                    // url error
                    $this->exitErrorString('url');
                }
            }
            // else if filter on
            //elseif ($this->request_action === 'search_info') {
            else {
                // build result array, you have to manage html view by array
                $this->curl_response_data = $this->buildJsonToPhpArray(json_decode($this->curl_response_data, true));
            }// else {
            //     // error response
            //     $this->exitErrorString('default');
            // }
        }
        // return as JavaScript json
        elseif ($this->scrap_mode === 'JSON' && !empty($output)) {

            // if wanna json data
            if ($this->request_action === 'get_user_info') {
                // check empty response
                if (strpos($output[2], $this->regex_pattern['empty_error']) !== false) {
                    $this->curl_response_data = $output[2];
                    // build result array, you have to manage html view by array
                    //$this->curl_response_data = str_replace('{source}', $this->curl_response_data, $this->regex_pattern['filter_response']);
                } else {
                    // url error
                    $this->exitErrorString('url');
                }
            } //elseif ($this->request_action === 'search_info') {
                // build result array, you have to manage html view by array
                //$this->curl_response_data = str_replace('{source}', $this->curl_response_data, $this->regex_pattern['filter_response']);
            //} else {
            //    $this->exitErrorString('default');
            //}
        } else {
            //$this->curl_response_data = str_replace('{source}', $this->curl_response_data, $this->regex_pattern['filter_response']);
            return $this->curl_response_data;
        }
    }
    /**
     * Build the array data from json respond
     * @param  [type] $data           [description]
     * @return [type] [description]
     */
    public function buildJsonToPhpArray($data)
    {
        $array_data = array();
        // collect all personal information, same key as in result data
        $array_data['country_code']  = $data['country_code'];
        $array_data['language_code'] = $data['language_code'];
        // get
        extract(array_shift($data['entry_data']['ProfilePage']));
        // this is total post count
        $array_data['count']              = $user['media']['count'];
        $array_data['biography']          = $user['biography'];
        $array_data['external_url']       = $user['external_url'];
        $array_data['count']              = $user['followed_by']['count'];
        $array_data['count']              = $user['follows']['count'];
        $array_data['follows_viewer']     = $user['follows_viewer'];
        $array_data['full_name']          = $user['full_name'];
        $array_data['id']                 = $user['id'];
        $array_data['username']           = $user['username'];
        $array_data['external_url']       = $user['external_url'];
        $array_data['profile_pic_url']    = $user['profile_pic_url'];
        $array_data['followed_by_viewer'] = $user['followed_by_viewer'];
        // collect all post information
        if ($user['media']['nodes']) {
            // parse one by one
            foreach ($user['media']['nodes'] as $index => $array) {
                $array_data['post']['data'][$index]['id']            = $array['id'];
                $array_data['post']['data'][$index]['thumbnail_src'] = $array['thumbnail_src'];
                $array_data['post']['data'][$index]['is_video']      = $array['is_video'];
                $array_data['post']['data'][$index]['date']          = $array['date'];
                $array_data['post']['data'][$index]['display_src']   = $array['display_src'];
                $array_data['post']['data'][$index]['caption']       = isset($array['caption']) ? $array['caption'] : '';
                $array_data['post']['data'][$index]['comments']      = $array['comments']['count'];
                $array_data['post']['data'][$index]['likes']         = $array['likes']['count'];
            }
        }
        return $array_data;
    }
    /**
     * Start html source code parsing
     */
    private function parseResponseScript()
    {}
    /**
     * Building final output from
     * Instagram source scrape data
     */
    private function prepareResponseData()
    {}
    /**
     * Display the data
     * @return [type] [description]
     */
    public function getResponse()
    {
        // set json header
        //header('Content-Type: application/javascript');
        // get script
        return $this->curl_response_data;
    }
    /**
     * Set User Post Key for Instagram links
     * @param string $key [description]
     */
    public function setInputKey($key = 'keyword')
    {
        $this->input_key = $key;
    }
    /**
     * cURL settings
     * @return type
     */
    private function cUrlOptionHandler()
    {
        // create a random ip address
        $dynamic_ip = "" . mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255) . "." . mt_rand(0, 255);
        // add additional curl options here
        return array(
            // return web page
            CURLOPT_RETURNTRANSFER => true,
            // don't return headers
            CURLOPT_HEADER         => false,
            // follow redirects
            CURLOPT_FOLLOWLOCATION => true,
            // handle all encodings
            CURLOPT_ENCODING       => "",
            // set referer on redirect
            CURLOPT_AUTOREFERER    => true,
            // timeout on connect
            CURLOPT_CONNECTTIMEOUT => 120,
            // timeout on response
            CURLOPT_TIMEOUT        => 120,
            // stop after 10 redirects
            CURLOPT_MAXREDIRS      => 10,
            // Disabled SSL Cert checks
            CURLOPT_SSL_VERIFYPEER => false,
            // user agent be present in the request
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
            // set fake ip address
            CURLOPT_HTTPHEADER     => array("REMOTE_ADDR: $dynamic_ip", "HTTP_X_FORWARDED_FOR: $dynamic_ip"),
        );
    }
    /**
     * Return all error string
     * @param  string    $error_key
     * @return private
     */
    private function exitErrorString($error_key = 'default')
    {
        // check request typeof error
        switch ($error_key) {
            case 'curl':
                $error = "You must enable Curl or 'allow_url_fopen' & 'openssl' to use this application";
                break;
            case 'url':
                $error = "Invalid or corrupt Instagram url request.";
                break;
            case 'json':
                $error = "You must enable Curl or 'allow_url_fopen' & 'openssl' to use this application";
                break;
            case 'request':
                $error = "Invalid data request or bad input send...";
                break;
            default:
                $error = "Error, unknown error determine. Please try again.";
                break;
        }
        // return error response in json format
        echo "Error : ", $error;
        $this->curl_response_data = str_replace('{error}', $error, $this->regex_pattern('error_response'));
        // return break status
        return false;
    }
    /**
     * [regex_pattern description]
     * @param  [type] $key [description]
     * @return [type]      [description]
     */
    public function regex_pattern($key)
    {
        if (isset($this->regex_pattern[$key])) {
            return $this->regex_pattern[$key];
        } else {
            return false;
        }
    }





    public function run($request, $params, $give_meta = false)
    {
        $this->initialize($request, $params);
        // call action
        $this->setInputKey('keyword');
        // get and return ajax response
        $result_raw = $this->setRequest($give_meta);
        //
        if ($give_meta){
            return $response_array = $result_raw;
        } else {
            return $response_array = json_decode($result_raw, TRUE);
        }
    }

    public function getWeb($page_address)
    {
      $this->users_input = $page_address;
      if ($this->sendRequest() ) {
          // return response
          return $this->curl_response_data;
      }
    }
}
