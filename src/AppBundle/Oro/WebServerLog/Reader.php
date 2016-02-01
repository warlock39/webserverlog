<?php

namespace AppBundle\Oro\WebServerLog;

use AppBundle\Oro\WebServerLog\Exception\WebServerLogException;
use Symfony\Component\Finder\Finder;

/**
 * Class Reader.
 */
class Reader
{
    /**
     * @param string $logDir
     *
     * @throws \InvalidArgumentException
     *
     * @return \Generator
     */
    public function readDir($logDir)
    {
        foreach ($this->getDirFiles($logDir) as $file) {
            try {
                yield $file => $this->readFile($file);
            } catch (WebServerLogException $e) {
                continue;
            }
        }
    }

    /**
     * @param \SplFileInfo|null $file
     *
     * @throws \InvalidArgumentException
     * @throws WebServerLogException
     *
     * @return \Generator
     */
    public function readFile($file)
    {
        $this->assertValidFile($file);

        try {
            $openedFile = $file->openFile('r');
            foreach ($this->readLines($openedFile) as $line) {
                yield $line;
            }
        } finally {
            $openedFile = null;
        }
    }

    /**
     * @param \SplFileObject $file
     *
     * @return \Generator
     */
    private function readLines($file)
    {
        $pos = -1;
        $currentLine = '';
        while (-1 !== $file->fseek($pos, SEEK_END)) {
            $char = $file->fgetc();
            if (PHP_EOL === $char) {
                yield $currentLine;
                $currentLine = '';
            } else {
                $currentLine = $char.$currentLine;
            }
            $pos--;
        }
        if (strlen($currentLine) > 0) {
            yield $currentLine;
        }
    }

    /**
     * @param \SplFileInfo|string $file
     * @return bool
     * @throws WebServerLogException
     * TODO one of cases that produces PHP segfault (php bug)
     */
    private function assertValidFile($file)
    {
        if (!$file instanceof \SplFileInfo) {
            $file = new \SplFileInfo($file);
        }
        if ($file->getExtension() !== 'log') {
            throw WebServerLogException::notLogFile($file);
        }
        if (!$file->isReadable()) {
            throw WebServerLogException::notReadable($file);
        }
    }

    /**
     * @param string $logDir
     *
     * @throws \InvalidArgumentException
     *
     * @return Finder
     */
    private function getDirFiles($logDir)
    {
        $finder = new Finder();
        $finder->ignoreDotFiles(true)->ignoreVCS(true);
        $finder->files()->in($logDir)->name('*.log');

        return $finder;
    }
}
