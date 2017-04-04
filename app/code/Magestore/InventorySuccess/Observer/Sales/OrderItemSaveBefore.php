<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Observer\Sales;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class OrderItemSaveBefore implements ObserverInterface {

    /**
     *
     * @var \Magento\Framework\ObjectManagerInterface  
     */
    protected $_objectManager;

    /**
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager, 
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_objectManager = $objectManager;
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * @param EventObserver $observer
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(EventObserver $observer) {
        $orderItem = $observer->getEvent()->getItem();
        $beforeItem = $this->_objectManager->create('Magento\Sales\Model\Order\Item');
        if($orderItem->getId()) {
            $beforeItem->load($orderItem->getId());
        }
        if (!$this->_coreRegistry->registry('os_beforeOrderItem' . $orderItem->getId())) {
            $this->_coreRegistry->register('os_beforeOrderItem' . $orderItem->getId(), $beforeItem);
        }
    }

}
