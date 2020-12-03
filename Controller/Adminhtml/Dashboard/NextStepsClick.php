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
use Pureclarity\Core\Model\NextSteps\Complete;

/**
 * Class NextStepsClick
 *
 * controller for pureclarity/dashboard/nextStepsClick POST request
 */
class NextStepsClick extends Action
{
    /** @var JsonFactory $jsonFactory */
    private $jsonFactory;

    /** @var Validator $formKeyValidator */
    private $formKeyValidator;

    /** @var Complete $complete */
    private $complete;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Complete $complete
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Complete $complete
    ) {
        $this->jsonFactory      = $jsonFactory;
        $this->formKeyValidator = $context->getFormKeyValidator();
        $this->complete         = $complete;
        parent::__construct($context);
    }

    /**
     * Sends a notification to PureClarity that a next step is completed
     *
     * @return Json
     */
    public function execute()
    {
        $result = [
            'error' => '',
            'success' => false
        ];

        $params = $this->getRequest()->getParams();
        if (!$this->getRequest()->isPost()) {
            $result['error'] = __('Invalid request, please reload the page and try again');
        } elseif (!$this->formKeyValidator->validate($this->getRequest())) {
            $result['error'] = __('Invalid form key, please reload the page and try again');
        } elseif (!isset($params['store'])) {
            $result['error'] = __('Missing Store ID');
        } else {
            $this->complete->markNextStepComplete($params['store'], $params['next-step-id']);
        }

        $json = $this->jsonFactory->create();
        $json->setData($result);
        return $json;
    }
}
