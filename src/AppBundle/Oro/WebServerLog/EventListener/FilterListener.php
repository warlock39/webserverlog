<?php

namespace AppBundle\Oro\WebServerLog\EventListener;

use AppBundle\Oro\WebServerLog\FiltersFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class FilterListener.
 */
class FilterListener implements EventSubscriberInterface
{
    protected $container;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    /**
     * @param FilterControllerEvent $event A FilterControllerEvent instance
     * @throws \InvalidArgumentException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $filters = $this->container->getParameter('app.webserverlog_controller')['filters'];

        $factory = new FiltersFactory($filters);
        $filters = $factory->createFiltersFromRequest($request); // there is no relation with controller. bad

        $request->attributes->set('_filters', $filters);
    }
    /**
     * Get subscribed events.
     *
     * @return array Subscribed events
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => 'onKernelController'
        );
    }
}
