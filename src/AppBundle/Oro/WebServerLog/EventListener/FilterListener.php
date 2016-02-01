<?php

namespace AppBundle\Oro\WebServerLog\EventListener;

use AppBundle\Oro\WebServerLog\Controller\FiltersAwareController;
use AppBundle\Oro\WebServerLog\Exception\WebServerLogException;
use AppBundle\Oro\WebServerLog\FiltersFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
     * @throws BadRequestHttpException
     * @throws \InvalidArgumentException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $controller = $event->getController();

        // TODO can $controller be not array-callable?
        if (!$controller[0] instanceof FiltersAwareController) {
            return;
        }

        $factory = new FiltersFactory($controller[0]->getFiltersConfig());
        try {
            $filters = $factory->createFiltersFromRequest($request);
        } catch (WebServerLogException $e) {
            // TODO may be use BadRequestHttpException inside FiltersFactory?
            throw new BadRequestHttpException($e->getMessage());
        }

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
