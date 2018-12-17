<?php

namespace Pureclarity\Core\Model\Product;

use Magento\Backend\Model\Url as BackendUrl;
use Magento\Catalog\Model\Product;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Session\SidResolverInterface;
use Magento\Framework\Url as FrontendUrl;
use Magento\Framework\UrlFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

use \Psr\Log\LoggerInterface;

class Url extends \Magento\Catalog\Model\Product\Url
{
    const FRONTEND_URL = 'Magento\Framework\Url';
    const BACKEND_URL = 'Magento\Backend\Model\Url';

    protected $logger;

    private $backendUrl;
    private $frontendUrl;

    public function __construct(
        UrlFactory $urlFactory,
        StoreManagerInterface $storeManager,
        FilterManager $filter,
        SidResolverInterface $sidResolver,
        UrlFinderInterface $urlFinder,
        LoggerInterface $logger,
        BackendUrl $backendUrl,
        FrontendUrl $frontendUrl,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->backendUrl = $backendUrl;
        $this->frontendUrl = $frontendUrl;
        parent::__construct($urlFactory, $storeManager, $filter, $sidResolver, $urlFinder, $data);
    }

    /**
     * Adapted from \Magento\Catalog\Model\Product\Url->getUrl(), the following
     * copyright notice applies.
     * Copyright © Magento, Inc. All rights reserved.
     * See COPYING.txt for license details.
     */
    public function getUrl(Product $sourceProduct, $parameters = [])
    {
        $routeUrl = '';
        $routeParameters = $parameters;
        $storeId = $sourceProduct->getStoreId();
        $categoryId = $this->getCategoryId( $parameters, $sourceProduct );

        if ( $sourceProduct->hasUrlDataObject() ) {
            $routeParameters['_scope'] = $sourceProduct->getUrlDataObject()->getStoreId();
            $requestUrl = $sourceProduct->getUrlDataObject()->getUrlRewrite();
        } 
        else {
            $requestUrl = $sourceProduct->getRequestPath();
            if ( empty( $requestUrl ) && $requestUrl !== false ) {
                $searchData = [];
                $searchData[UrlRewrite::ENTITY_ID] = $sourceProduct->getId();
                $searchData[UrlRewrite::ENTITY_TYPE] = ProductUrlRewriteGenerator::ENTITY_TYPE;
                $searchData[UrlRewrite::STORE_ID] = $storeId;
                if ($categoryId) {
                    $searchData[UrlRewrite::METADATA]['category_id'] = $categoryId;
                }
                $urlRewrite = $this->urlFinder->findOneByData($searchData);
                if ( $urlRewrite ) {
                    $requestUrl = $urlRewrite->getRequestPath();
                    $sourceProduct->setRequestPath( $requestUrl );
                } 
                else {
                    $sourceProduct->setRequestPath( false );
                }
            }
        }

        if ( isset( $routeParameters['_scope'] ) ) {
            $storeId = $this->storeManager->getStore( $routeParameters['_scope'] )->getId();
        }

        // if ( $storeId != $this->storeManager->getStore()->getId() ) {
        //     $routeParameters['_scope_to_url'] = true;
        // }

        if ( ! empty( $requestUrl ) ) {
            $routeUrl = $requestUrl;
        } 
        else {
            $routeUrl = 'catalog/product/view';
            $routeParameters['id'] = $sourceProduct->getId();
            $routeParameters['s'] = $sourceProduct->getUrlKey();
            if ( $categoryId ) {
                $routeParameters['category'] = $categoryId;
            }
        }

        if ( ! isset( $routeParameters['_query'] ) ) {
            $routeParameters['_query'] = [];
        }

        return $this->getStoreScopeUrlInstance( $storeId )->getUrl( $routeUrl, $routeParameters );
    }

    public function getStoreScopeUrlInstance($storeId)
    {
        return ( $storeId == 0 ? $this->backendUrl : $this->frontendUrl );
    }

    private function getCategoryId($parameters, $sourceProduct) 
    {
        $categoryId = null;

        if ( ! isset( $parameters['_ignore_category'] )  
            && ! $sourceProduct->getDoNotUseCategoryId()
            && $sourceProduct->getCategoryId() ) {
            $categoryId = $sourceProduct->getCategoryId();
        }
        return $categoryId;
    }
}
