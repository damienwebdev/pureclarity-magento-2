<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Zones;

use Exception;
use Magento\Widget\Model\Widget\InstanceFactory;
use \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\Block\Bmz;

/**
 * Class Installer
 *
 * Class for installing Zones
 */
class Installer
{
    /** @var array - Installed Zones */
    private $installed = [];

    /** @var array - Existing Zones */
    private $existing = [];

    /** @var InstanceFactory $widgetFactory */
    private $widgetFactory;

    /** @var CollectionFactory $collectionFactory */
    private $collectionFactory;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param InstanceFactory $widgetFactory
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        InstanceFactory $widgetFactory,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger
    ) {
        $this->widgetFactory     = $widgetFactory;
        $this->collectionFactory = $collectionFactory;
        $this->logger            = $logger;
    }

    /**
     * Installs PureClarity Zones for the given types
     *
     * @param $types array zone types to bbe installed
     * @param $storeId integer The store id
     * @param $themeId integer Theme of the id to install the BMZs for
     */
    public function install(array $types, int $storeId, int $themeId): array
    {
        foreach ($types as $type) {
            $this->installZones($type, $storeId, $themeId);
        }

        return [
            'alreadyExists' => $this->existing,
            'installed' => $this->installed
        ];
    }

    /**
     * Installs PureClarity Zones for the given type
     *
     * @param string $type
     * @param int $storeId
     * @param int $themeId
     */
    public function installZones(string $type, int $storeId, int $themeId): void
    {
        try {
            $zones = $this->getTypeZones($type);
            foreach ($zones as $zoneId => $zone) {
                if ($this->doesZoneExist($zone['title'], $storeId, $themeId)) {
                    $this->existing[] = $zone['title'];
                    continue;
                }

                $this->createZone($zoneId, $zone, $storeId, $themeId);
                $this->installed[] = $zone['title'];
            }
        } catch (Exception $e) {
            $this->logger->error('PureClarity Error installing ' . $type . ' Zones: ' . $e->getMessage());
        }
    }

    /**
     * Creates an individual Zone by creating the widget instance.
     *
     * @param string $zoneId
     * @param array $zone
     * @param int $storeId
     * @param int $themeId
     * @throws Exception
     */
    public function createZone(string $zoneId, array $zone, int $storeId, int $themeId): void
    {
        $widgetInstance = $this->widgetFactory->create();

        $type = $widgetInstance->getWidgetReference(
            'code',
            Bmz::WIDGET_ID,
            'type'
        );

        $widgetInstance->setType($type);
        $widgetInstance->setCode(Bmz::WIDGET_ID);
        $widgetInstance->setThemeId($themeId);
        $widgetInstance->setTitle($zone['title']);
        $widgetInstance->setStoreIds([$storeId]);
        $widgetInstance->setWidgetParameters([
            'bmz_id' => $zoneId,
            'pc_bmz_buffer' => $zone['bmz_buffer'] ? 1 : 0
        ]);
        $widgetInstance->setSortOrder($zone['sort_order']);

        $widgetInstance->setPageGroups([
            [
                'page_group' => $zone['page_group'],
                $zone['page_group'] => [
                    'block' => $zone['group_data']['block'],
                    'for' => 'all',
                    'layout_handle' => $zone['group_data']['layout_handle'],
                    'page_id' => '',
                ]
            ]
        ]);

        $widgetInstance->save();
    }

    /**
     * Checks if a Zone is already installed on this store / theme.
     *
     * @param string $title
     * @param int $storeId
     * @param int $themeId
     * @return bool
     */
    public function doesZoneExist(string $title, int $storeId, int $themeId): bool
    {
        $instanceCollection = $this->collectionFactory->create();
        $instanceCollection->addFilter('title', $title);
        $instanceCollection->addFilter('theme_id', $themeId);
        $instanceCollection->addFilter('store_ids', $storeId);

        return $instanceCollection->count() > 0;
    }

    /**
     * Gets the Zone info for the given type.
     * @param $type
     * @return array|array[]
     */
    public function getTypeZones($type): array
    {
        $zones = $this->getZones();
        return $zones[$type] ?? [];
    }

    /**
     * Returns all default Zone info.
     *
     * @return array[][]
     */
    public function getZones(): array
    {
        return [
            'homepage' => [
                'HP-01' => [
                    'title' => 'PC Zone HP-01',
                    'sort_order' => 0,
                    'page_group' => 'pages',
                    'group_data' => [
                        'block' => 'content.bottom',
                        'layout_handle' => 'cms_index_index',
                    ],
                    'bmz_buffer' => false,
                ],
                'HP-02' => [
                    'title' => 'PC Zone HP-02',
                    'sort_order' => 1,
                    'page_group' => 'pages',
                    'group_data' => [
                        'block' => 'content.bottom',
                        'layout_handle' => 'cms_index_index',
                    ],
                    'bmz_buffer' => false,
                ],
                'HP-03' => [
                    'title' => 'PC Zone HP-03',
                    'sort_order' => 2,
                    'page_group' => 'pages',
                    'group_data' => [
                        'block' => 'content.bottom',
                        'layout_handle' => 'cms_index_index',
                    ],
                    'bmz_buffer' => false,
                ],
                'HP-04' => [
                    'title' => 'PC Zone HP-04',
                    'sort_order' => 3,
                    'page_group' => 'pages',
                    'group_data' => [
                        'block' => 'content.bottom',
                        'layout_handle' => 'cms_index_index',
                    ],
                    'bmz_buffer' => false,
                ]
            ],
            'product_page' => [
                'PP-01' => [
                    'title' => 'PC Zone PP-01',
                    'sort_order' => 0,
                    'page_group' => 'all_products',
                    'group_data' => [
                        'block' => 'content.bottom',
                        'layout_handle' => 'default'
                    ],
                    'bmz_buffer' => false,
                ],
                'PP-02' => [
                    'title' => 'PC Zone PP-02',
                    'sort_order' => 1,
                    'page_group' => 'all_products',
                    'group_data' => [
                        'block' => 'content.bottom',
                        'layout_handle' => 'default'
                    ],
                    'bmz_buffer' => false,
                ],
            ],
            'basket_page' => [
                'BP-01' => [
                    'title' => 'PC Zone BP-01',
                    'sort_order' => 0,
                    'page_group' => 'pages',
                    'group_data' => [
                        'block' => 'content.bottom',
                        'layout_handle' => 'checkout_cart_index',
                    ],
                    'bmz_buffer' => false,
                ],
                'BP-02' => [
                    'title' => 'PC Zone BP-02',
                    'sort_order' => 1,
                    'page_group' => 'pages',
                    'group_data' => [
                        'block' => 'content.bottom',
                        'layout_handle' => 'checkout_cart_index',
                    ],
                    'bmz_buffer' => false,
                ],
            ],
            'order_confirmation_page' => [
                'OC-01' => [
                    'title' => 'PC Zone OC-01',
                    'sort_order' => 0,
                    'page_group' => 'pages',
                    'group_data' => [
                        'block' => 'content.bottom',
                        'layout_handle' => 'checkout_onepage_success',
                    ],
                    'bmz_buffer' => true,
                ],
                'OC-02' => [
                    'title' => 'PC Zone OC-02',
                    'sort_order' => 1,
                    'page_group' => 'pages',
                    'group_data' => [
                        'block' => 'content.bottom',
                        'layout_handle' => 'checkout_onepage_success',
                    ],
                    'bmz_buffer' => false,
                ],
            ],
        ];
    }
}
