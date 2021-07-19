<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Zones;

use \Magento\Widget\Model\ResourceModel\Widget\Instance\CollectionFactory;
use Pureclarity\Core\Block\Bmz;
use Psr\Log\LoggerInterface;

/**
 * Class Uninstaller
 *
 * Class for uninstalling Zones
 */
class Uninstaller
{
    /** @var CollectionFactory $collectionFactory */
    private $collectionFactory;

    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->logger            = $logger;
    }

    /**
     * Uninstalls all PureClarity BMZ widgets (Magento db table is widget_instance).
     * Called when PureClarity is uninstalled (/Setup/Uninstall).
     */
    public function uninstall(): void
    {
        try {
            $instanceCollection = $this->collectionFactory->create();
            $instanceCollection->addFilter('instance_type', Bmz::class);

            $widgets = $instanceCollection->getItems();
            foreach ($widgets as $widgetInstance) {
                $widgetInstance->delete();
            }
        } catch (\Exception $e) {
            $this->logger->error('PureClarity error uninstalling Zones: ' . $e->getMessage());
        }
    }
}
