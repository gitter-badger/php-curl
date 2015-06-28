<?php
/**
 * Created by PhpStorm.
 * User: ec
 * Date: 28.06.15
 * Time: 23:03
 * Project: php-curl
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\Curl;

use bpteam\HttpHeader\HttpHeader;

class SingleCurl extends Curl{
    protected $redirectCount;
    protected $maxRedirectCount = 10;

    /**
     * @param mixed $redirectCount
     */
    public function setRedirectCount($redirectCount) {
        $this->redirectCount = $redirectCount;
    }

    /**
     * @return mixed
     */
    public function getRedirectCount() {
        return $this->redirectCount;
    }

    /**
     * @param mixed $maxRedirectCount
     */
    public function setMaxRedirectCount($maxRedirectCount) {
        $this->maxRedirectCount = $maxRedirectCount;
    }

    /**
     * @return mixed
     */
    public function getMaxRedirectCount() {
        return $this->maxRedirectCount;
    }

    public function setKeyStream($key){
        $descriptor =& $this->getDescriptor();
        $descriptor['descriptor_key'] = $key;
    }

    public function getKeyStream(){
        $descriptor =& $this->getDescriptor();
        return $descriptor['descriptor_key'];
    }

    protected function useRedirect(){
        $this->setRedirectCount($this->getRedirectCount()+1);
        return ($this->getRedirectCount() <= $this->getMaxRedirectCount());
    }

    public function removeOption($option){
        $descriptor =& $this->getDescriptor();
        $descriptor['option'][$option] = null;
        $this->configOption($descriptor, $option, null);
    }

    function __construct(){
        parent::__construct();
    }

    function __destruct(){
        parent::__destruct();
    }

    public function init(){
        $descriptor =& $this->getDescriptor();
        if (!isset($descriptor['descriptor_key']) || !$descriptor['descriptor_key']){
            $descriptor['descriptor_key'] = uniqid('single_curl');
        }
        $descriptor['descriptor'] = curl_init();
    }

    protected function exec(){
        $descriptor =& $this->getDescriptor();
        return curl_exec($descriptor['descriptor']);
    }

    public function close(){
        $descriptor =& $this->getDescriptor();
        curl_close($descriptor['descriptor']);
        unset($descriptor['descriptor']);
        $this->saveOption($descriptor);
    }

    public function load($url){
        $descriptor =& $this->getDescriptor();
        $this->setOption($descriptor, CURLOPT_URL, $url);
        $this->setOptions($descriptor);
        $answer = $this->exec();
        $descriptor['info'] = curl_getinfo($descriptor['descriptor']);
        $descriptor['info']['error'] = curl_error($descriptor['descriptor']);
        $descriptor['info']['header'] = HttpHeader::cutHeader($answer);
        if(HttpHeader::isRedirect($descriptor['info']['http_code'])){
            if($this->useRedirect()){
                $this->setReferer($url, $descriptor);
                $answer = $this->load($descriptor['info']['redirect_url']);
            }
        }
        $this->setRedirectCount(0);
        $this->setAnswer($answer);
        $this->reInit();
        return $this->getAnswer();
    }

    public function getAnswer(){
        return $this->answer;
    }

    public function getInfo(){
        $descriptor = $this->getDescriptor();
        return isset($descriptor['info']) ? $descriptor['info'] : false;
    }
}