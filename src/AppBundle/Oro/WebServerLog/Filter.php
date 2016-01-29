<?php

namespace AppBundle\Oro\WebServerLog;

use Doctrine\ORM\QueryBuilder;
use AppBundle\Oro\WebServerLog\Exception\WebServerLogException;

/**
 * Class Filter.
 */
class Filter
{
    /**
     * @var string
     */
    private $fieldName;
    /**
     * @var string
     */
    private $operator;
    /**
     * @var array
     */
    private $value;

    /**
     * Filter constructor.
     *
     * @param string $fieldName
     * @param string|array $value
     * @param null|string $operator
     *
     * @throws WebServerLogException
     */
    public function __construct($fieldName, $value, $operator = null)
    {
        $this->fieldName = (string) $fieldName;
        $this->operator = $operator ?: 'eq';
        $this->setValue($value);
    }

    /**
     * @param QueryBuilder $qb
     *
     * @throws WebServerLogException
     */
    public function match(QueryBuilder $qb)
    {
        $field = $this->getEntityAlias($qb).'.'.$this->fieldName;

        $where = [];
        foreach ($this->value as $i => $value) {
            $where[] = $this->matchOperator($qb, $field, $this->operator, $value, $i);
        }
        $where = array_filter($where);
        $qb->andWhere(
            $qb->expr()->orX(...$where)
        );
    }

    /**
     * @param QueryBuilder $qb
     * @param string $field
     * @param string $operator
     * @param string $value
     * @param int $i
     *
     * @throws WebServerLogException
     *
     * @return \Doctrine\ORM\Query\Expr\Andx|\Doctrine\ORM\Query\Expr\Comparison|\Doctrine\ORM\Query\Expr\Func
     */
    private function matchOperator(QueryBuilder $qb, $field, $operator, $value, $i = 0)
    {
        switch ($operator) {
            case 'eq':
            case 'gt':
            case 'lt':
                $placeholder = ':'.$this->fieldName.'_'.$i;
                $qb->setParameter($placeholder, $value);

                return $qb->expr()->$operator($field, $placeholder);
            case 'regex':
                $placeholder = ':regexp_'.$this->fieldName.'_'.$i;
                $qb->setParameter($placeholder, $value);

                return $qb->expr()->andX("REGEXP({$field}, {$placeholder}) = true");

            case 'like':
                $placeholder = ':like_'.$this->fieldName.'_'.$i;
                $qb->setParameter($placeholder, '%'.$value.'%');

                return $qb->expr()->like($field, $placeholder);

            case 'between':
                list ($from, $to) = explode(',', $value);
                $qb->setParameter('from_'.$i, $from);
                $qb->setParameter('to_'.$i, $to);
                return $qb->expr()->between($field, ':from_'.$i, ':to_'.$i);

            default:
                throw WebServerLogException::unknownFilterOperator($operator);
        }
    }

    /**
     * @param string|array $value
     *
     * @throws WebServerLogException
     */
    private function setValue($value)
    {
        if (!is_array($value) && !is_scalar($value)) {
            throw WebServerLogException::invalidFilterValue($this->fieldName);
        }
        $value = (array) $value;
        $value = array_values($value); // exclude assoc query params

        foreach ($value as $item) {
            if (!is_string($item)) {
                throw WebServerLogException::oneDimensionValue($this->fieldName);
            }
        }
        $this->value = $value;
    }

    /**
     * @param QueryBuilder $qb
     *
     * @throws WebServerLogException
     *
     * @return string
     */
    private function getEntityAlias(QueryBuilder $qb)
    {
        $aliases = $qb->getRootAliases();
        if (count($aliases) !== 1) {
            throw WebServerLogException::invalidQbAlias();
        }
        return array_shift($aliases);
    }
}
