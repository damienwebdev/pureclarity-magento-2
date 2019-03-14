<?php
namespace Pureclarity\Core\Console\Command\RunFeed;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pureclarity\Core\Model\Feed;
use Pureclarity\Core\Model\CronFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\State;

class User extends Command
{
    /** @var \Pureclarity\Core\Model\CronFactory */
    private $feedRunnerFactory;
    
    /** @var \Magento\Framework\App\State */
    private $state;
    
    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;
    
    /**
     * @param \Pureclarity\Core\Model\CronFactory $feedRunner
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
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
        $this->setName('pureclarity:runfeed:user')
             ->setDescription('Run USer Data Feed');
            
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
                    $output->writeln('Running User Feed for Store ID ' . $store->getId());
                    $feedRunner->selectedFeeds($store->getId(), [Feed::FEED_TYPE_USER]);
                }
            }
        }
        
        $mem_usage = memory_get_usage();
        $mem_peak = memory_get_peak_usage();
    }
}
