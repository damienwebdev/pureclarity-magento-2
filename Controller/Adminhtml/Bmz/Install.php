<?php
namespace Pureclarity\Core\Controller\Adminhtml\Bmz;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Pureclarity\Core\Model\Zones\Installer;

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

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Installer $zoneInstaller
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Installer $zoneInstaller
    ) {
        $this->zoneInstaller     = $zoneInstaller;
        $this->resultJsonFactory = $resultJsonFactory;
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
            return $this->resultJsonFactory->create()->setData($result);
        } catch (\Exception $e) {
            $this->getResponse()
                ->clearHeaders()
                ->setHeader('HTTP/1.0', 409, true)
                ->setHeader('Content-Type', 'text/html')
                ->setBody('Error');
        }
    }
}
