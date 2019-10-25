<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Indexer;

use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Pureclarity\Core\Model\ProductFeedFactory;

/**
 * Class Category
 *
 * PureClarity Category indexer
 */
class Category implements ActionInterface, MviewActionInterface
{
    /** @var ProductFeedFactory $productFeedFactory */
    private $productFeedFactory;

    /**
     * @param ProductFeedFactory $productFeedFactory
     */
    public function __construct(
        ProductFeedFactory $productFeedFactory
    ) {
        $this->productFeedFactory = $productFeedFactory;
    }

    public function execute($productIds)
    {

        $deltaProduct = $this->productFeedFactory->create();

        $deltaProduct->setData(
            [
                'product_id'    => -1,
                'token'         => 'category',
                'status_id'     => 0
            ]
        );
        $deltaProduct->save();
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
