<?php
namespace Pureclarity\Core\Helper;

class Sftp extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $logger;
    protected $sftp;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem\Io\Sftp $sftp
    ) {
        $this->sftp = $sftp;
        $this->logger = $logger;
        parent::__construct(
            $context
        );
    }
    public function send($host, $port, $username, $password, $filename, $payload, $directory = null)
    {
        $path = '/' . ($directory?$directory.'/':'') . $filename;
        $success = true;
        try {
            $this->sftp->open(["host"=>($host.":".$port),"username"=>$username, "password"=>$password]);
            $this->sftp->write($path, $payload);
        } catch (\Exception $e) {
            $this->logger->error("ERROR: Processing PureClarity SFTP transfer. See Exception log for details.");
            $this->logger->critical($e);
            $success = false;
        }
        $this->sftp->close();
        return $success;
    }
}
