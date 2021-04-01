<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Console\Command\RunFeed;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PureClarity\Api\Feed\Feed;
use Pureclarity\Core\Model\Feed\Runner;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State;

/**
 * Class Brand
 *
 * Command class to run brand feed via bin/magento pureclarity:runfeed:brand
 */
class Brand extends Command
{
    /** @var Runner $feedRunner */
    private $feedRunner;
    
    /** @var State $state */
    private $state;
    
    /** @var StoreManagerInterface $storeManager */
    private $storeManager;

    /**
     * @param Runner $feedRunner
     * @param State $state
     * @param StoreManagerInterface $storeManager
     * @param string|null $name
     */
    public function __construct(
        Runner $feedRunner,
        State $state,
        StoreManagerInterface $storeManager,
        $name = null
    ) {
        $this->feedRunner   = $feedRunner;
        $this->state        = $state;
        $this->storeManager = $storeManager;
        parent::__construct($name);
    }
    
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('pureclarity:runfeed:brand')
             ->setDescription('Run Brand Data Feed');
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
        
        foreach ($this->storeManager->getStores() as $store) {
            $output->writeln('Running Brand Feed for Store ID ' . $store->getId());
            $this->feedRunner->selectedFeeds($store->getId(), [Feed::FEED_TYPE_BRAND]);
        }
        
        $memUsage = round((memory_get_usage() / 1024) / 1024, 2);
        $memPeak = round((memory_get_peak_usage() / 1024) / 1024, 2);
        $output->writeln('Brand Feed finished, memory usage:');
        $output->writeln('Current: ' . $memUsage . 'Mb');
        $output->writeln('Peak: ' . $memPeak . 'Mb');
    }
}
