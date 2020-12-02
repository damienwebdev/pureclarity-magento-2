<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Signup;

use Magento\Framework\Exception\CouldNotSaveException;
use PureClarity\Api\Signup\AddStoreFactory;
use Pureclarity\Core\Api\StateRepositoryInterface;
use Pureclarity\Core\Helper\Serializer;
use Psr\Log\LoggerInterface;

/**
 * Class AddStore
 *
 * Model for submitting an Add Store to account request to PureClarity
 */
class AddStore
{
    /** @var AddStoreFactory $addStoreFactory */
    private $addStoreFactory;

    /** @var Serializer $serializer */
    private $serializer;

    /** @var StateRepositoryInterface $stateRepository */
    private $stateRepository;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param AddStoreFactory $addStoreFactory
     * @param Serializer $serializer
     * @param StateRepositoryInterface $stateRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        AddStoreFactory $addStoreFactory,
        Serializer $serializer,
        StateRepositoryInterface $stateRepository,
        LoggerInterface $logger
    ) {
        $this->addStoreFactory = $addStoreFactory;
        $this->serializer      = $serializer;
        $this->stateRepository = $stateRepository;
        $this->logger          = $logger;
    }

    /**
     * Sends the signup request to PureClarity
     *
     * @param mixed[] $params
     *
     * @return mixed[]
     */
    public function sendRequest($params)
    {
        try {
            $params['platform'] = 'magento2';
            /** @var \PureClarity\Api\Signup\AddStore $addStoreApi */
            $addStoreApi = $this->addStoreFactory->create();
            $response = $addStoreApi->request($params);
            $result = $this->processResponse($response, $params);
        } catch (\Exception $e) {
            $result = [
                'error' => __(
                    'PureClarity Link Account Error:' . $e->getMessage()
                )
            ];
        }

        return $result;
    }

    /**
     * Processes a response from the PureClarity AddStore API call
     * @param mixed[] $response
     * @param mixed[] $params
     * @return mixed[]
     */
    public function processResponse($response, $params)
    {
        $result = [
            'error' => ''
        ];

        if (!empty($response['errors'])) {
            $result['error'] = implode($response['errors']);
            return $result;
        }

        if ($response['status'] === 403) {
            $result['error'] = __(
                'Account not found. Please check your Access Key & Secret Key are correct and try again.'
            );
        } elseif ($response['status'] !== 200) {
            $result['error'] = __(
                'PureClarity server error occurred. If this persists,'
                . 'please contact PureClarity support. Error code %1',
                $response['status']
            );
        } elseif (!isset($response['response'])) {
            $result['error'] = __('An error occurred. If this persists, please contact PureClarity support.');
        } else {
            $this->saveRequest($response, $params);
        }

        return $result;
    }

    /**
     * Saves the request details to state table
     *
     * @param mixed[] $response
     * @param mixed[] $params
     */
    private function saveRequest($response, $params)
    {
        try {
            $signupData = [
                'id' => $response['request_id'],
                'store_id' => $params['store_id'],
                'region' =>  $params['region']
            ];

            $this->serializer->serialize($signupData);

            $state = $this->stateRepository->getByNameAndStore('signup_request', $params['store_id']);
            $state->setName('signup_request');
            $state->setValue($this->serializer->serialize($signupData));
            $state->setStoreId($params['store_id']);

            $this->stateRepository->save($state);
        } catch (CouldNotSaveException $e) {
            $this->logger->error('PureClarity Link Account Error: could not save state:' . $e->getMessage());
        }
    }
}
