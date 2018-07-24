<?php

namespace Pureclarity\Core\Model\Indexer;

class Product implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{

    protected $logger;
    protected $coreHelper;
    protected $productFeedFactory;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Pureclarity\Core\Model\ProductFeedFactory $productFeedFactory
    ) {
        $this->logger = $logger;
        $this->coreHelper = $coreHelper;
        $this->productFeedFactory = $productFeedFactory;
    }

    public function execute($productIds)
    {

        $deltaProduct = $this->productFeedFactory->create();

        if ($productIds == null) {
            // reindexing full product set.
            $deltaProduct->setData(
                [
                    'product_id'    => -1,
                    'token'         => 'product',
                    'status_id'     => 0
                ]
            );
            $deltaProduct->save();
        } else {
            // Reindex specific products
            foreach ($productIds as $productId) {
                $deltaProduct = $this->productFeedFactory->create();
                $deltaProduct->setData(
                    [
                        'product_id'    => $productId,
                        'token'         => '',
                        'status_id'     => 0
                    ]
                );
                $deltaProduct->save();
            }
        }
    }


    public function executeFull()
    {
        $this->execute(null);
    }

    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    public function executeRow($id)
    {
        $this->execute([$id]);
    }
}
