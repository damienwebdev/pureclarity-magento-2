<?php

namespace Pureclarity\Core\Model\Config\Source;

class Categories implements \Magento\Framework\Option\ArrayInterface
{

    protected $categoryRepository;
    protected $coreHelper;
    protected $storeManager;
    protected $coreProductExportFactory;
    protected $logger;
    protected $categories = [
        [
            "label" => "  ",
            "value" => "-1"
        ]
    ];

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Pureclarity\Core\Helper\Data $coreHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Pureclarity\Core\Model\ProductExportFactory $coreProductExportFactory,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->coreHelper = $coreHelper;
        $this->storeManager = $storeManager;
        $this->coreProductExportFactory = $coreProductExportFactory;
        $this->logger = $logger;
    }

    public function buildCategories()
    {
        $rootCategories = [];
        foreach ($this->storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    if (!in_array($store->getRootCategoryId(), $rootCategories)) {
                        $rootCategories[] = $store->getRootCategoryId();
                        $this->GetSubGategories($store->getRootCategoryId());
                    }
                }
            }
        }
    }

    function GetSubGategories($id, $prefix = '')
    {

        $category = $this->categoryRepository->get($id);
        $label = $prefix . $category->getName();
        $this->categories[] = [
            "value" => $category->getId(),
            "label" => $label
        ];
        $subcategories = $category->getChildrenCategories();
        foreach ($subcategories as $subcategory) {
            $this->GetSubGategories($subcategory->getId(), $label . ' -> ');
        }
    }

    public function toOptionArray()
    {
        $this->buildCategories();
        return $this->categories;
    }
}
