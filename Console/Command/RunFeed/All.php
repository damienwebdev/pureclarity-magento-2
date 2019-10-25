<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Console\Command\RunFeed;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pureclarity\Core\Model\Feed;
use Pureclarity\Core\Model\CronFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State;

/**
 * Class All
 *
 * Command class to run all feed types via bin/magento pureclarity:runfeed:all
 */
class All extends Command
{
    /** @var string[] */
    private $feeds = [
        Feed::FEED_TYPE_PRODUCT,
        Feed::FEED_TYPE_CATEGORY,
        Feed::FEED_TYPE_BRAND,
        Feed::FEED_TYPE_USER,
    ];
    
    /** @var CronFactory $feedRunnerFactory */
    private $feedRunnerFactory;
    
    /** @var State $state */
    private $state;
    
    /** @var StoreManagerInterface $storeManager */
    private $storeManager;
    
    /**
     * @param CronFactory $feedRunnerFactory
     * @param State $state
     * @param StoreManagerInterface $storeManager
     * @param string|null $name
     */
    public function __construct(
        CronFactory $feedRunnerFactory,
        State $state,
        StoreManagerInterface $storeManager,
        $name = null
    ) {
        $this->feedRunnerFactory = $feedRunnerFactory;
        $this->state             = $state;
        $this->storeManager      = $storeManager;
        parent::__construct($name);
    }
    
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('pureclarity:runfeed:all')
             ->setDescription('Run All Data Feeds (Product, Category, Brands, Users)');
            
        parent::configure();
    }
    
    /**
     * {@inheritdoc}
     */
    public function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        try {
            $this->state->setAreaCode('adminhtml');
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
        
        /** @var \Pureclarity\Core\Model\Cron */
        $feedRunner = $this->feedRunnerFactory->create();
        
        foreach ($this->storeManager->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                foreach ($stores as $store) {
                    foreach ($this->feeds as $feedType) {
                        $output->writeln('Running ' . ucfirst($feedType) . ' Feed for Store ID ' . $store->getId());
                        $feedRunner->selectedFeeds($store->getId(), [$feedType]);
                    }
                }
            }
        }
        
        $memUsage = round((memory_get_usage() / 1024) / 1024, 2);
        $memPeak = round((memory_get_peak_usage() / 1024) / 1024, 2);
        $output->writeln('All Feeds finished, memory usage:');
        $output->writeln('Current: ' . $memUsage . 'Mb');
        $output->writeln('Peak: ' . $memPeak . 'Mb');
    }
}
