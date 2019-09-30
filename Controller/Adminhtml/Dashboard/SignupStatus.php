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
use Pureclarity\Core\Model\Signup\Status as RequestStatus;
use Pureclarity\Core\Model\Signup\Process;

/**
 * Class SignupStatus
 *
 * controller for pureclarity/dashboard/signupStatus GET request
 */
class SignupStatus extends Action
{
    /** @var JsonFactory $jsonFactory */
    private $jsonFactory;

    /** @var RequestStatus $requestStatus */
    private $requestStatus;

    /** @var Process $requestProcess */
    private $requestProcess;

    /**
     * @param Context $context
     * @param RequestStatus $requestStatus
     * @param Process $requestProcess
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        RequestStatus $requestStatus,
        Process $requestProcess,
        JsonFactory $jsonFactory
    ) {
        $this->requestStatus  = $requestStatus;
        $this->requestProcess = $requestProcess;
        $this->jsonFactory    = $jsonFactory;
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

        if (!$this->getRequest()->isGet()) {
            $result['error'] = __('Invalid request, please reload the page and try again');
        } else {
            $response = $this->requestStatus->checkStatus();
            if ($response['complete'] === true) {
                $this->requestProcess->process($response['response']);
                $result['success'] = true;
            } elseif ($response['error']) {
                $result['error'] = $response['error'];
            }
        }

        $json = $this->jsonFactory->create();
        $json->setData($result);
        return $json;
    }
    // TODO: check ACL
}
