<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Account;

use PureClarity\Api\Account\ValidateFactory;

/**
 * Class Validate
 *
 * Model for submitting account validation requests to PureClarity
 */
class Validate
{
    /** @var ValidateFactory $validateFactory */
    private $validateFactory;

    /**
     * @param ValidateFactory $validateFactory
     */
    public function __construct(
        ValidateFactory $validateFactory
    ) {
        $this->validateFactory = $validateFactory;
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
            /** @var \PureClarity\Api\Account\Validate $validateApi */
            $validateApi = $this->validateFactory->create();
            $response = $validateApi->request($params);
            $result = $this->processResponse($response);
        } catch (\Exception $e) {
            $result = [
                'valid_account' => false,
                'error' => __(
                    'PureClarity Link Account Error:' . $e->getMessage()
                )
            ];
        }

        return $result;
    }

    /**
     * Processes a response from the PureClarity Account Validation API call
     * @param mixed[] $response
     * @return mixed[]
     */
    public function processResponse($response)
    {
        $result = [
            'valid_account' => false,
            'error' => ''
        ];

        if (!empty($response['errors'])) {
            $result['error'] = implode($response['errors']);
            return $result;
        }

        if ($response['status'] !== 200) {
            $result['error'] = __(
                'PureClarity server error occurred. If this persists,'
                . 'please contact PureClarity support. Error code %1',
                $response['status']
            );
        } elseif (isset($response['response'])) {
            if ($response['response']['IsValid'] !== true) {
                $result['error'] = __('Account not found, please check your details and try again.');
            }
        } else {
            $result['error'] = __('An error occurred. If this persists, please contact PureClarity support.');
        }

        return $result;
    }
}
