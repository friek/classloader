<?php
/**
 * Created by Johan Mulder <johan@cambrium.nl>
 * Date: 2016-05-20 15:12
 */

namespace ClassLoader;


use Psr\Log\NullLogger;

class ClassWithDependencyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassLoader
     */
    private $classLoader;

    public function setUp()
    {
        $this->classLoader = new ClassLoader(new NullLogger());
    }

    public function testLoadClass()
    {
        /** @var TestClass $test */
        $test = $this->classLoader->getObject(TestClass::class);
        $this->assertInstanceOf(TestClass::class, $test);

        $this->assertInstanceOf(TestDependency::class, $test->dependency);
        $this->assertEquals('blaat', $test->testString);
        $this->assertEquals('*uninitialized*', $test->testString);
    }
}

class TestClass
{
    public $dependency;
    public $testString;

    public function __construct(TestDependency $dependency, $testString = 'blaat')
    {
        $this->dependency = $dependency;
        $this->testString = $testString;
    }
}

class TestDependency
{

}