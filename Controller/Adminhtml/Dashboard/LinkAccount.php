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
use Pureclarity\Core\Model\Account\Validate;
use Pureclarity\Core\Model\Signup\AddStore;

/**
 * Class LinkAccount
 *
 * controller for pureclarity/dashboard/linkAccount POST request
 */
class LinkAccount extends Action
{
    /** @var JsonFactory $jsonFactory */
    private $jsonFactory;

    /** @var Process $requestProcess */
    private $requestProcess;

    /** @var Validator $formKeyValidator */
    private $formKeyValidator;

    /** @var Validate $validate */
    private $validate;

    /** @var AddStore $addStore */
    private $addStore;

    /**
     * @param Context $context
     * @param Process $requestProcess
     * @param JsonFactory $jsonFactory
     * @param Validate $validate
     * @param AddStore $addStore
     */
    public function __construct(
        Context $context,
        Process $requestProcess,
        JsonFactory $jsonFactory,
        Validate $validate,
        AddStore $addStore
    ) {
        $this->requestProcess   = $requestProcess;
        $this->jsonFactory      = $jsonFactory;
        $this->formKeyValidator = $context->getFormKeyValidator();
        $this->validate         = $validate;
        $this->addStore         = $addStore;
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
            $params = array_map('trim', $this->getRequest()->getParams());

            if ($params['type'] === 'link') {
                $response = $this->validate->sendRequest($params);
            } else {
                $response = $this->addStore->sendRequest($params);
            }

            if ($response['error']) {
                $result['error'] = $response['error'];
            } else {
                // link existing account
                if ($params['type'] === 'link') {
                    $response = $this->requestProcess->processManualConfigure($params);
                    if ($response['errors']) {
                        $result['error'] = implode(',', $response['errors']);
                    } else {
                        $result['success'] = true;
                    }
                } else {
                    $result['success'] = true;
                }
            }
        }

        $json = $this->jsonFactory->create();
        $json->setData($result);
        return $json;
    }
}
