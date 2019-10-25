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
use Pureclarity\Core\Model\Signup\Process;

/**
 * Class Configure
 *
 * controller for pureclarity/dashboard/configure POST request
 */
class Configure extends Action
{
    /** @var JsonFactory $jsonFactory */
    private $jsonFactory;

    /** @var Process $requestProcess */
    private $requestProcess;

    /** @var Validator $formKeyValidator */
    private $formKeyValidator;

    /**
     * @param Context $context
     * @param Process $requestProcess
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        Process $requestProcess,
        JsonFactory $jsonFactory
    ) {
        $this->requestProcess   = $requestProcess;
        $this->jsonFactory      = $jsonFactory;
        $this->formKeyValidator = $context->getFormKeyValidator();
        parent::__construct($context);
    }

    /**
     * Checks the status of a signup request and if complete, triggers auto-configuration
     *
     * @return Json
     */
    public function execute()
    {
        $result = [
            'error' => '',
            'success' => false
        ];

        if (!$this->getRequest()->isPost()) {
            $result['error'] = __('Invalid request, please reload the page and try again');
        } elseif (!$this->formKeyValidator->validate($this->getRequest())) {
            $result['error'] = __('Invalid form key, please reload the page and try again');
        } else {
            $response = $this->requestProcess->processManualConfigure($this->getRequest()->getParams());
            if ($response['errors']) {
                $result['error'] = implode(',', $response['errors']);
            } else {
                $result['success'] = true;
            }
        }

        $json = $this->jsonFactory->create();
        $json->setData($result);
        return $json;
    }
}
