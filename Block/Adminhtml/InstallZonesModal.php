<?php
/**
 * Copyright © PureClarity. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Pureclarity\Core\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Pureclarity\Core\ViewModel\Adminhtml\Stores;
use Pureclarity\Core\ViewModel\Adminhtml\Themes;

/**
 * Class InstallZonesModal
 *
 * Block for Zones Modal popup
 */
class InstallZonesModal extends Template
{
    protected $_template = 'Pureclarity_Core::install_zones_modal.phtml';

    /** @var Stores $storesViewModel */
    private $storesViewModel;

    /** @var Themes $themesViewModel */
    private $themesViewModel;
    
    public function __construct(
        Context $context,
        Stores $storesViewModel,
        Themes $themesViewModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storesViewModel = $storesViewModel;
        $this->themesViewModel = $themesViewModel;
    }

    /**
     * @return Stores
     */
    public function getPureclarityStoresViewModel()
    {
        return $this->storesViewModel;
    }

    /**
     * @return Themes
     */
    public function getPureclarityThemesViewModel()
    {
        return $this->themesViewModel;
    }
}
