<?php

namespace AppBundle\Oro\WebServerLog;

use Doctrine\ORM\EntityManager;
use Kassner\LogParser\FormatException;
use Kassner\LogParser\LogParser;
use AppBundle\Oro\WebServerLog\Model\LogEntry;

/**
 * Class Collector.
 */
class Collector
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var LogParser
     */
    protected $parser;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var int Consecutive invalid log entries
     */
    private $consecutiveInvalid = 0;

    /**
     * TODO configure as external parameter
     * @var int How many consecutive invalid log entries should we read when format considered invalid
     */
    private $invalidLogsThreshold = 20;

    /**
     * Collector constructor.
     *
     * @param EntityManager $em
     * @param Reader        $reader
     * @param LogParser     $parser
     */
    public function __construct(EntityManager $em, Reader $reader, LogParser $parser)
    {
        $this->em = $em;
        $this->reader = $reader;
        $this->setParser($parser);
    }

    /**
     * @param string         $logDir
     * @param \DateTime|null $until
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function collectDir($logDir, \DateTime $until = null)
    {
        $stat = [];
        /** @var $file \SplFileInfo */
        foreach ($this->reader->readDir($logDir) as $file => $fileRows) {
            try {
                $stat[$file->getFilename()] = $this->collectFile($fileRows, $until);
            } catch (FormatException $e) {
                $stat[$file->getFilename()] = $e->getMessage();
            }
        }

        return $stat;
    }

    /**
     * @param \Generator     $fileRows
     * @param \DateTime|null $until
     *
     * @throws FormatException
     *
     * @return array
     */
    private function collectFile($fileRows, \DateTime $until = null)
    {
        $stat = [
            'proceed' => 0,
            'succeed' => 0
        ];
        // TODO make it configurable
        $batchSize = 100;
        $this->consecutiveInvalid = 0; // TODO there is no place for that

        foreach ($fileRows as $row) {
            $stat['proceed']++;

            $logEntry = $this->createLogEntry($row);

            if (!$this->isLogEntryValid($logEntry)) {
                continue;
            }

            if (!$this->shouldContinue($logEntry, $until)) {
                break;
            }

            $this->em->persist($logEntry);
            $stat['succeed']++;

            if ($stat['succeed'] % $batchSize === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }
        $this->em->flush();
        $this->em->clear();

        return $stat;
    }

    /**
     * @param LogEntry|bool $logEntry
     *
     * @return bool
     *
     * @throws FormatException
     */
    private function isLogEntryValid($logEntry)
    {
        if (!$logEntry instanceof LogEntry) {
            $this->consecutiveInvalid++;

            if ($this->consecutiveInvalid > $this->invalidLogsThreshold) {
                throw new FormatException('Log file probably has invalid format, only CLF allowed');
            }

            return false;
        }
        $this->consecutiveInvalid = 0;

        return true;
    }

    /**
     * @param LogEntry       $logEntry
     * @param \DateTime|null $until
     *
     * @return bool
     */
    private function shouldContinue(LogEntry $logEntry, \DateTime $until = null)
    {
        if (!$until instanceof \DateTime) {
            return true;
        }

        return $logEntry->getDatetime() > $until;
    }

    /**
     * @param string $stringRow
     *
     * @return LogEntry|bool
     */
    private function createLogEntry($stringRow)
    {
        try {
            $parsed = $this->parser->parse((string) $stringRow);
        } catch (FormatException $e) {
            return false;
        }
        $format = 'd/M/Y:H:i:s O'; // TODO hard code

        $logEntry = new LogEntry();
        $logEntry->setDatetime(\DateTime::createFromFormat($format, $parsed->time));
        $logEntry->setText($stringRow);

        return $logEntry;
    }

    /**
     * @param LogParser $parser
     */
    private function setParser($parser)
    {
        // TODO set format as dependency
        $parser->setFormat('%h %l %u %t "%r" %>s %b');
        $this->parser = $parser;
    }
}
