<?php

namespace AppBundle\WebServerLog\Model;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

/**
 *
 * @Entity(repositoryClass="\AppBundle\WebServerLog\Model\LogEntryRepository")
 * @Table(name="log_entry", indexes={
 *     @Index(name="datetime", columns={"datetime"})
 * })
 */
class LogEntry
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @var \DateTime
     * @Column(type="datetime")
     */
    protected $datetime;

    /**
     * @var string
     * @Column(type="text")
     */
    protected $text;
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return \DateTime
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @param \DateTime $datetime
     */
    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;
    }
}
