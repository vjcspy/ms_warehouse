<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Observer\LowStockNotification;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class Notification implements ObserverInterface
{

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $this->notification();
    }

    /**
     * notification
     */
    public function notification()
    {
        /** @var \Magestore\InventorySuccess\Model\LowStockNotification\RuleManagement $ruleManagement */
        $ruleManagement = \Magento\Framework\App\ObjectManager::getInstance()->create(
            '\Magestore\InventorySuccess\Model\LowStockNotification\RuleManagement'
        );
        $availableRules = $ruleManagement->getAvailableRules();
        if (count($availableRules)) {
            foreach ($availableRules as $rule) {
                $ruleManagement->startNotification($rule);
            }
        }
    }
}