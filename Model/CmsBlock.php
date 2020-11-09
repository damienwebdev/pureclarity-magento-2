<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model;

use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\File\Csv;
use Magento\Widget\Model\Widget\InstanceFactory;
use \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory as WidgetInstanceCollectionFactory;
use \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Class CmsBlock
 *
 * Class for handling Widget related actions
 */
class CmsBlock
{
    /** @var Csv $csvProcessor */
    private $csvProcessor;

    /** @var ComponentRegistrar $componentRegistrar */
    private $componentRegistrar;

    /** @var InstanceFactory $widgetFactory */
    private $widgetFactory;

    /** @var BlockFactory $cmsBlockFactory */
    private $cmsBlockFactory;

    /** @var WidgetInstanceCollectionFactory $appCollectionFactory */
    private $appCollectionFactory;

    /** @var CategoryCollectionFactory $categoryFactory */
    private $categoryFactory;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param Csv $csvProcessor
     * @param ComponentRegistrar $componentRegistrar
     * @param InstanceFactory $widgetFactory
     * @param BlockFactory $cmsBlockFactory
     * @param WidgetInstanceCollectionFactory $appCollectionFactory
     * @param CategoryCollectionFactory $categoryFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Csv $csvProcessor,
        ComponentRegistrar $componentRegistrar,
        InstanceFactory $widgetFactory,
        BlockFactory $cmsBlockFactory,
        WidgetInstanceCollectionFactory $appCollectionFactory,
        CategoryCollectionFactory $categoryFactory,
        LoggerInterface $logger
    ) {
        $this->csvProcessor = $csvProcessor;
        $this->widgetFactory = $widgetFactory;
        $this->cmsBlockFactory = $cmsBlockFactory;
        $this->appCollectionFactory = $appCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->componentRegistrar = $componentRegistrar;
        $this->logger = $logger;
    }

    /**
     * Installs PureClarity BMZs based on the CSV files provided.
     * @param $files array CSV file(s) to be parsed
     * @param $storeId integer The store id
     * @param $themeId integer Theme of the id to install the BMZs for
     */
    public function install(array $files, $storeId, $themeId)
    {
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'Pureclarity_Core')
              . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;

        $pageGroupConfig = [
            'pages' => [
                'block' => '',
                'for' => 'all',
                'layout_handle' => 'default',
                'page_id' => '',
            ],
            'all_products' => [
                'block' => '',
                'for' => 'all',
                'layout_handle' => 'default',
                'page_id' => ''
            ],
            'all_pages' => [
                'block' => '',
                'for' => 'all',
                'layout_handle' => 'default',
                'page_id' => '',
            ],
            'anchor_categories' => [
                'entities' => '',
                'block' => '',
                'for' => 'all',
                'is_anchor_only' => 0,
                'layout_handle' => 'catalog_category_view_type_layered',
                'template' => 'widget/static_block/default.phtml',
                'page_id' => '',
            ],
        ];

        $installed = [];
        $alreadyExists = [];

        foreach ($files as $fileName) {
            try {
                $file = $path . $fileName;
                $rows = $this->csvProcessor->getData($file);
                $header = array_shift($rows);

                foreach ($rows as $row) {
                    $data = [];
                    foreach ($row as $key => $value) {
                        $data[$header[$key]] = $value;
                    }
                    $row = $data;

                    $instanceCollection = $this->appCollectionFactory->create()
                        ->addFilter('title', $row['title'])
                        ->addFilter('theme_id', $themeId)
                        ->addFilter('store_ids', $storeId);
                    if ($instanceCollection->count() > 0) {
                        $alreadyExists[] = $row['title'];
                        continue;
                    }

                    $widgetInstance = $this->widgetFactory->create();

                    $code = $row['type_code'];
                    $type = $widgetInstance->getWidgetReference('code', $code, 'type');
                    $pageGroup = [];
                    $group = $row['page_group'];
                    $pageGroup['page_group'] = $group;

                    $pageGroup[$group] = array_merge(
                        $pageGroupConfig[$group],
                        json_decode($row['group_data'], true)
                    );
                    if (!empty($pageGroup[$group]['entities'])) {
                        $pageGroup[$group]['entities'] = $this->getCategoryByUrlKey(
                            $pageGroup[$group]['entities']
                        )->getId();
                    }

                    $customParameters = ['bmz_id' => $row['bmz_id']];
                    if ($row['bmz_buffer'] && $row['bmz_buffer'] === 'true') {
                        $customParameters['pc_bmz_buffer'] = 1;
                    }

                    $widgetInstance->setType($type)
                        ->setCode($code)
                        ->setThemeId($themeId);
                    $widgetInstance->setTitle($row['title'])
                        ->setStoreIds([$storeId])
                        ->setWidgetParameters($customParameters)
                        ->setSortOrder($row['sort_order'])
                        ->setPageGroups([$pageGroup]);
                    $widgetInstance->save();

                    $installed[] = $row['title'];
                }
            } catch (\Exception $e) {
                $this->logger->error('PureClarity Error installing Zones: ' . $e->getMessage());
            }
        }

        return [
            "alreadyExists" => $alreadyExists,
            "installed" => $installed
        ];
    }

    /**
     * Uninstalls all PureClarity BMZ widgets (Magento db table is widget_instance).
     * Called when PureClarity is uninstalled (/Setup/Uninstall).
     */
    public function uninstall()
    {

        $instanceCollection = $this->appCollectionFactory->create()
            ->addFilter('instance_type', 'Pureclarity\Core\Block\Bmz');
        foreach ($instanceCollection as $widgetInstance) {
            $widgetInstance->delete();
        }
    }

    /**
     * @param string $urlKey
     * @return \Magento\Framework\DataObject
     */
    protected function getCategoryByUrlKey($urlKey)
    {
        $category = $this->categoryFactory->create()
            ->addAttributeToFilter('url_key', $urlKey)
            ->addUrlRewriteToResult()
            ->getFirstItem();
        return $category;
    }
}
