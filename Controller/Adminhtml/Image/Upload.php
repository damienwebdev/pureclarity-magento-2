<?php
namespace Pureclarity\Core\Controller\Adminhtml\Image;

use Magento\Framework\Controller\ResultFactory;
 
class Upload extends \Magento\Backend\App\Action
{
    protected $imageUploader;
    protected $logger;
 
    public function __construct(
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);
    }
 
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Catalog::categories') ||
            $this->_authorization->isAllowed('Magento_Catalog::products');
    }
 
    public function execute()
    {
        try {
            $attributeCode = $this->getRequest()->getParam('attribute_code');
            if (!$attributeCode) {
                throw new \Exception('attribute_code missing');
            }
            
            $basePath = 'catalog/' . $attributeCode;
            /*
             * Using object manager rather than instantiating \Magento\Catalog\Model\ImageUploader in constructor, 
             * as class does not exist on Magento 2.0, potentially causing setup:di:compile errors. 
             * Creating the Pureclarity\Core\ImageUpload class here, as it then uses arguments set in di.xml rather
             * than needing to e.g. pass in baseTmpPath here.
             */
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $this->imageUploader = $objectManager->create('Pureclarity\Core\ImageUpload');
            $this->imageUploader->setBasePath($basePath);
            $result = $this->imageUploader->saveFileToTmpDir($attributeCode);
 
            $result['cookie'] = [
                'name'  => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path'     => $this->_getSession()->getCookiePath(),
                'domain'   => $this->_getSession()->getCookieDomain(),
            ];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
