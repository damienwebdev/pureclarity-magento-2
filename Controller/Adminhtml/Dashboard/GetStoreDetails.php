<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Pureclarity\Core\Helper\StoreData;

/**
 * class GetStoreDetails
 *
 * controller for pureclarity/dashboard/getStoreDetails POST request
 */
class GetStoreDetails extends Action
{
    /** @var JsonFactory $jsonFactory */
    private $jsonFactory;

    /** @var Validator $formKeyValidator */
    private $formKeyValidator;

    /** @var Validator $formKeyValidator */
    private $storeData;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Validator $formKeyValidator
     * @param StoreData $storeData
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Validator $formKeyValidator,
        StoreData $storeData
    ) {
        $this->jsonFactory      = $jsonFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->storeData        = $storeData;
        parent::__construct($context);
    }

    /**
     * Processes the signup request and return json of result
     *
     * @return Json
     */
    public function execute()
    {
        $result = [
            'error' => '',
            'success' => false,
            'store_data' => []
        ];

        $params = $this->getRequest()->getParams();
        if (!$this->getRequest()->isPost()) {
            $result['error'] = __('Invalid request, please reload the page and try again');
        } elseif (!$this->formKeyValidator->validate($this->getRequest())) {
            $result['error'] = __('Invalid form key, please reload the page and try again');
        } elseif (!isset($params['store_id'])) {
            $result['error'] = __('Missing Store ID');
        } else {
            $storeId = (int)$params['store_id'];
            $result['store_data']['currency'] = $this->storeData->getStoreCurrency($storeId);
            $result['store_data']['timezone'] = $this->storeData->getStoreTimezone($storeId);
            $result['store_data']['url'] = $this->storeData->getStoreURL($storeId);
            $result['success'] = true;
        }

        $json = $this->jsonFactory->create();
        $json->setData($result);
        return $json;
    }
}
