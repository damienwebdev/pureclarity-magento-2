<?php
namespace Pureclarity\Core\Controller\Adminhtml\Datafeed;

class Runfeed extends \Magento\Backend\App\Action
{

    protected $coreCronFactory;
    protected $coreHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Pureclarity\Core\Model\CronFactory $coreCronFactory,
        \Pureclarity\Core\Helper\Data $coreHelper
    ) {
        $this->coreCronFactory = $coreCronFactory;
        $this->coreHelper = $coreHelper;
        parent::__construct(
            $context
        );
    }

    
    public function execute()
    {
        session_write_close();
        
        try {
            $storeId =  (int)$this->getRequest()->getParam('storeid');
            $model = $this->coreCronFactory->create();
            $feeds = [];
            if ($this->getRequest()->getParam('product') == 'true') {
                $feeds[] = 'product';
            }
            if ($this->getRequest()->getParam('category') == 'true') {
                $feeds[] = 'category';
            }
            if ($this->getRequest()->getParam('brand') == 'true') {
                $feeds[] = 'brand';
            }
            if ($this->getRequest()->getParam('user') == 'true') {
                $feeds[] = 'user';
            }
            if ($this->getRequest()->getParam('orders') == 'true') {
                $feeds[] = 'orders';
            }
            $model->scheduleSelectedFeeds($storeId, $feeds);
        } catch (\Exception $e) {
            $this->getResponse()
                ->clearHeaders()
            ->setStatusCode(\Magento\Framework\App\Response\Http::STATUS_CODE_500)
                ->setHeader('Content-type', 'text/html')
                ->setBody($e->getMessage());
        }
    }
}
