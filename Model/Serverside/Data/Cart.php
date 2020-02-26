<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Model\Serverside\Data;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\Quote\Item;

/**
 * Serverside Cart handler, gets cart contents and determines if an event needs to be fired
 */
class Cart
{
    /** @var CheckoutSession */
    private $checkoutSession;

    /** @var CustomerSession */
    private $customerSession;

    /**
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
    }

    /**
     * Checks to see if a basket event needs to be sent
     *
     * @return array
     */
    public function checkCart()
    {
        $lastCart = $this->customerSession->getPureclarityLastCartHash();
        $cart = $this->getCartContents();
        $send = false;

        if (!$cart || $cart['hash'] !== $lastCart) {
            $this->customerSession->setPureclarityLastCartHash($cart['hash']);
            $send = true;
        }

        return [
            'send' => $send,
            'items' => $cart['items']
        ];
    }

    /**
     * Gets the contents of the cart from the session
     *
     * @return array
     */
    public function getCartContents()
    {
        $cartHash = '';
        $quote = $this->checkoutSession->getQuote();
        $visibleItems = $quote->getAllVisibleItems();

        $items = [];
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

        return [
            'hash' => $cartHash,
            'items' => array_values($items) ?: []
        ];
    }
}
