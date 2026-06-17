<?php
use PHPUnit\Framework\TestCase;

class HelloWorldTest2 extends TestCase
{
    public function testHelloWorld()
    {
        $this->assertEquals('Hello, World!', 'Hello, World!');
    }
}