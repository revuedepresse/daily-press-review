<?php

namespace WeavingTheWeb\Bundle\MappingBundle\Analyzer;

use WeavingTheWeb\Bundle\Legacy\ProviderBundle\Entity\WeavingHeader,
    WeavingTheWeb\Bundle\MappingBundle\Entity\Property;

/**
 * @package WeavingTheWeb\Bundle\MappingBundle\Analyzer
 */
class EmailHeadersAnalyzer 
{
    /**
     * @var \Doctrine\ORM\EntityManager $entityManager
     */
    public $entityManager;

    /**
     * @var \WeavingTheWeb\Bundle\MappingBundle\Parser\EmailHeadersParser $parser
     */
    public $parser;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    public $logger;

    /**
     * @param $options
     * @return array
     */
    public function aggregateEmailHeadersProperties($options)
    {
        /** @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Repository\WeavingHeaderRepository $headerRepository */
        $headerRepository = $this->entityManager->getRepository('WeavingTheWebLegacyProviderBundle:WeavingHeader');

        $emailHeadersProperties = array();
        while ($options['offset'] <= $options['max_offset']) {
            $headers = $headerRepository->paginate($options['offset'], $options['items_per_page'], $withoutSubject = true);

            /** @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Entity\WeavingHeader $header */
            foreach ($headers as $header) {
                $properties = $this->parser->parse($header->getHdrValue());
                $this->updateHeader($header, $properties);

                foreach ($properties as $name => $value) {
                    $emailHeadersProperties[$name] = $value;
                }

                $this->saveEmailsHeadersAsProperties($emailHeadersProperties);
                $this->logger->info(sprintf('%d headers have been parsed', count($emailHeadersProperties)));

                $memoryPeakUsage = memory_get_peak_usage(true);
                if ($memoryPeakUsage > $options['memory_limit'] * 1024 * 1024) {
                    $this->entityManager->flush();
                    $this->logger->info(sprintf(
                        'Memory limit has been exceeded. Exiting now at offset %d with %d items per page',
                        $options['offset'], $options['items_per_page']
                    ));

                    return $emailHeadersProperties;
                }
            }

            $this->entityManager->flush();

            $options['offset']++;
            $this->logger->info(sprintf('Moving selection cursor with offset set at %d', $options['offset']));
        }

        return $emailHeadersProperties;
    }

    /**
     * @param $emailHeadersProperties
     */
    protected function saveEmailsHeadersAsProperties($emailHeadersProperties)
    {
        /** @var \Doctrine\ORM\EntityRepository $propertyRepository */
        $propertyRepository = $this->entityManager->getRepository('WeavingTheWebMappingBundle:Property');
        foreach ($emailHeadersProperties as $name => $value) {
            $header = $propertyRepository->findOneBy(['name' => $name]);
            if (is_null($header)) {
                $property = new Property();
                $property->setName($name);
                $property->setType($property::TYPE_EMAIL_HEADER);

                $this->entityManager->persist($property);
            }
        }
    }

    /**
     * @param WeavingHeader $header
     * @param $properties
     */
    protected function updateHeader(WeavingHeader $header, $properties)
    {
        if (array_key_exists('From', $properties)) {
            $header->setFrom($properties['From']);
        }
        if (array_key_exists('Subject', $properties)) {
            $header->setSubject($properties['Subject']);
        }
        if (array_key_exists('To', $properties)) {
            $header->setTo($properties['To']);
        }

        $this->entityManager->persist($header);
    }
} 