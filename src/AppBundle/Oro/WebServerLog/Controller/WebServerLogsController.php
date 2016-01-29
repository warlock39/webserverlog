<?php

namespace AppBundle\Oro\WebServerLog\Controller;

use AppBundle\Oro\WebServerLog\Model\LogEntry;
use AppBundle\Oro\WebServerLog\Model\LogEntryRepository;
use AppBundle\Oro\WebServerLog\Validator\Constraints\DateTimeBetween;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\FOSRestController;

/**
 * Class WebServerLogsController.
 */
class WebServerLogsController extends FOSRestController
{
    /**
     * @var LogEntryRepository
     */
    protected $repo;

    /**
     * @QueryParam(name="offset", requirements="\d+", default="0", strict=true)
     * @QueryParam(name="limit", requirements="(10|25|50|100)", default="25", strict=true)
     * @QueryParam(name="datetimeBetween", array=true, requirements=@DateTimeBetween, nullable=true, strict=true)
     * @QueryParam(name="datetime", requirements=@Assert\DateTime, nullable=true, strict=true)
     * @QueryParam(name="textLike", array=true, nullable=true)
     * @QueryParam(name="text", array=true, nullable=true)
     * @QueryParam(name="textRegex", array=true, nullable=true)
     *
     * @param Request $request
     *
     * @throws \InvalidArgumentException
     * @throws \LogicException
     *
     * @return JsonResponse
     */
    public function getLogsAction(Request $request)
    {
        $limit = $request->get('limit');
        $offset = $request->get('offset');
        $filters = $request->attributes->get('_filters', []);

        $logs = $this->getRepo()->getLogsByFilters($filters, $limit, $offset);

        return new JsonResponse($logs);
    }

    /**
     * @return LogEntryRepository
     * @throws \LogicException
     */
    private function getRepo()
    {
        if (null === $this->repo) {
            $this->repo = $this->getDoctrine()->getRepository(LogEntry::class);
        }
        return $this->repo;
    }

}