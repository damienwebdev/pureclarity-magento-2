<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Adminhtml\Dashboard;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Regions;
use Pureclarity\Core\ViewModel\Adminhtml\Dashboard\Store;

/**
 * Class Signup
 *
 * Block for Signup content on dashboard page
 */
class Signup extends Template
{
    /** @var Stores $storesViewModel */
    private $storesViewModel;

    /** @var Regions $regionsViewModel */
    private $regionsViewModel;

    /** @var Store $storeViewModel */
    private $storeViewModel;

    /** @var FormKey $formKey */
    private $formKey;

    /**
     * @param Context $context
     * @param Stores $storesViewModel
     * @param Regions $regionsViewModel
     * @param Store $storeViewModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        Stores $storesViewModel,
        Regions $regionsViewModel,
        Store $storeViewModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storesViewModel  = $storesViewModel;
        $this->regionsViewModel = $regionsViewModel;
        $this->storeViewModel   = $storeViewModel;
        $this->formKey          = $context->getFormKey();
    }

    /**
     * @return Stores
     */
    public function getPureclarityStoresViewModel()
    {
        return $this->storesViewModel;
    }

    /**
     * @return Regions
     */
    public function getPureclarityRegionsViewModel()
    {
        return $this->regionsViewModel;
    }

    /**
     * @return Store
     */
    public function getPureclarityStoreViewModel()
    {
        return $this->storeViewModel;
    }

    /**
     * @return string
     */
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }
}
