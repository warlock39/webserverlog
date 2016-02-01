<?php

namespace AppBundle\Oro\WebServerLog\Model;

use AppBundle\Oro\WebServerLog\Exception\WebServerLogException;
use Doctrine\ORM\EntityRepository;
use AppBundle\Oro\WebServerLog\Filter;

/**
 * Class LogEntryRepository.
 */
class LogEntryRepository extends EntityRepository
{
    /**
     * @param Filter[] $filters
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return array
     */
    public function getLogsByFilters(array $filters = [], $limit = null, $offset = null)
    {
        $qb = $this->createQueryBuilder('l');

        foreach ($filters as $filter) {
            if (!$filter instanceof Filter) {
                continue;
            }
            try {
                $filter->match($qb);
            } catch (WebServerLogException $e) {
                continue;
            }
        }
        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return string
     */
    public function getLastLogUpdate()
    {
        $dql = 'SELECT MAX(l.datetime) FROM \AppBundle\Oro\WebServerLog\Model\LogEntry l ';

        return $this->_em->createQuery($dql)->getSingleScalarResult();
    }

    /**
     * @param \DateTime $dateTime
     * @return mixed
     */
    public function deleteLessThan(\DateTime $dateTime)
    {
        $dql = 'DELETE FROM \AppBundle\Oro\WebServerLog\Model\LogEntry l where l.datetime < :datetime';
        $q = $this->_em->createQuery($dql);

        return $q->execute([
            'datetime' => $dateTime
        ]);
    }
}
