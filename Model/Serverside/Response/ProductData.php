<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Serverside\Response;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Product\Compare;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Wishlist\Helper\Data as WishlistHelper;
use Pureclarity\Core\Helper\Serializer;

/**
 * Class ProductData
 *
 * Takes Zone data from PureClarity and supplements it with data from Magento
 */
class ProductData
{
    /** @var string */
    private $currentUrl;

    /** @var string */
    private $currentUrlEncoded;

    /** @var UrlInterface */
    private $encoder;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var Cart */
    private $cartHelper;

    /** @var PostHelper */
    private $postHelper;

    /** @var WishlistHelper */
    private $wishlistHelper;

    /** @var Compare */
    private $compareHelper;

    /** @var Serializer */
    private $serializer;

    /**
     * @param EncoderInterface $encoder
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Cart $cartHelper
     * @param PostHelper $postHelper
     * @param WishlistHelper $wishlistHelper
     * @param Compare $compareHelper
     * @param Serializer $serializer
     */
    public function __construct(
        EncoderInterface $encoder,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Cart $cartHelper,
        PostHelper $postHelper,
        WishlistHelper $wishlistHelper,
        Compare $compareHelper,
        Serializer $serializer
    ) {
        $this->encoder               = $encoder;
        $this->productRepository     = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->cartHelper            = $cartHelper;
        $this->postHelper            = $postHelper;
        $this->compareHelper         = $compareHelper;
        $this->wishlistHelper        = $wishlistHelper;
        $this->serializer            = $serializer;
    }

    /**
     * @param string $currentUrl
     */
    public function setCurrentUrl($currentUrl)
    {
        $this->currentUrl = $currentUrl;
    }

    /**
     * @return string
     */
    public function getCurrentUrlEncoded()
    {
        if ($this->currentUrlEncoded === null) {
            $this->currentUrlEncoded = $this->encoder->encode($this->currentUrl);
        }

        return $this->currentUrlEncoded;
    }

    /**
     * Takes Zone items and overwrites the data with data from Magento
     *
     * @param mixed[] $zone - Array of data from PureClarity for the Zone
     * @return mixed[]
     */
    public function getProductData($zone)
    {
        $skus = [];
        $skuKeys = [];
        foreach ($zone['items'] as $key => $item) {
            $skus[] = $item['Sku'];
            $skuKeys[$item['Sku']] = $key;
        }
        //var_dump(array_keys($zone['items']));

        $this->searchCriteriaBuilder->addFilter('sku', $skus, 'in');
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->productRepository->getList($searchCriteria);

        $newItems = [];

        foreach ($searchResults->getItems() as $product) {
            $sku = $product->getSku();
            $newItems[$skuKeys[$sku]] = $this->populateProductData(
                $product,
                $zone['items'][$skuKeys[$sku]]
            );
        }
        sort($newItems);

        //var_dump(array_keys($newItems));

        return $newItems;
    }

    /**
     * @param ProductInterface $product
     * @param mixed[] $data
     * @return mixed
     */
    public function populateProductData($product, $data)
    {
        $data['price'] = $product->getPrice();
        $data['final_price'] = $product->getFinalPrice(1);
        $data['name'] = $product->getName();
        $data['add_to_cart_post_data'] = $this->getAddToCartPost($product);
        $data['wishlist_post_data'] = $this->getAddToWishlistPost($product);
        $data['compare_post_data'] = $this->getAddToComparePost($product);
        $data['swatchrenderjson'] = isset($data['swatchrenderjson']) ? $data['swatchrenderjson'] : '';
        $data['jsonconfig'] = isset($data['jsonconfig']) ? $data['jsonconfig'] : '';

        return $data;
    }

    /**
     * @param ProductInterface $product
     * @return string
     */
    public function getAddToCartPost($product)
    {
        $addUrl = $this->cartHelper->getAddUrl($product, ['uenc' => $this->getCurrentUrlEncoded()]);
        return $this->postHelper->getPostData($addUrl, ['product' => $product->getId()]);
    }

    /**
     * @param ProductInterface $product
     * @return string
     */
    public function getAddToWishlistPost($product)
    {
        return $this->wishlistHelper->getAddParams($product, ['uenc' => $this->getCurrentUrlEncoded()]);
    }

    /**
     * @param ProductInterface $product
     * @return string
     */
    public function getAddToComparePost($product)
    {
        $comparePost = $this->compareHelper->getPostDataParams($product);
        $comparePostData = $this->serializer->unserialize($comparePost);
        $comparePostData['data']['uenc'] = $this->getCurrentUrlEncoded();
        $comparePost = $this->serializer->serialize($comparePostData);

        return $comparePost;
    }
}
