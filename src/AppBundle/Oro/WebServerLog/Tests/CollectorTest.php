<?php

namespace Oro\WebServerLog\Tests;

use Oro\WebServerLog\Collector;

class CollectorTest extends \PHPUnit_Framework_TestCase
{

    public function testCreate()
    {
        $this->assertInstanceOf('Oro\WebServerLog\Collector', new Collector());
    }

    public function testConstructor()
    {
        $this->markTestIncomplete();
    }

    public function testCollectDir()
    {
        $this->markTestIncomplete();
    }

    public function testCollectDirInvalidLogFormat()
    {
        $this->markTestIncomplete();
    }
}
