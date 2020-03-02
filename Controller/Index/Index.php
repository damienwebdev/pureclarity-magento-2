<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Pureclarity\Core\Model\Config\Source\Mode;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\Serverside\Request\Frontend;
use Magento\Store\Model\StoreManagerInterface;

/**
 * PureClarity serverside request controller
 *
 * Sends & Processes a serverside request
 */
class Index extends Action
{
    /** @var JsonFactory $resultJsonFactory */
    private $resultJsonFactory;

    /** @var Frontend */
    private $serverside;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var CoreConfig */
    private $coreConfig;

    /**
     * @param Context $context
     * @param Frontend $serverside
     * @param JsonFactory $resultJsonFactory
     * @param StoreManagerInterface $storeManager
     * @param CoreConfig $coreConfig
     */
    public function __construct(
        Context $context,
        Frontend $serverside,
        JsonFactory $resultJsonFactory,
        StoreManagerInterface $storeManager,
        CoreConfig $coreConfig
    ) {
        $this->serverside        = $serverside;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager      = $storeManager;
        $this->coreConfig        = $coreConfig;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $storeId = $this->storeManager->getStore()->getId();
        if ($this->coreConfig->isActive($storeId)
            && $this->coreConfig->getMode($storeId) === Mode::MODE_SERVERSIDE
        ) {
            $this->serverside->setStoreId($this->storeManager->getStore()->getId());
            $result = $this->serverside->execute($this->getRequest()->getParams());
        } else {
            $result = [];
        }
        return $this->resultJsonFactory->create()->setData($result);
    }
}
