<?php
/**
 * @link http://hiqdev.com/yii2-hiar
 * @copyright Copyright (c) 2015 HiQDev
 * @license http://hiqdev.com/yii2-hiar/license
 */

namespace hiqdev\hiar;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\Json;
use yii\web\HttpException;

class Connection extends Component
{
    const EVENT_AFTER_OPEN = 'afterOpen';

    public $config = [
        'api_url'=>'sol-api.ahnames.com',
    ];

    /**
     * Tmporary auth config
     * @var array
     */
    public $auth = [
//        'auth_ip'=>'192.168.1.39',
//        'auth_login'=>'tofid',
//        'auth_password'=>'1309847555',
//        'access_token' => \Yii::$app->user->identity->getAccessToken()
    ];

    public $connectionTimeout = null;

    public $dataTimeout = null;

    public static $curl = null;

    private function _getAuth()
    {
        $res = [];
        if (\Yii::$app->user->identity) {
            $res['access_token'] = \Yii::$app->user->identity->getAccessToken();
        } else {
            \Yii::$app->user->loginRequired();
        }

        return $res;
    }

    public function init()
    {
        if (!isset($this->config['api_url'])) {
            throw new InvalidConfigException('HiActiveResource needs api_url configuration.');
        }
    }

    public function getHandler() {
        if (!self::$curl) {
            self::$curl = static::$curl = curl_init();
        }
        return self::$curl;
    }


    /**
     * Closes the connection when this component is being serialized.
     * @return array
     */
    public function __sleep()
    {
        return array_keys(get_object_vars($this));
    }

    /**
     * Returns the name of the DB driver for the current [[dsn]].
     * @return string name of the DB driver
     */
    public function getDriverName()
    {
        return 'hiresource';
    }

    /**
     * Creates a command for execution.
     * @param array $config the configuration for the Command class
     * @return Command the DB command
     */
    public function createCommand($config = [])
    {
        $config['db'] = $this;
        $command = new Command($config);
        return $command;
    }

    /**
     * Creates new query builder instance
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return new QueryBuilder($this);
    }
    /**
     * Performs GET HTTP request

     *
*@param string $url URL
     * @param array $options URL options
     * @param string $body request body
     * @param boolean $raw if response body contains JSON and should be decoded
     *
*@return mixed response
     * @throws HiResException
     * @throws \yii\base\InvalidConfigException
     */
    public function get ($url, $options = [], $body = null, $raw = false)
    {
        return $this->httpRequest('POST', $this->createUrl($url, $options), $body, $raw);
    }
    /**
     * Performs HEAD HTTP request

     *
*@param string $url URL
     * @param array $options URL options
     * @param string $body request body
     *
*@return mixed response
     * @throws HiResException
     * @throws \yii\base\InvalidConfigException
     */
    public function head($url, $options = [], $body = null)
    {
        return $this->httpRequest('HEAD', $this->createUrl($url, $options), $body);
    }
    /**
     * Performs POST HTTP request

     *
*@param string $url URL
     * @param array $options URL options
     * @param string $body request body
     * @param boolean $raw if response body contains JSON and should be decoded
     *
*@return mixed response
     * @throws HiResException
     * @throws \yii\base\InvalidConfigException
     */
    public function post($url, $options = [], $body = null, $raw = false)
    {
        return $this->httpRequest('POST', $this->createUrl($url, $options), $body, $raw);
    }
    /**
     * Performs PUT HTTP request

     *
*@param string $url URL
     * @param array $options URL options
     * @param string $body request body
     * @param boolean $raw if response body contains JSON and should be decoded
     *
*@return mixed response
     * @throws HiResException
     * @throws \yii\base\InvalidConfigException
     */
    public function put($url, $options = [], $body = null, $raw = false)
    {
        return $this->httpRequest('POST', $this->createUrl($url, $options), $body, $raw);
        //return $this->httpRequest('PUT', $this->createUrl($url, $options), $body, $raw);
    }
    /**
     * Performs DELETE HTTP request

     *
*@param string $url URL
     * @param array $options URL options
     * @param string $body request body
     * @param boolean $raw if response body contains JSON and should be decoded
     *
*@return mixed response
     * @throws HiResException
     * @throws \yii\base\InvalidConfigException
     */
    public function delete($url, $options = [], $body = null, $raw = false)
    {
        // return $this->httpRequest('DELETE', $this->createUrl($url, $options), $body, $raw);
        return $this->httpRequest('POST', $this->createUrl($url, $options), $body, $raw);
    }

