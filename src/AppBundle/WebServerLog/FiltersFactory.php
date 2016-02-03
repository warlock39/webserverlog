<?php

namespace AppBundle\WebServerLog;

use AppBundle\WebServerLog\Exception\WebServerLogException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FiltersFactory.
 */
class FiltersFactory
{
    /**
     * @var array
     */
    private $filtersConfig = [];

    /**
     * FiltersFactory constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function createFiltersFromRequest(Request $request)
    {
        $filters = [];

        $queryParams = $request->query->all();
        if (0 === count($queryParams)) {
            return $filters;
        }

        foreach ($queryParams as $name => $value) {
            $filters[] = $this->createFilter($name, $value);
        }

        return array_filter($filters);
    }

    /**
     * @param string       $name
     * @param string|array $value
     *
     * @throws WebServerLogException
     *
     * @return Filter|bool
     */
    private function createFilter($name, $value)
    {
        if (!is_string($name)) {
            return false;
        }
        // such filter is not supported
        if (!array_key_exists($name, $this->filtersConfig)) {
            return false;
        }
        $filterConfig = $this->filtersConfig[$name];
        $operator = array_key_exists('operator', $filterConfig) ? $filterConfig['operator'] : null;

        return new Filter($filterConfig['fieldName'], $value, $operator);
    }

    /**
     * @param array $config
     */
    private function setConfig(array $config)
    {
        $config = array_map([$this, 'normalizeConfig'], $config);
        $config = array_filter($config);

        $queryParams = array_map(function ($item) {
            return $item['queryParam'];
        }, $config);

        $this->filtersConfig = array_combine($queryParams, $config);
    }

    /**
     * @param string|array $item
     *
     * @return array
     */
    protected function normalizeConfig($item)
    {
        if (is_string($item)) {
            $item = [
                'queryParam' => $item,
                'fieldName' => $item
            ];
        }
        if (!is_array($item)) {
            $item = [];
        }
        if (array_key_exists('fieldName', $item)) {
            if (!array_key_exists('queryParam', $item)) {
                $item['queryParam'] = $item['fieldName'];
            }
        } else {
            $item = [];
        }

        return $item;
    }
}
