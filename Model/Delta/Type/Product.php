<?php
declare(strict_types=1);
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Delta\Type;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Pureclarity\Core\Model\CoreConfig;
use Pureclarity\Core\Model\ProductExportFactory;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\App\Area;
use PureClarity\Api\Delta\Type\ProductFactory as ProductDeltaFactory;
use Magento\Catalog\Model\Product as ProductModel;

/**
 * Class Product
 *
 * Handles sending Product Deltas
 */
class Product
{
    /** @var Emulation $appEmulation */
    private $appEmulation;

    /** @var ProductCollectionFactory $productCollectionFactory */
    private $productCollectionFactory;

    /** @var ProductDeltaFactory $deltaFactory */
    private $deltaFactory;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var ProductExportFactory $productExportFactory */
    private $productExportFactory;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param Emulation $appEmulation
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductDeltaFactory $deltaFactory
     * @param CoreConfig $coreConfig
     * @param ProductExportFactory $productExportFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Emulation $appEmulation,
        ProductCollectionFactory $productCollectionFactory,
        ProductDeltaFactory $deltaFactory,
        CoreConfig $coreConfig,
        ProductExportFactory $productExportFactory,
        LoggerInterface $logger
    ) {
        $this->appEmulation             = $appEmulation;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->deltaFactory             = $deltaFactory;
        $this->coreConfig               = $coreConfig;
        $this->productExportFactory     = $productExportFactory;
        $this->logger                   = $logger;
    }

    /**
     * Runs the product delta for the Product IDs provided
     *
     * @param int $storeId
     * @param string[] $productIds
     */
    public function runDelta(int $storeId, array $productIds): void
    {
        $this->appEmulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);

        $products = $this->loadDeltaProducts($storeId, $productIds);

        if ($products->count() > 0 || count($productIds) > 0) {
            $this->processDelta($storeId, $products->getItems(), $productIds);
        }

        $this->appEmulation->stopEnvironmentEmulation();
    }

    /**
     * Uses the PureClarity PHP SDK to build & send deltas for the provided valid products
     *
     * @param int $storeId
     * @param ProductModel[]|DataObject[] $products
     * @param array $productIds
     */
    public function processDelta(int $storeId, array $products, array $productIds): void
    {
        try {
            $deltaHandler = $this->deltaFactory->create([
                'accessKey' => $this->coreConfig->getAccessKey($storeId),
                'secretKey' => $this->coreConfig->getSecretKey($storeId),
                'region' => $this->coreConfig->getRegion($storeId)
            ]);

            $productExportModel = $this->productExportFactory->create();
            $productExportModel->init($storeId);

            $index = 0;
            foreach ($productIds as $productId) {
                $product = $products[$productId] ?? null;
                if ($product === null || $this->isProductHidden($product)) {
                    $deltaHandler->addDelete((int)$productId);
                } else {
                    $data = $productExportModel->processProduct(
                        $product,
                        $index
                    );
                    if ($data !== null) {
                        $deltaHandler->addData($data);
                        $index++;
                    } else {
                        $deltaHandler->addDelete((int)$productId);
                    }
                }
            }

            $deltaHandler->send();
        } catch (\Exception $e) {
            $this->logger->error(
                'PureClarity: Error processing product Deltas: '.
                $e->getMessage()
            );
        }
    }

    /**
     * Returns whether a product is hidden on the site.
     *
     * @param ProductModel $product
     * @return bool
     */
    public function isProductHidden(ProductModel $product): bool
    {
        return $product->getData('status') === Status::STATUS_DISABLED ||
            $product->getVisibility() === Visibility::VISIBILITY_NOT_VISIBLE;
    }

    /**
     * Loads product information for the provided product IDs
     *
     * @param int $storeId
     * @param array $productIds
     * @return Collection
     */
    public function loadDeltaProducts(int $storeId, array $productIds): Collection
    {
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addStoreFilter($storeId);
        $collection->addUrlRewrite();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('entity_id', $productIds);
        $collection->addMinimalPrice();
        $collection->addTaxPercents();
        return $collection;
    }
}
