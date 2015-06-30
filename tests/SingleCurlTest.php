<?php
/**
 * Created by PhpStorm.
 * User: ec
 * Date: 28.06.15
 * Time: 23:13
 * Project: php-curl
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\Curl;

use \PHPUnit_Framework_TestCase;
use \ReflectionClass;

class SingleCurlTest extends PHPUnit_Framework_TestCase
{
    public static $name;

    public static function setUpBeforeClass()
    {
        self::$name = 'unit_test';
    }

    /**
     * @param        $name
     * @param string $className
     * @return \ReflectionMethod
     */
    protected static function getMethod($name, $className = 'bpteam\Curl\SingleCurl')
    {
        $class = new ReflectionClass($className);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * @param        $name
     * @param string $className
     * @return \ReflectionProperty
     */
    protected static function getProperty($name, $className = 'bpteam\Curl\SingleCurl')
    {
        $class = new ReflectionClass($className);
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        return $property;
    }

    public function testInit()
    {
        $curl = new SingleCurl();
        $descriptor =& $curl->getDescriptor();
        $this->assertTrue(is_resource($descriptor['descriptor']));
    }

    public function testSetOption()
    {
        $curl = new SingleCurl();
        $descriptor = & $curl->getDescriptor();
        $curl->setOption($descriptor, CURLOPT_TIMEOUT, 5);
        $this->assertEquals(5, $descriptor['option'][CURLOPT_TIMEOUT]);
        $curl->setOption($descriptor, CURLOPT_TIMEOUT);
        $this->assertEquals($curl->getDefaultOption(CURLOPT_TIMEOUT), $descriptor['option'][CURLOPT_TIMEOUT]);
    }

    public function testSetOptions()
    {
        $options = [
            CURLOPT_TIMEOUT => 20,
            CURLOPT_POSTFIELDS => 'qwer=1234&asdf=5678',
        ];
        $curl = new SingleCurl();
        $curl->setOptions($curl->getDescriptor(), $options);
        $descriptor =& $curl->getDescriptor();
        $this->assertEquals($options[CURLOPT_TIMEOUT], $descriptor['option'][CURLOPT_TIMEOUT]);
        $this->assertTrue((bool)$descriptor['option'][CURLOPT_POST]);
        $this->assertEquals($options[CURLOPT_POSTFIELDS], $descriptor['option'][CURLOPT_POSTFIELDS]);
    }

    public function testLoad()
    {
        $curl = new SingleCurl();
        $curl->load('ya.ru');
        $answer = $curl->getAnswer();
        $this->assertRegExp('%yandex%ims', $answer);
        $answer2 = $curl->load('vk.com');
        $this->assertRegExp('%vk\.com%ims', $answer2);
    }

    public function testGetHeader()
    {
        $curl = new SingleCurl();
        $curl->load('ya.ru', '%yandex%ims');
        $descriptor = $curl->getDescriptor();
        $this->assertArrayHasKey('header', $descriptor['info']);
    }

    public function testSetReferer()
    {
        $url = 'http://bpteam.net/referer.php';
        $referer = 'http://iamreferer.net';
        $curl = new SingleCurl();
        $curl->setReferer($referer);
        $curl->load($url);
        $text = $curl->getAnswer();
        $this->assertRegExp('%iamreferer%ims', $text);
    }
}