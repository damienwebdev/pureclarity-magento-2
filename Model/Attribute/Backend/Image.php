<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Attribute\Backend;
 
use Magento\Catalog\Model\ImageUploader;
use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\App\ObjectManager;
use Psr\Log\LoggerInterface;
use Pureclarity\Core\ImageUpload;

/**
 * Class Image
 *
 * Backend model for category image uploads
 */
class Image extends AbstractBackend
{
    /** @var LoggerInterface $logger */
    private $logger;

    /** @var ImageUploader $imageUploader */
    private $imageUploader;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }
 
    private function getImageUploader()
    {
        if ($this->imageUploader === null) {
            $this->imageUploader = ObjectManager::getInstance()->get(
                ImageUpload::class
            );
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
                    }
                }
            } else {
                $object->setData($attrCode, '');
            }
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
