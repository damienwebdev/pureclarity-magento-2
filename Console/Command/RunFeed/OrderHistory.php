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
 * Class OrderHistory
 *
 * Command class to run order history feed via bin/magento pureclarity:runfeed:order-history
 */
class OrderHistory extends Command
{
    /** @var CronFactory */
    private $feedRunnerFactory;
    
    /** @var State */
    private $state;
    
    /** @var StoreManagerInterface */
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
        $this->setName('pureclarity:runfeed:order-history')
             ->setDescription('Run Order History Feed');
            
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
                    $output->writeln('Running Order History Feed for Store ID ' . $store->getId());
                    $feedRunner->selectedFeeds($store->getId(), [Feed::FEED_TYPE_ORDER]);
                }
            }
        }
        
        $memUsage = round((memory_get_usage() / 1024) / 1024, 2);
        $memPeak = round((memory_get_peak_usage() / 1024) / 1024, 2);
        $output->writeln('Order History Feed finished, memory usage:');
        $output->writeln('Current: ' . $memUsage . 'Mb');
        $output->writeln('Peak: ' . $memPeak . 'Mb');
    }
}
