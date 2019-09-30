<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Cron;

use Magento\Framework\Serialize\Serializer\Json;
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

    /** @var Json $json*/
    private $json;

    /** @var StateRepositoryInterface $stateRepository*/
    private $stateRepository;

    /** @var LoggerInterface $logger*/
    private $logger;

    /**
     * @param Url $url
     * @param Curl $curl
     * @param Json $json
     * @param StateRepositoryInterface $stateRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Url $url,
        Curl $curl,
        Json $json,
        StateRepositoryInterface $stateRepository,
        LoggerInterface $logger
    ) {
        $this->url             = $url;
        $this->curl            = $curl;
        $this->json            = $json;
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
            $response = $this->curl->getBody();

            if ($status !== 200) {
                $this->logger->error('PureClarity Check Version cron error: error retrieving latest version number');
            } else {
                $resultData = $this->json->unserialize($response);
                $newVersionState = $this->stateRepository->getByNameAndStore('new_version', 0);
                if (version_compare(Data::CURRENT_VERSION, $resultData['tag_name'], '<')) {
                    $newVersionState->setName('new_version');
                    $newVersionState->setValue($resultData['tag_name']);
                    $this->stateRepository->save($newVersionState);
                } elseif ($newVersionState->getId()) {
                    $this->stateRepository->delete($newVersionState);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('PureClarity Check Version cron error: ' . $e->getMessage());
        }
    }
}
