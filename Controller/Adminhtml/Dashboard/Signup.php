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
use Pureclarity\Core\Model\Signup\Request;

/**
 * Class Signup
 *
 * controller for pureclarity/dashboard/signup POST request
 */
class Signup extends Action
{
    /** @var JsonFactory $jsonFactory */
    private $jsonFactory;

    /** @var Request $signupRequest */
    private $signupRequest;

    /** @var Validator $formKeyValidator */
    private $formKeyValidator;

    /**
     * @param Context $context
     * @param Request $signupRequest
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        Request $signupRequest,
        JsonFactory $jsonFactory
    ) {
        $this->signupRequest    = $signupRequest;
        $this->jsonFactory      = $jsonFactory;
        $this->formKeyValidator = $context->getFormKeyValidator();
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
            'success' => false
        ];

        if (!$this->getRequest()->isPost()) {
            $result['error'] = __('Invalid request, please reload the page and try again');
        } elseif (!$this->formKeyValidator->validate($this->getRequest())) {
            $result['error'] = __('Invalid form key, please reload the page and try again');
        } else {
            $params = $this->getRequest()->getParams();
            $response = $this->signupRequest->sendRequest($params);
            if ($response['error']) {
                $result['error'] = $response['error'];
            } else {
                $result['success'] = true;
            }
        }

        $json = $this->jsonFactory->create();
        $json->setData($result);
        return $json;
    }
}
