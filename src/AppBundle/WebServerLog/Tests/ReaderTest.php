<?php

namespace AppBundle\WebServerLog\Tests;

use AppBundle\WebServerLog\Reader;

/**
 * Class ReaderTest.
 */
class ReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $this->assertInstanceOf('\AppBundle\WebServerLog\Reader', new Reader());
    }

    public function testReadEmptyDir()
    {
        $reader = new Reader();
        $records = $reader->readDir($this->dir('emptyLogDir'));

        $this->assertCount(0, iterator_to_array($records));
    }

    public function testDirWithoutLogFiles()
    {
        $reader = new Reader();
        $records = $reader->readDir($this->dir('withoutLogFilesDir'));

        $this->assertCount(0, iterator_to_array($records));

        $records = $reader->readDir($this->dir('logs'));

        $this->assertGreaterThan(0, count(iterator_to_array($records, false)));
    }

    /**
     * @expectedException \AppBundle\WebServerLog\Exception\WebServerLogException
     */
    public function testIsNotLogFile()
    {
        $reader = new Reader();
        $records = $reader->readFile($this->fileInfo('logs/notLogFile.test'));

        iterator_to_array($records);
    }

    public function testEmptyFile()
    {
        $reader = new Reader();
        $records = $reader->readFile($this->fileInfo('logs/emptyLog.log'));

        $this->assertCount(0, iterator_to_array($records));
    }

    /**
     * @param string $dirName
     * @return string
     */
    private function dir($dirName)
    {
        return $this->getFixturesPath().$dirName;
    }

    /**
     * @param string $fileName
     * @return string
     */
    private function file($fileName)
    {
        return $this->getFixturesPath().$fileName;
    }

    /**
     * Create file with specified name
     *
     * @param string $fileName
     * @return \SplFileInfo
     */
    private function fileInfo($fileName)
    {
        return new \SplFileInfo($this->file(str_replace('/', DIRECTORY_SEPARATOR, $fileName)));
    }

    /**
     * @return string
     */
    private function getFixturesPath()
    {
        return __DIR__.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR;
    }
}
