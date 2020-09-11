<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Serverside\Data;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;

/**
 * Serverside Cart handler, gets cart contents and determines if an event needs to be fired
 */
class Cart
{
    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var CustomerSession */
    private $customerSession;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->logger          = $logger;
    }

    /**
     * Checks to see if a basket event needs to be sent
     *
     * @return array
     */
    public function checkCart()
    {
        $lastCart = (string)$this->customerSession->getPureclarityLastCartHash();
        $cart = $this->getCartContents();
        $send = false;

        if ($cart['hash'] !== $lastCart) {
            $this->setCartSessionHash($cart['hash']);
            $send = true;
        }

        return [
            'send' => $send,
            'items' => $cart['items']
        ];
    }

    /**
     * Sets the provided session hash
     * @param string $hash
     */
    public function setCartSessionHash($hash)
    {
        $this->customerSession->setPureclarityLastCartHash($hash);
    }

    /**
     * Gets the contents of the cart from the session
     *
     * @return array
     */
    public function getCartContents()
    {
        $cartHash = '';
        $items = [];

        try {
            $quote = $this->checkoutSession->getQuote();
            $visibleItems = $quote->getAllVisibleItems();
            foreach ($visibleItems as $item) {
                /** @var Item $item */
                $items[$item->getItemId()] = [
                    'id' => $item->getProductId(),
                    'qty' => $item->getQty(),
                    'unitprice' => $item->getPrice(),
                    'children' => []
                ];
                $cartHash .= $item->getProductId() . $item->getQty() . $item->getPrice();
            }

            $allItems = $quote->getAllItems();
            foreach ($allItems as $item) {
                /** @var Item $item */
                if ($item->getParentItemId() && isset($items[$item->getParentItemId()])) {
                    $items[$item->getParentItemId()]['children'][] = ['sku' => $item->getSku(), 'qty' => $item->getQty()];
                    $cartHash .= $item->getProductId() . $item->getQty() . $item->getPrice();
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('PureClarity error: error getting basket contents for event: ' . $e->getMessage());
        }

        return [
            'hash' => $cartHash,
            'items' => array_values($items) ?: []
        ];
    }
}
