<?php

namespace AppBundle\WebServerLog\Command;

use AppBundle\WebServerLog\Collector;
use AppBundle\WebServerLog\Model\LogEntry;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CollectorCommand.
 */
class CollectorCommand extends Command
{
    private $entityManager;
    private $collector;
    private $defaultLogDir;

    /**
     * CollectorCommand constructor.
     *
     * @param EntityManager $entityManager
     * @param Collector $collector
     * @param string $defaultLogDif
     *
     * @throws \LogicException
     *
     * TODO non-consistent constructor signature
     */
    public function __construct(EntityManager $entityManager, Collector $collector, $defaultLogDif = '')
    {
        parent::__construct($name = 'logs-collector');

        $this->entityManager = $entityManager;
        $this->collector = $collector;
        $this->defaultLogDir = $defaultLogDif;
    }
    protected function configure()
    {
        $this
            ->setDescription('Collects several log-files into mysql-cache table')
            ->addArgument(
                'logDir',
                InputArgument::OPTIONAL,
                'Specify dir where logs are located'
            )
            ->addOption(
                'keepMax',
                null,
                InputOption::VALUE_REQUIRED,
                'Max date range when logs will be cached',
                '1 day'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logDir = $input->getArgument('logDir');
        $logDir = $logDir ?: $this->defaultLogDir;

        $keepMax = $input->getOption('keepMax');

        // TODO expose into service
        $this->deleteExpiredLogs($keepMax);
        $stat = $this->collector->collectDir($logDir, $this->getLastLogUpdate($keepMax));

        // TODO add more verbose messages and logging
        $output->write(json_encode($stat, JSON_PRETTY_PRINT));
    }

    /**
     * @param string $keepMax
     */
    private function deleteExpiredLogs($keepMax)
    {
        $this->getLogEntryRepository()->deleteLessThan($this->getLastActualLogDate($keepMax));
    }

    /**
     * @param string $keepMax
     * @return \DateTime|null|string
     */
    private function getLastLogUpdate($keepMax)
    {
        $update = $this->getLogEntryRepository()->getLastLogUpdate();
        if (!$update) {
            return null;
        }
        $update = \DateTime::createFromFormat('Y-m-d H:i:s', $update);
        $lastActual = $this->getLastActualLogDate($keepMax);
        if ($update < $lastActual) {
            $update = $lastActual;
        }
        return $update;
    }

    /**
     * @param string $keepMax
     * @return \DateTime
     */
    private function getLastActualLogDate($keepMax)
    {
        return (new \DateTime())->sub(\DateInterval::createFromDateString($keepMax));
    }


    /**
     * @return \AppBundle\WebServerLog\Model\LogEntryRepository
     */
    private function getLogEntryRepository()
    {
        return $this->entityManager->getRepository(LogEntry::class);
    }
}
