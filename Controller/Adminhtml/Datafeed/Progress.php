<?php
namespace Pureclarity\Core\Controller\Adminhtml\Datafeed;

class Progress extends \Magento\Backend\App\Action
{

    protected $coreCronFactory;
    protected $coreHelper;
    protected $resultJsonFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Pureclarity\Core\Model\CronFactory $coreCronFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Pureclarity\Core\Helper\Data $coreHelper
    ) {
        $this->coreCronFactory = $coreCronFactory;
        $this->coreHelper = $coreHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct(
            $context
        );
    }

    
    public function execute()
    {
        session_write_close();
        $contents = "";
        $storeId =  (int)$this->getRequest()->getParam('storeid');
        $progressFileName = $this->coreHelper->getProgressFileName("all");
        if ($progressFileName != null && file_exists($progressFileName)) {
            $contents = file_get_contents($progressFileName);
        }
        return $this->resultJsonFactory->create()->setData(json_decode($contents));
    }
}
