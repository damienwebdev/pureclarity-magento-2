<?php
namespace Pureclarity\Core\Cron;

use Pureclarity\Core\Helper\Data;
use Pureclarity\Core\Model\Cron;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Controls the execution of scheduled feeds to be sent to PureClarity.
 */
class RunScheduled
{
    /** @var \Pureclarity\Core\Helper\Data */
    private $coreHelper;
    
    /** @var \Pureclarity\Core\Model\Cron */
    private $feedRunner;
    
    /** @var \Magento\Framework\Filesystem */
    private $fileSystem;

    public function __construct(
        Data $coreHelper,
        Cron $feedRunner,
        Filesystem $fileSystem
    ) {
        $this->coreHelper = $coreHelper;
        $this->feedRunner = $feedRunner;
        $this->fileSystem = $fileSystem;
    }

    /**
     * Runs feeds that have been scheduled by a button press in admin
     * called via cron every minute (see /etc/crontab.xml)
     */
    public function execute()
    {
        $scheduleFile = $this->coreHelper->getPureClarityBaseDir() . DIRECTORY_SEPARATOR . 'scheduled_feed';
        
        $fileReader = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);
        
        if ($fileReader->isExist($scheduleFile)) {
            $scheduleData = $fileReader->readFile($scheduleFile);
            $schedule = (array)json_decode($scheduleData);
            $fileWriter = $this->fileSystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $fileWriter->delete($scheduleFile);
            var_dump($schedule);
            if (!empty($schedule) && isset($schedule['store']) && isset($schedule['feeds'])) {
                $this->feedRunner->selectedFeeds($schedule['store'], $schedule['feeds']);
            }
        }
    }
}
