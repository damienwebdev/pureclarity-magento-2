<?php
namespace Pureclarity\Core\Controller\Adminhtml\Bmz;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Pureclarity\Core\Model\CmsBlock;

/**
 * Class Install
 *
 * controller for pureclarity/bmz/install POST request
 */
class Install extends Action
{
    /** @var JsonFactory $resultJsonFactory */
    private $resultJsonFactory;

    /** @var CmsBlock $cmsBlock */
    private $cmsBlock;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CmsBlock $cmsBlock
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CmsBlock $cmsBlock
    ) {
        $this->cmsBlock          = $cmsBlock;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct(
            $context
        );
    }
    
    public function execute()
    {
        session_write_close();

        try {
            $storeId =  (int)$this->getRequest()->getParam('storeid');
            $themeId =  (int)$this->getRequest()->getParam('themeid');
            $result = $this->cmsBlock->install(['bmzs.csv'], $storeId, $themeId);
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
