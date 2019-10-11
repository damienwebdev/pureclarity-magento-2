<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Cron;

use Pureclarity\Core\Helper\Serializer;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Service\Url;
use Magento\Framework\HTTP\Client\Curl;
use Pureclarity\Core\Helper\Data;

/**
 * Class CheckVersion
 *
 * Checks the PureClarity github for a new version
 */
class CheckVersion
{
    /** @var Url $url*/
    private $url;

    /** @var Curl $curl*/
    private $curl;

    /** @var Serializer $serializer*/
    private $serializer;

    /** @var StateRepositoryInterface $stateRepository*/
    private $stateRepository;

    /** @var LoggerInterface $logger*/
    private $logger;

    /**
     * @param Url $url
     * @param Curl $curl
     * @param Serializer $serializer
     * @param StateRepositoryInterface $stateRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Url $url,
        Curl $curl,
        Serializer $serializer,
        StateRepositoryInterface $stateRepository,
        LoggerInterface $logger
    ) {
        $this->url             = $url;
        $this->curl            = $curl;
        $this->serializer      = $serializer;
        $this->stateRepository = $stateRepository;
        $this->logger          = $logger;
    }

    /**
     * Checks the released version against the installed version to see if there are updates
     * called via cron every night at 2am (see /etc/crontab.xml)
     */
    public function execute()
    {
        $url = $this->url->getGithubUrl();

        try {
            $this->curl->setTimeout(5);
            $this->curl->setOption(
                CURLOPT_USERAGENT,
                'Magento 2 Extension, version' . DATA::CURRENT_VERSION
            );
            $this->curl->get($url);
            $status = $this->curl->getStatus();

            if ($status !== 200) {
                $this->logger->error(
                    'PureClarity Check Version cron error: error retrieving latest version number.'
                    . ' Response code ' . $status
                );
            } else {
                $response = $this->curl->getBody();
                $resultData = $this->serializer->unserialize($response);
                if (!isset($resultData['tag_name'])) {
                    $this->logger->error(
                        'PureClarity Check Version cron error: error retrieving '
                        . 'latest version number, bad response format'
                    );
                } else {
                    $newVersionState = $this->stateRepository->getByNameAndStore('new_version', 0);
                    if (version_compare(Data::CURRENT_VERSION, $resultData['tag_name'], '<')) {
                        $newVersionState->setName('new_version');
                        $newVersionState->setValue($resultData['tag_name']);
                        $newVersionState->setStoreId(0);
                        $this->stateRepository->save($newVersionState);
                    } elseif ($newVersionState->getId()) {
                        $this->stateRepository->delete($newVersionState);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('PureClarity Check Version cron error: ' . $e->getMessage());
        }
    }
}
