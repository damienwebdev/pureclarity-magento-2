<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Pureclarity\Core\Model\Log\Delete;

/**
 * Class LogDelete
 *
 * controller for pureclarity/dashboard/logDelete page
 */
class LogDelete extends Action
{

    /** @var Delete $logDelete */
    private $logDelete;

    /**
     * @param Context $context
     * @param Delete $logDelete
     */
    public function __construct(
        Context $context,
        Delete $logDelete
    ) {
        $this->logDelete = $logDelete;
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            $this->messageManager->addErrorMessage(__('Invalid request, please reload the page and try again'));
        } elseif (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Invalid form key, please reload the page and try again'));
        } elseif ($this->logDelete->deleteLogs()) {
            $this->messageManager->addSuccessMessage(__('Logs deleted successfully'));
        } else {
            $this->messageManager->addErrorMessage(__('Logs failed to delete, please try again.'));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('pureclarity/dashboard/logs');
        return $resultRedirect;
    }
}
