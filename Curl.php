<?php
/**
 * Created by PhpStorm.
 * User: ec
 * Date: 28.06.15
 * Time: 10:22
 * Project: php-curl
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\Curl;

use bpteam\Cookie\CurlCookie;
use bpteam\UserAgentSwitcher\UserAgentSwitcher;
use bpteam\DryText\DryPath;

abstract class Curl {
    protected $scheme = 'http';
    protected $schemeDefaultPort = ['http' => 80, 'https' => 443, 'ftp' => 21];
    protected $url;
    protected $answer;
    protected $referer = '';

    protected $saveOption = true;


    protected $useCookie = false;
    protected $useProxy;
    /**
     * @var CurlCookie
     */
    protected $cookie;
    protected $useStaticCookie = false;
    protected $staticCookieFileName;

    /**
     * @var string|cProxy
     */
    public $proxy;
    /**
     * @var UserAgentSwitcher
     */
    public $userAgent;
    public $descriptor = [];
    protected $shareDescriptor;
    public $defaultOptions = array(
        CURLOPT_URL => '',
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_REFERER => '',
        CURLOPT_POST => false,
        CURLOPT_POSTFIELDS => '',
        CURLOPT_PROXY => false,
        CURLOPT_FRESH_CONNECT => false,
        CURLOPT_FORBID_REUSE => false,
        CURLOPT_AUTOREFERER => true,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIEJAR => false,
        CURLOPT_COOKIEFILE => false,
        CURLOPT_HTTPHEADER => [],
        CURLOPT_PORT => 80,
        CURLOPT_MAXREDIRS => 25,
    );

    protected function setAnswer($newAnswer){
        $this->answer = $newAnswer;
    }

    public abstract function getAnswer();

    public function setUserAgent($userAgent){
        $this->setDefaultOption(CURLOPT_USERAGENT, $userAgent);
    }

    public function &getDescriptor() {
        return $this->descriptor;
    }

    /**
     * @param bool|array $descriptor
     * @param               $newReferer
     */
    public function setReferer($newReferer,&$descriptor = false){
        if(!$descriptor){
            $descriptor =& $this->getDescriptor();
        }
        $this->referer = $newReferer;
        $this->setOption($descriptor, CURLOPT_REFERER, $newReferer);
    }

    /**
     * @return mixed
     */
    public function getReferer() {
        return $this->referer;
    }

    /**
     * @param array $defaultOption
     */
    public function setDefaultOptions($defaultOption) {
        $this->defaultOptions = $defaultOption;
    }

    public function setDefaultOption($option, $value) {
        $this->defaultOptions[$option] = $value;
    }

    public function unsetDefaultOption($option){
        unset($this->defaultOptions[$option]);
    }

    /**
     * @return array
     */
    public function getDefaultOptions() {
        return $this->defaultOptions;
    }

    public function getDefaultOption($option) {
        return $this->defaultOptions[$option];
    }

    /**
     * @param mixed $saveOption
     */
    public function setSaveOption($saveOption) {
        $this->saveOption = $saveOption;
    }

    /**
     * @return mixed
     */
    public function getSaveOption() {
        return $this->saveOption;
    }

    protected function reInit(){
        $this->close();
        $this->init();
    }

    /**
     * @param mixed $useProxy
     * @param int   $type
     */
    public function setUseProxy($useProxy, $type = CURLPROXY_HTTP) {
        $this->useProxy = $this->setProxy($useProxy, $type);
    }

    /**
     * @return mixed
     */
    public function getUseProxy() {
        return $this->useProxy;
    }

    /**
     * @param bool|string $proxy
     * @param int         $type CURLPROXY_HTTP | CURLPROXY_SOCKS4 | CURLPROXY_SOCKS5
     * @return bool
     */
    protected function setProxy($proxy, $type = CURLPROXY_HTTP) {
        switch ((bool)$proxy) {
            case true:
                if (is_string($proxy)){
                    if(DryPath::isIp($proxy)){
                        $this->proxy = $proxy;
                        $this->setDefaultOption(CURLOPT_PROXYTYPE, $type);
                    } else {
                        $this->setProxy(false);
                    }
                } elseif(!is_object($this->proxy)) {
                    $this->proxy = new cProxy();
                }
                break;
            default:
                $proxy = false;
                $this->unsetDefaultOption(CURLOPT_PROXY);
                $this->unsetDefaultOption(CURLOPT_PROXYTYPE);
        }
        return (bool)$proxy;
    }

    /**
     * @return string|cProxy
     */
    public function getProxy() {
        return $this->proxy;
    }

    protected function setOptionProxy(&$descriptor){
        if (is_object($this->proxy)) {
            $proxy = $this->proxy->getProxy($descriptor['descriptor_key'], $descriptor['option'][CURLOPT_URL]);
            if (is_string($proxy['proxy']) && DryPath::isIp($proxy['proxy'])){
                $this->setOption($descriptor, CURLOPT_PROXY, $proxy['proxy']);
            } else {
                $descriptor['option'][CURLOPT_URL] = false;
            }
        } elseif (is_string($this->proxy)){
            $this->setOption($descriptor, CURLOPT_PROXY, $this->proxy);
        }
    }

    protected function setOptionCookie(&$descriptor){
        $this->cookie->open($descriptor['descriptor_key']);
        $this->setOption($descriptor, CURLOPT_COOKIEJAR, $this->cookie->getFileName());
        $this->setOption($descriptor, CURLOPT_COOKIEFILE, $this->cookie->getFileName());
    }

    protected function setOptionShare(&$descriptor){
        $this->setOption($descriptor, CURLOPT_SHARE, $this->shareDescriptor);
    }

    /**
     * @param boolean $useStaticCookie
     */
    public function setUseStaticCookie($useStaticCookie) {
        $this->useStaticCookie = (bool)$useStaticCookie;
    }

    /**
     * @return boolean
     */
    public function hasUseStaticCookie() {
        return $this->useStaticCookie;
    }

    /**
     * @param string $staticCookieFileName
     */
    public function setStaticCookieFileName($staticCookieFileName) {
        $this->setUseStaticCookie(true);
        $this->staticCookieFileName = $staticCookieFileName;
    }

    /**
     * @return string
     */
    public function getStaticCookieFileName() {
        return $this->staticCookieFileName;
    }

    function __construct(){
        $this->userAgent = new UserAgentSwitcher('desktop');
        $this->setDefaultOption(CURLOPT_USERAGENT, $this->userAgent->getRandomRecord());
        $this->cookie = new CurlCookie();
        $this->shareInit();
        $this->init();
    }

    function __destruct(){
        curl_share_close($this->shareDescriptor);
        $this->cookie->deleteOldFiles();
    }

    public abstract function load($url);

    protected abstract function init();

    protected abstract function exec();

    protected abstract function close();

    protected function shareInit(){
        $this->shareDescriptor = curl_share_init();
        curl_share_setopt($this->shareDescriptor, CURLSHOPT_SHARE, CURL_LOCK_DATA_DNS);
    }

    public function setOption(&$descriptor, $option, $value = null){
        if ($value === null){
            $descriptor['option'][$option] = $this->getDefaultOption($option);
        } else {
            $descriptor['option'][$option] = $value;
        }
        $this->configOption($descriptor, $option, $descriptor['option'][$option]);
        return true;
    }

    public abstract function removeOption($option);

    public function setOptions(&$descriptor, $options = []){
        foreach($options as $keySetting => $value){
            $this->setOption($descriptor, $keySetting, $options[$keySetting]);
        }
        foreach ($this->defaultOptions as $keySetting => $value) {
            if(!isset($descriptor['option'][$keySetting])) {
                $this->setOption($descriptor, $keySetting);
            }
        }
        if ($this->getUseProxy()) {
            $this->setOptionProxy($descriptor);
        } elseif(isset($descriptor['option'][CURLOPT_PROXY])) {
            unset($descriptor['option'][CURLOPT_PROXY]);
        }
        $this->setOptionCookie($descriptor);
        $this->setOptionShare($descriptor);
        return curl_setopt_array($descriptor['descriptor'], $descriptor['option']);
    }

    protected function configOption(&$descriptor, $option, $value){
        switch ($option) {
            case CURLOPT_POST:
                if ($value != NULL) {
                    $descriptor['option'][$option] = (bool)$value;
                }
                if(!$descriptor['option'][$option] && isset($descriptor['option'][CURLOPT_POSTFIELDS])) {
                    unset($descriptor['option'][CURLOPT_POSTFIELDS]);
                }
                break;
            case CURLOPT_POSTFIELDS:
                if (!$value) {
                    unset($descriptor['option'][$option]);
                    $this->setOption($descriptor, CURLOPT_POST, false);
                } else {
                    $this->setOption($descriptor, CURLOPT_POST, true);
                }
                break;
            case CURLOPT_URL:
                $urlInfo = DryPath::parseUrl($value);
                if(isset($urlInfo['scheme']) && $urlInfo['scheme'] != $this->scheme){
                    $this->setScheme($urlInfo['scheme']);
                }
                break;
            case CURLOPT_PROXY:
                if(DryPath::isIp($value)){
                    $this->useProxy = true;
                }
                break;
            default:
                break;
        }
    }

    protected function saveOption(&$descriptorCurl){
        if (!$this->getSaveOption()){
            $descriptorCurl['option'] = [];
        }
    }

    protected function setScheme($schemeName){
        $this->scheme = $schemeName;
        if(isset($this->schemeDefaultPort[$schemeName])){
            $this->setDefaultOption(CURLOPT_PORT, $this->schemeDefaultPort[$schemeName]);
            $this->removeOption(CURLOPT_PORT);
        }
    }
}