    /**
     * @param $url
     * @param array $options
     *
     * @return mixed
     */
    public function perform($url, $options = [])
    {
        return $this->httpRequest('POST', $this->createUrl($url, $options));
    }
    /**
     * Creates URL
     *
     * @param mixed $path path
     * @param array $options URL options
     * @return array
     */
    private function createUrl($path, $options = [])
    {
//        if (!is_string($path)) {
//            $url = implode('/', array_map(function ($a) {
//                return urlencode(is_array($a) ? implode(',', $a) : $a);
//            }, $path));
//            if (!empty($options)) {
//                $url .= '?' . http_build_query($options);
//            }
//        } else {
//            $url = $path;
//            if (!empty($options)) {
//                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($options);
//            }
//        }

        $options = \yii\helpers\ArrayHelper::merge($options, $this->_getAuth());
        if (!is_string($path)) {
            $url = urldecode(reset($path));
            if (!empty($options)) {
                $url .= '?' . http_build_query($options);
            }
        } else {
            $url = $path;
            if (!empty($options)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($options);
            }
        }
        return [$this->config['api_url'], $url];
    }
    /**
     * Performs HTTP request

     *
*@param string $method method name
     * @param string $url URL
     * @param string $requestBody request body
     * @param boolean $raw if response body contains JSON and should be decoded
     *
     * @throws HiResException if request failed
     * @throws \yii\base\InvalidParamException
     * @return mixed response
     */
    protected function httpRequest($method, $url, $requestBody = null, $raw = false)
    {
        $this->auth = [
            'access_token' => \Yii::$app->user->identity->getAccessToken()
        ];
        $method = strtoupper($method);
        // response body and headers
        $headers = [];
        $body = '';
        $options = [
            CURLOPT_URL             => $url,
            CURLOPT_USERAGENT       => 'Yii Framework ' . Yii::getVersion() . __CLASS__,
            //CURLOPT_ENCODING        => 'UTF-8',
            # CURLOPT_USERAGENT       => 'curl/0.00 (php 5.x; U; en)',
            CURLOPT_RETURNTRANSFER  => false,
            CURLOPT_HEADER          => false,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => 2,
            // http://www.php.net/manual/en/function.curl-setopt.php#82418
            CURLOPT_HTTPHEADER      => ['Expect:'],
            CURLOPT_WRITEFUNCTION   => function ($curl, $data) use (&$body) {
                    $body .= $data;
                    return mb_strlen($data, '8bit');
                },
            CURLOPT_HEADERFUNCTION => function ($curl, $data) use (&$headers) {
                    foreach (explode("\r\n", $data) as $row) {
                        if (($pos = strpos($row, ':')) !== false) {
                            $headers[strtolower(substr($row, 0, $pos))] = trim(substr($row, $pos + 1));
                        }
                    }
                    return mb_strlen($data, '8bit');
                },
            CURLOPT_CUSTOMREQUEST  => $method,
        ];
        if ($this->connectionTimeout !== null) {
            $options[CURLOPT_CONNECTTIMEOUT] = $this->connectionTimeout;
        }
        if ($this->dataTimeout !== null) {
            $options[CURLOPT_TIMEOUT] = $this->dataTimeout;
        }
        if ($requestBody !== null) {
            $options[CURLOPT_POSTFIELDS] = $requestBody;
        }
        if ($method == 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
            unset($options[CURLOPT_WRITEFUNCTION]);
        }
        if (is_array($url)) {
            list($host, $q) = $url;
            if (strncmp($host, 'inet[', 5) == 0) {
                $host = substr($host, 5, -1);
                if (($pos = strpos($host, '/')) !== false) {
                    $host = substr($host, $pos + 1);
                }
            }
            $profile = $method . ' ' . $q . '#' . $requestBody;
            if (preg_match("@^https?://@", $host)) $url = $host . '/' . $q;
            else throw new HiResException('HiActiveResource request failed: please specify the protocol (http, https) in reference to the API HiResource Core');
        } else {
            $profile = false;
        }
        $options[CURLOPT_URL] = $url;
        Yii::trace("Sending request to HiActiveResource node: $method $url\n$requestBody", __METHOD__);
        if ($profile !== false) {
            Yii::beginProfile($profile, __METHOD__);
        }
        $curl = $this->getHandler();
        curl_setopt_array($curl, $options);
        if (curl_exec($curl) === false) {
            throw new HiResException('HiActiveResource request failed: ' . curl_errno($curl) . ' - ' . curl_error($curl), [
                'requestMethod' => $method,
                'requestUrl' => $url,
                'requestBody' => $requestBody,
                'responseHeaders' => $headers,
                'responseBody' => $this->decodeErrorBody($body),
            ]);
        }

        $responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        Yii::trace(curl_getinfo($curl));
        if ($profile !== false) {
            Yii::endProfile($profile, __METHOD__);
        }
        if ($responseCode >= 200 && $responseCode < 300) {
            if ($method == 'HEAD') {
                return true;
            } else {
                if (isset($headers['content-length']) && ($len = mb_strlen($body, '8bit')) < $headers['content-length']) {
                    throw new HiResException("Incomplete data received from HiActiveResource: $len < {$headers['content-length']}", [
                        'requestMethod' => $method,
                        'requestUrl' => $url,
                        'requestBody' => $requestBody,
                        'responseCode' => $responseCode,
                        'responseHeaders' => $headers,
                        'responseBody' => $this->decodeErrorBody($body),
                    ]);
                }
                if (isset($headers['content-type']) && !strncmp($headers['content-type'], 'application/json', 16)) {
                    return $raw ? $body : Json::decode($body);
                } else {
                    return $body;
                }
                throw new HiResException('Unsuppor ted data received from Hiresource: ' . $headers['content-type'], [
                    'requestMethod' => $method,
                    'requestUrl' => $url,
                    'requestBody' => $requestBody,
                    'responseCode' => $responseCode,
                    'responseHeaders' => $headers,
                    'responseBody' => $this->decodeErrorBody($body),
                ]);
            }
        } elseif ($responseCode == 404) {
            return false;
        } else {
            throw new HiResException("HiActiveResource request failed with code $responseCode.", [
                'requestMethod' => $method,
                'requestUrl' => $url,
                'requestBody' => $requestBody,
                'responseCode' => $responseCode,
                'responseHeaders' => $headers,
                'responseBody' => $this->decodeErrorBody($body),
            ]);
        }
    }
    /**
     * Try to decode error information if it is valid json, return it if not.
     * @param $body
     * @return mixed
     */
    protected function decodeErrorBody($body)
    {
        try {
            $decoded = Json::decode($body);
            if (isset($decoded['error'])) {
                $decoded['error'] = preg_replace('/\b\w+?Exception\[/', "<span style=\"color: red;\">\\0</span>\n               ", $decoded['error']);
            }
            return $decoded;
        } catch(InvalidParamException $e) {
            return $body;
        }
    }
}
