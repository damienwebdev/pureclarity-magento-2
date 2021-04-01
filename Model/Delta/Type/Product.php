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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Pureclarity\Core\Api\ProductFeedRowDataManagementInterface;
use Pureclarity\Core\Model\CoreConfig;
use Magento\Store\Model\App\Emulation;
use Magento\Framework\App\Area;
use PureClarity\Api\Delta\Type\ProductFactory as ProductDeltaFactory;
use Magento\Catalog\Model\Product as ProductModel;

/**
 * Class Product
 *
 * Handles sending Product Deltas
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Product
{
    /** @var StoreInterface */
    private $store;

    /** @var Emulation $appEmulation */
    private $appEmulation;

    /** @var ProductCollectionFactory $collectionFactory */
    private $collectionFactory;

    /** @var ProductDeltaFactory $deltaFactory */
    private $deltaFactory;

    /** @var CoreConfig $coreConfig */
    private $coreConfig;

    /** @var ProductFeedRowDataManagementInterface $productDataHandler */
    private $productDataHandler;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * @param Emulation $appEmulation
     * @param ProductCollectionFactory $collectionFactory
     * @param ProductDeltaFactory $deltaFactory
     * @param CoreConfig $coreConfig
     * @param ProductFeedRowDataManagementInterface $productDataHandler
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Emulation $appEmulation,
        ProductCollectionFactory $collectionFactory,
        ProductDeltaFactory $deltaFactory,
        CoreConfig $coreConfig,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        ProductFeedRowDataManagementInterface $productDataHandler
    ) {
        $this->appEmulation       = $appEmulation;
        $this->collectionFactory  = $collectionFactory;
        $this->deltaFactory       = $deltaFactory;
        $this->coreConfig         = $coreConfig;
        $this->productDataHandler = $productDataHandler;
        $this->logger             = $logger;
        $this->storeManager       = $storeManager;
    }

    /**
     * Runs the product delta for the Product IDs provided
     *
     * @param int $storeId
     * @param string[] $productIds
     */
    public function runDelta(int $storeId, array $productIds): void
    {
        if (count($productIds) > 0) {
            try {
                $store = $this->getStore($storeId);
                $this->appEmulation->startEnvironmentEmulation((int)$store->getId(), Area::AREA_FRONTEND, true);

                $collection = $this->getProductCollection($store, $productIds);

                if ($collection->count() > 0) {
                    $this->processDelta($store, $collection->getItems(), $productIds);
                }

                $this->appEmulation->stopEnvironmentEmulation();
            } catch (\Exception $e) {
                $this->appEmulation->stopEnvironmentEmulation();
                $this->logger->error(
                    'PureClarity: Error running product Deltas: '.
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * Uses the PureClarity PHP SDK to build & send deltas for the provided valid products
     *
     * @param StoreInterface $store
     * @param ProductModel[]|DataObject[] $products
     * @param array $productIds
     */
    public function processDelta(StoreInterface $store, array $products, array $productIds): void
    {
        try {
            $deltaHandler = $this->deltaFactory->create([
                'accessKey' => $this->coreConfig->getAccessKey((int)$store->getId()),
                'secretKey' => $this->coreConfig->getSecretKey((int)$store->getId()),
                'region' => $this->coreConfig->getRegion((int)$store->getId())
            ]);

            foreach ($productIds as $productId) {
                $product = $products[$productId] ?? null;
                if ($product === null || $this->isProductHidden($product)) {
                    $deltaHandler->addDelete((int)$productId);
                } else {
                    $data = $this->productDataHandler->getRowData($store, $product);
                    if (empty($data)) {
                        $deltaHandler->addDelete((int)$productId);
                    } else {
                        $deltaHandler->addData($data);
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
     * @param StoreInterface $store
     * @param array $productIds
     * @return Collection
     */
    public function getProductCollection(StoreInterface $store, array $productIds): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->setStoreId((int)$store->getId());
        $collection->addStoreFilter($store);
        $collection->addUrlRewrite();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('entity_id', $productIds);
        $collection->addMinimalPrice();
        $collection->addTaxPercents();
        return $collection;
    }

    /**
     * Gets a Store object for the given store ID
     * @param int $storeId
     * @return StoreInterface|Store
     * @throws NoSuchEntityException
     */
    private function getStore(int $storeId): StoreInterface
    {
        if ($this->store === null) {
            $this->store = $this->storeManager->getStore($storeId);
        }
        return $this->store;
    }
}
