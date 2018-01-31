<?php
namespace Pureclarity\Core\Controller\Adminhtml\Bmz;

class Install extends \Magento\Backend\App\Action
{

    protected $coreHelper;
    protected $cmsBlock;
    protected $resultJsonFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Pureclarity\Core\Model\CmsBlock $cmsBlock
    ) {
        $this->coreHelper = $coreHelper;
        $this->cmsBlock = $cmsBlock;
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
            $result = $this->cmsBlock->install(['bmzs.csv'],$storeId,$themeId);
            return $this->resultJsonFactory->create()->setData($result);
        }
        catch (\Exception $e){
            $this->getResponse()
                ->clearHeaders()
                ->setHeader('HTTP/1.0', 409, true)
                ->setHeader('Content-Type', 'text/html')
                ->setBody('Error');
        }
    }
}