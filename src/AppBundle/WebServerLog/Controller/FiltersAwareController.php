<?php

namespace AppBundle\WebServerLog\Controller;

/**
 * Interface FiltersAwareController.
 */
interface FiltersAwareController
{
    /**
     * Gets controller filters config
     *
     * @return []
     */
    public function getFiltersConfig();
}
