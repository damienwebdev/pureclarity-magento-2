<?php
namespace Pureclarity\Core\Controller\Adminhtml\Bmz;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Pureclarity\Core\Model\Zones\Installer;
use Psr\Log\LoggerInterface;

/**
 * Class Install
 *
 * controller for pureclarity/bmz/install POST request
 */
class Install extends Action
{
    /** @var JsonFactory $resultJsonFactory */
    private $resultJsonFactory;

    /** @var Installer $zoneInstaller */
    private $zoneInstaller;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Installer $zoneInstaller
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Installer $zoneInstaller,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->zoneInstaller     = $zoneInstaller;
        $this->logger            = $logger;
        parent::__construct(
            $context
        );
    }
    
    public function execute()
    {
        try {
            $storeId =  (int)$this->getRequest()->getParam('storeid');
            $themeId =  (int)$this->getRequest()->getParam('themeid');
            $result = $this->zoneInstaller->install(
                [
                    'homepage',
                    'product_page',
                    'basket_page',
                    'order_confirmation_page'
                ],
                $storeId,
                $themeId
            );
            $result['success'] = true;
        } catch (\Exception $e) {
            $this->logger->error('PureClarity Zone install error: ' . $e->getMessage());
            $result['success'] = false;
        }

        $json = $this->resultJsonFactory->create();
        $json->setData($result);
        return $json;
    }
}
