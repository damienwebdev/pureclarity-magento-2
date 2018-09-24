<?php

namespace Pureclarity\Core\Model;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Component\ComponentRegistrar;

class CmsBlock
{

    protected $categoryFactory;
    protected $widgetFactory;
    protected $themeCollectionFactory;
    protected $cmsBlockFactory;
    protected $appCollectionFactory;
    protected $fixtureManager;
    protected $csvProcessor;
    private $serializer;
    protected $componentRegistrar;
    protected $logger;

    public function __construct(
        \Magento\Framework\File\Csv $csvProcessor,
        ComponentRegistrar $componentRegistrar,
        \Magento\Widget\Model\Widget\InstanceFactory $widgetFactory,
        \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory $themeCollectionFactory,
        \Magento\Cms\Model\BlockFactory $cmsBlockFactory,
        \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory $appCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryFactory,
        \Psr\Log\LoggerInterface $logger,
        Json $serializer = null
    ) {
        $this->csvProcessor = $csvProcessor;
        $this->widgetFactory = $widgetFactory;
        $this->themeCollectionFactory = $themeCollectionFactory;
        $this->cmsBlockFactory = $cmsBlockFactory;
        $this->appCollectionFactory = $appCollectionFactory;
        $this->categoryFactory = $categoryFactory;
        $this->componentRegistrar = $componentRegistrar;
        $this->logger = $logger;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Json::class);
    }


    /**
     * Installs PureClarity BMZs based on the CSV files provided.
     * @param $files array CSV file(s) to be parsed
     * @param $storeId integer The store id
     * @param $themeId integer Theme of the id to install the BMZs for
     */
    public function install(array $files, $storeId, $themeId)
    {
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'Pureclarity_Core') . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
        
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
            $file = $path . $fileName;
            if (!file_exists($file)) {
                continue;
            }

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
                    $this->serializer->unserialize($row['group_data'])
                );
                if (!empty($pageGroup[$group]['entities'])) {
                    $pageGroup[$group]['entities'] = $this->getCategoryByUrlKey(
                        $pageGroup[$group]['entities']
                    )->getId();
                }

                $customParameters = ['bmz_id' => $row['bmz_id']];
                if ($row['bmz_buffer'] && $row['bmz_buffer'] == 'true') {
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
    public function uninstall(){

        $instanceCollection = $this->appCollectionFactory->create()
            ->addFilter('instance_type', 'Pureclarity\Core\Block\Bmz');
        foreach($instanceCollection as $widgetInstance){
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
