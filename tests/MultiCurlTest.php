<?php
/**
 * Created by PhpStorm.
 * User: ec
 * Date: 28.06.15
 * Time: 23:53
 * Project: php-curl
 * @author: Evgeny Pynykh bpteam22@gmail.com
 */

namespace bpteam\Curl;

use \PHPUnit_Framework_TestCase;
use \ReflectionClass;

class MultiCurlTest extends PHPUnit_Framework_TestCase {
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
        $getContent = new MultiCurl();
        $descriptor =& $getContent->getDescriptor();
        $getContent->setCountStream(2);
        $descriptorArray =& $getContent->getDescriptorArray();
        $this->assertArrayHasKey(0, $descriptorArray);
        $this->assertTrue(is_resource($descriptorArray[0]['descriptor']));
        $this->assertArrayHasKey(1, $descriptorArray);
        $this->assertTrue(is_resource($descriptorArray[1]['descriptor']));
        $this->assertTrue(is_resource($descriptor['descriptor']));
    }

    public function testLoad()
    {
        $url = [
            'vk.com',
            'ya.ru'
        ];
        $getContent = new MultiCurl();
        $getContent->setCountStream(5);
        $getContent->load($url);
        $answer = $getContent->getAnswer();
        $this->assertRegExp('%vk\.com%ims', $answer[0]);
        $this->assertRegExp('%yandex%ims', $answer[1]);
    }
}