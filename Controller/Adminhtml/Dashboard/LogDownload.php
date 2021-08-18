<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class LogDownload
 *
 * controller for pureclarity/dashboard/logDownload page
 */
class LogDownload extends Action
{
    /** @var FileFactory $fileFactory */
    private $fileFactory;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory
    ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            $this->messageManager->addErrorMessage(__('Invalid request, please reload the page and try again'));
        } elseif (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Invalid form key, please reload the page and try again'));
        } else {
            try {
                return $this->fileFactory->create(
                    'pureclarity.log',
                    [
                        'type' => 'filename',
                        'value' => 'log/pureclarity.log'
                    ],
                    DirectoryList::VAR_DIR
                );
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage('Log failed to download, please try again');
            }
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('pureclarity/dashboard/logs');
        return $resultRedirect;
    }
}
