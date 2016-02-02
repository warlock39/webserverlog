<?php

namespace AppBundle\Oro\WebServerLog\Tests;

use AppBundle\Oro\WebServerLog\Collector;
use AppBundle\Oro\WebServerLog\Model\LogEntry;
use AppBundle\Oro\WebServerLog\Reader;
use Doctrine\ORM\EntityManager;
use Kassner\LogParser\LogParser;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class CollectorTest.
 */
class CollectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCollectDirUntilSpecified()
    {
        $until = \DateTime::createFromFormat('Y-m-d H:i:s', '2016-01-27 12:00:00');

        $reader = $this->getMock(Reader::class);
        $reader->expects($this->once())
            ->method('readDir')
            ->will($this->returnValue($this->dirLogsGenerator()));

        $entityManager = $this->getEmMock();

        $callback = function (LogEntry $logEntry) use ($until) {
            return $logEntry->getDatetime() > $until;
        };
        $cntGreater = 3;
        $consecutive = array_fill(0, $cntGreater, $this->callback($callback));
        $entityManager->expects($this->exactly($cntGreater))
            ->method('persist')
            ->withConsecutive(...$consecutive);

        $collector  = new Collector($entityManager, $reader, new LogParser());
        $stat = $collector->collectDir('validLogDir', $until);

        $this->assertEquals($cntGreater, array_shift($stat)['succeed']);
    }

    public function testCollectDirUntilNotSpecified()
    {
        $reader = $this->getReader($this->dirLogsGenerator());

        $cntLogs = $this->cntGeneratorLogs($this->logsGenerator());

        $entityManager = $this->getEmMock();
        $entityManager->expects($this->exactly($cntLogs))->method('persist');

        $collector  = new Collector($entityManager, $reader, new LogParser());
        $stat = $collector->collectDir('validLogDir');

        $this->assertEquals($cntLogs, array_shift($stat)['succeed']);
    }
    public function testCollectDirInvalidLogFormat()
    {
        $reader = $this->getReader($this->dirLogsGenerator($this->invalidLogFormatGenerator()));

        $collector  = new Collector($this->getEmMock(), $reader, new LogParser());
        $stat = $collector->collectDir('validLogDir');

        $fileStat = array_shift($stat);

        $this->assertInternalType('string', $fileStat);
        $this->assertEquals('Log file probably has invalid format, only CLF allowed', $fileStat);
    }

    public function testSkipInvalidLogEntry()
    {
        $reader = $this->getReader($this->dirLogsGenerator($this->twoValidAndOtherInvalidLogsGenerator()));

        $collector  = new Collector($this->getEmMock(), $reader, new LogParser());
        $stat = $collector->collectDir('validLogDir');

        $fileStat = array_shift($stat);
        $this->assertEquals(2, $fileStat['succeed']);
    }

    public function testConsecutiveInvalidLogEntriesDifferentLogFiles()
    {
        $file1Logs = $this->createGeneratorFromArray(array_merge(
            array_fill(0, 3, $this->validLogLine()),
            array_fill(0, 10, 'invalid log line')
        ));
        $file2Logs = $this->createGeneratorFromArray(array_merge(
            array_fill(0, 11, 'invalid log line'),
            array_fill(0, 3, $this->validLogLine())
        ));
        $reader = $this->getReader($this->dirLogsGenerator($file1Logs, $file2Logs));

        $collector  = new Collector($this->getEmMock(), $reader, new LogParser());
        $stat = $collector->collectDir('validLogDir');

        foreach ($stat as $fileStat) {
            $this->assertInternalType('array', $fileStat);
            $this->assertGreaterThan(0, $fileStat['proceed']);
        }
    }

    public function testProceedLogsStat()
    {
        $reader = $this->getReader(
            $this->dirLogsGenerator(
                $this->twoValidAndOtherInvalidLogsGenerator(),
                $this->logsGenerator()
            )
        );

        $collector  = new Collector($this->getEmMock(), $reader, new LogParser());
        $stat = $collector->collectDir('validLogDir');

        $fileStat = array_shift($stat);
        $expectedCnt = $this->cntGeneratorLogs($this->twoValidAndOtherInvalidLogsGenerator());
        $this->assertEquals($expectedCnt, $fileStat['proceed']);

        $fileStat = array_shift($stat);
        $expectedCnt = $this->cntGeneratorLogs($this->logsGenerator());
        $this->assertEquals($expectedCnt, $fileStat['proceed']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getEmMock()
    {
        return $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param $returnValue
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getReader($returnValue)
    {
        $reader = $this->getMock(Reader::class);
        $reader->expects($this->once())
            ->method('readDir')
            ->will($this->returnValue($returnValue));

        return $reader;
    }

    /**
     * @return \Generator
     */
    protected function dirLogsGenerator()
    {
        /** @var \Generator[] $logGenerators */
        $logGenerators = func_get_args();
        if (count($logGenerators) === 0) {
            $logGenerators[] = $this->logsGenerator();
        }

        foreach ($logGenerators as $i => $generator) {
            $file = new SplFileInfo('test_'.$i, 'relativePath', 'relativepathname');
            yield $file => $generator;
        }
    }

    /**
     * @return \Generator
     */
    protected function invalidLogFormatGenerator()
    {
        return $this->createGeneratorFromArray(array_fill(0, 21, 'invalid log line'));
    }

    /**
     * @return \Generator
     */
    protected function twoValidAndOtherInvalidLogsGenerator()
    {
        $valid = [
            '127.0.0.1 - - [29/Jan/2016:15:24:31 +0200] "GET /logs?datetime=2016-01-29 HTTP/1.1" 400 142',
            '127.0.0.1 - - [28/Jan/2016:15:24:31 +0200] "GET /logs?datetime=2016-01-29 HTTP/1.1" 400 142'
        ];
        $logs = array_merge(array_fill(0, 21, 'invalid log line'), $valid);
        shuffle($logs);

        return $this->createGeneratorFromArray($logs);
    }

    /**
     * @return string
     */
    protected function validLogLine()
    {
        return '127.0.0.1 - - [29/Jan/2016:15:24:31 +0200] "GET /logs?datetime=2016-01-29 HTTP/1.1" 400 142';
    }

    /**
     * @param array $array
     *
     * @return \Generator
     */
    protected function createGeneratorFromArray(array $array)
    {
        foreach ($array as $row) {
            yield $row;
        }
    }

    /**
     * @return \Generator
     */
    protected function logsGenerator()
    {
        $greater = [
            '127.0.0.1 - - [29/Jan/2016:15:24:31 +0200] "GET /app_dev.php/logs?datetime=2016-01-29 HTTP/1.1" 400 142',
            '127.0.0.1 - - [28/Jan/2016:15:24:31 +0200] "GET /app_dev.php/logs?datetime=2016-01-29 HTTP/1.1" 400 142',
            '127.0.0.1 - - [27/Jan/2016:15:24:31 +0200] "GET /app_dev.php/logs?datetime=2016-01-29 HTTP/1.1" 400 142'
        ];
        $less = [
            '127.0.0.1 - - [26/Jan/2016:15:24:31 +0200] "GET /app_dev.php/logs?datetime=2016-01-29 HTTP/1.1" 400 142',
            '127.0.0.1 - - [25/Jan/2016:15:24:31 +0200] "GET /app_dev.php/logs?datetime=2016-01-29 HTTP/1.1" 400 142'
        ];
        $logs = array_merge($greater, $less);

        return $this->createGeneratorFromArray($logs);
    }

    /**
     * @param \Generator $logs
     *
     * @return int
     */
    protected function cntGeneratorLogs($logs)
    {
        $cnt = 0;
        while ($logs->valid()) {
            $cnt++;
            $logs->next();
        }

        return $cnt;
    }
}
