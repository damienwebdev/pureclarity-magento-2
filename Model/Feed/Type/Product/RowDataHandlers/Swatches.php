<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Feed\Type\Product\RowDataHandlers;

use Magento\Framework\View\Element\BlockFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Swatches\Block\Product\Renderer\Listing\Configurable;

/**
 * Class Swatches
 *
 * Gets swatch data for the given product
 */
class Swatches
{
    /** @var BlockFactory $blockFactory */
    private $blockFactory;

    /** @var SerializerInterface */
    private $serializer;

    /**
     * @param BlockFactory $blockFactory
     * @param SerializerInterface $serializer
     */
    public function __construct(
        BlockFactory $blockFactory,
        SerializerInterface $serializer
    ) {
        $this->blockFactory = $blockFactory;
        $this->serializer   = $serializer;
    }

    /**
     * Gets the swatch data for a given store & product
     * @param StoreInterface $store
     * @param Product|ProductInterface $product
     * @return array
     */
    public function getSwatchData(StoreInterface $store, $product): array
    {
        /** @var Configurable $swatchBlock */
        $swatchBlock = $this->blockFactory->createBlock(Configurable::class);
        $swatchBlock->setData('product', $product);
        $jsonConfig = $swatchBlock->getJsonConfig();

        return [
            'jsonconfig' => $jsonConfig,
            'swatchrenderjson' => $this->serializer->serialize([
                'selectorProduct' => '.product-item-details',
                'onlySwatches' => true,
                'enableControlLabel' => false,
                'numberToShow' => $swatchBlock->getNumberSwatchesPerProduct(),
                'jsonConfig' => $this->serializer->unserialize($jsonConfig),
                'jsonSwatchConfig' => $this->serializer->unserialize($swatchBlock->getJsonSwatchConfig()),
                'mediaCallback' => $store->getBaseUrl() . 'swatches/ajax/media/'
            ])
        ];
    }
}
