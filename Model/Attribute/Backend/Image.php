<?php
namespace Pureclarity\Core\Model\Attribute\Backend;
 
class Image extends \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
{
    protected $_uploaderFactory;
    protected $_filesystem;
    protected $_fileUploaderFactory;
    protected $logger;
    private $imageUploader;
 
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory
    ) {
        $this->_filesystem                = $filesystem;
        $this->_fileUploaderFactory       = $fileUploaderFactory;
        $this->logger                     = $logger;
    }
 
    private function getImageUploader()
    {
        if ($this->imageUploader === null) {
            $this->imageUploader = \Magento\Framework\App\ObjectManager::getInstance()->get('Pureclarity\Core\ImageUpload');
        }
        return $this->imageUploader;
    }
 
    public function beforeSave($object)
    {
        $attrCode = $this->getAttribute()->getAttributeCode();
    
        if (!$object->hasData($attrCode)) {
            $object->setData($attrCode, null);
        } else {
            $values = $object->getData($attrCode);
            if (is_array($values)) {
                if (!empty($values['delete'])) {
                    $object->setData($attrCode, null);
                } else {
                    if (isset($values[0]['name']) && isset($values[0]['tmp_name'])) {
                        $object->setData($attrCode, $values[0]['name']);
                    } else {
                        // don't update
                    }
                }
            }
            else
                $object->setData($attrCode, '');
        }
 
        return $this;
    }
 
    public function afterSave($object)
    {
        
        $image = $object->getData($this->getAttribute()->getName(), null);
        if ($image !== null) {
            try {
                $basePath = 'catalog/' . $this->getAttribute()->getAttributeCode();
                $imageUploader = $this->getImageUploader();
                $imageUploader->setBasePath($basePath);
                $imageUploader->moveFileFromTmp($image);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
 
        return $this;
    }
}
