<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Controller\Adminhtml\TransferStock\External;

use Magestore\InventorySuccess\Api\Data\TransferStock\TransferPermission;

class Edit extends \Magestore\InventorySuccess\Controller\Adminhtml\TransferStock\AbstractRequest
{

    const ADMIN_RESOURCE = TransferPermission::EXTERNAL_TRANSFER_STOCK_VIEW;

    /**
     * @var \Magestore\InventorySuccess\Model\TransferStockFactory
     */
    protected $_transferStockFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManager;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_adminSession;

    /**
     * @var \Magestore\InventorySuccess\Model\Warehouse\PermissionFactory
     */
    protected $_warehousePermissionFactory;

    /** @var  \Magestore\InventorySuccess\Model\Locator\LocatorFactory $_locatorFactory */
    protected $_locatorFactory;

    public function __construct(
        \Magestore\InventorySuccess\Controller\Adminhtml\Context $context,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Magestore\InventorySuccess\Model\Locator\LocatorFactory $locatorFactory

    ){
        parent::__construct($context);
        $this->_transferStockFactory = $context->getTransferStockFactory();
        $this->_adminSession = $adminSession;
        $this->_locatorFactory = $locatorFactory;

    }
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');

        $transferstockCode = "";
        /** @var \Magestore\InventorySuccess\Model\TransferStock $model */
        $model = $this->_transferStockFactory->create();
        $warehouseId = '';
        if ($id) {
            /** @var  \Magestore\InventorySuccess\Model\Locator\Locator $locator */
            $locator = $this->_locatorFactory->create();
            $locator->setSessionByKey("current_transferstock_id", $id);

            $model->load($id);
            if (!$model->getTransferstockId()) {
                $this->messageManager->addError(__('This transferstock is no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }

            $transferstockCode = "#".$model->getTransferstockCode();

        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Magestore_InventorySuccess::transfer_to_external_create');
        $type = $this->getRequest()->getParam('type');
        if($type =='to_external'){
            $pageTitle = "Transfer Stock to External Location " . $transferstockCode;
        }
        else{
            $pageTitle = "Transfer Stock from External Location " . $transferstockCode;
        }
        $resultPage->getConfig()->getTitle()->prepend(__($pageTitle));

        return $resultPage;
    }

}


