<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Helper;

use Magento\Framework\App\ObjectManager;

/**
 * Class Serialize
 *
 * Wrapper for SerializerInterface to ensure 2.1 compatibility
 */
class Serializer
{
    /**
     * Wrapper for SerializerInterface::serialize to ensure 2.1 compatibility
     * @param mixed $data
     * @return string
     */
    public function serialize($data)
    {
        if (class_exists(\Magento\Framework\Serialize\SerializerInterface::class)) {
            $objectManager = ObjectManager::getInstance();
            $serializer = $objectManager->create(\Magento\Framework\Serialize\SerializerInterface::class);
            return $serializer->serialize($data);
        } else {
            return \json_encode($data);
        }
    }

    /**
     * Wrapper for SerializerInterface::serialize to ensure 2.1 compatibility
     *
     * @param string $data
     * @return mixed
     */
    public function unserialize($data)
    {
        if (class_exists(\Magento\Framework\Serialize\SerializerInterface::class)) {
            $objectManager = ObjectManager::getInstance();
            $serializer = $objectManager->create(\Magento\Framework\Serialize\SerializerInterface::class);
            return $serializer->unserialize($data);
        } else {
            return \json_decode($data, true);
        }
    }
}
