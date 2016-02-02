<?php

namespace AppBundle\WebServerLog\Serializer;

use AppBundle\WebServerLog\Model\LogEntry;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * Class LogEntryNormalizer.
 */
class LogEntryNormalizer extends SerializerAwareNormalizer implements NormalizerInterface
{
    /**
     * @inheritdoc
     */
    public function normalize($logEntry, $format = null, array $context = [])
    {
        /* @var $logEntry LogEntry */
        $data = [
            'id' => $logEntry->getId(),
             // TODO datetime format should be configurable
            'datetime' => $logEntry->getDatetime()->format('Y-m-d H:i:s'),
            'text' => $logEntry->getText()
        ];


        return $data;
    }

    /**
     * @inheritdoc
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof LogEntry;
    }
}
