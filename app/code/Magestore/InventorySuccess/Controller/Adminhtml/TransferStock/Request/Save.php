<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\InventorySuccess\Controller\Adminhtml\TransferStock\Request;

use Magestore\InventorySuccess\Model\TransferStock as TransferStockModel;
use \Magestore\InventorySuccess\Model\TransferStock\TransferActivity as TransferActivityModel;
use \Magestore\InventorySuccess\Model\ResourceModel\TransferStock\TransferStockProduct as TransferStockProductResource;
use Magestore\InventorySuccess\Api\Data\TransferStock\TransferStockInterface;

class Save extends \Magestore\InventorySuccess\Controller\Adminhtml\TransferStock\AbstractTransfer
{
    /**
     * @var \Magestore\InventorySuccess\Model\TransferStock\TransferActivityFactory
     */
    protected $_transferActivityFactory;

    /**
     * @var \Magestore\InventorySuccess\Model\TransferStock\TransferActivityProductFactory
     */
    protected $_transferActivityProductFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    protected $adminSession;

    /** @var  \Magestore\InventorySuccess\Model\ResourceModel\TransferStock\TransferStockProductFactory */
    protected $_transferStockProductResourceFactory;

    /** @var \Magestore\InventorySuccess\Model\TransferStock\TransferStockManagementFactory */
    protected $_transferStockManagement;

    /** @var  \Magestore\InventorySuccess\Model\Locator\LocatorFactory */
    protected $_locatorFactory;

    /** @var  \Magestore\InventorySuccess\Model\TransferStock\Email\EmailNotificationFactory */
    protected $_emailNotificationFactory;

    
    /**
     * Save constructor.
     * @param \Magestore\InventorySuccess\Controller\Adminhtml\Context $context
     * @param TransferStockModel\TransferActivityFactory $transferActivityFactory
     * @param TransferStockModel\TransferActivityProductFactory $transferActivityProductFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Backend\Model\Auth\Session $adminSession
     */
    public function __construct(
        \Magestore\InventorySuccess\Controller\Adminhtml\Context $context,
        \Magestore\InventorySuccess\Model\ResourceModel\TransferStock\TransferStockProductFactory $transferStockProductResourceFactory,
        \Magestore\InventorySuccess\Model\TransferStock\TransferActivityProductFactory $transferActivityProductFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Magestore\InventorySuccess\Model\Locator\LocatorFactory $locatorFactory,
        \Magestore\InventorySuccess\Model\TransferStock\Email\EmailNotificationFactory $emailNotificationFactory
    ) {
        parent::__construct($context);
        $this->_transferActivityFactory = $context->getTransferActivityFactory();
        $this->_transferActivityProductFactory = $transferActivityProductFactory;
        $this->timezone = $timezone;
        $this->adminSession = $adminSession;
        $this->_transferStockProductResourceFactory = $transferStockProductResourceFactory;
        $this->_transferStockManagement = $context->getTransferStockManagementFactory();
        $this->_locatorFactory = $locatorFactory;
        $this->_emailNotificationFactory = $emailNotificationFactory;
    }

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $id = $this->getRequest()->getParam('id');
        $data = $this->getRequest()->getPostValue();
        $action = $this->getRequest()->getParam("action");
        //check download action
        
        switch ($action){
            case 'download_summary':
                return $resultRedirect->setPath('*/*/downloadsummary/id/' . $id);
                break;

            case 'download_shortfall':
                return $resultRedirect->setPath('*/*/downloadshortfall/id/' . $id);
                break;
        }

        if (!empty($data)) {
            $locator = $this->_locatorFactory->create();
            $locator->setSesionByKey("current_request_stock", $data);
        }

        //validate input data before do anything
        $transferStockManagement = $this->_transferStockManagement->create();
        $validateResult = $transferStockManagement->validate($data);


        if(!$validateResult['is_validate']){
            foreach ($validateResult["errors"] as $error){
                $this->messageManager->addErrorMessage(
                    __($error)
                );
            }
            return $resultRedirect->setPath('*/*/edit', ['id' => $id, '_current' => true]);
        }

        if ($data) {
            $transferstock = $this->_transferStockFactory->create();
            if ($id) {
                $hasErrors = false;
                $transferstock->load($id);


                switch ($action){
                    //save general information of this request stock
                    case 'save_general':
                        $transferstock->setData("reason", $data['reason']);
                        $transferstock->setData("notifier_emails", $data['notifier_emails']);
                        $transferstock->setData("transferstock_code", $data['transferstock_code']);
                        $this->messageManager->addSuccessMessage(__('Saved general information successfully!'));
                        break;

                    //start request stock:
                    // -> change status to procesing
                    // -> saved product
                    // -> send email notification
                    case 'start_request':
                        if(!isset($data['links']['request_products'])){
                            $this->messageManager->addErrorMessage(__('No product to request!'));
                        }
                        else{

                            $transferstock->setData("status", TransferStockInterface::STATUS_PROCESSING);
                            $request_products = $data['links']['request_products'];
                            $this->saveTransferStockProduct($request_products);
                            $emailNotification = $this->_emailNotificationFactory->create();
                            $emailNotification->notifyCreateNewTransfer($id);

                            $this->messageManager->addSuccessMessage(__('Request stock #'. $transferstock->getTransferstockCode() . ' is ready to deliver and receive!'));
                        }
                        break;

                    //change the transfer status to completed
                    case 'complete':
                        $transferstock->setData("status", TransferStockInterface::STATUS_COMPLETED);
                        $this->messageManager->addSuccessMessage(__('Marked request stock #'. $transferstock->getTransferstockCode() . ' as completed'));
                        break;
                    //create new delivery and save delivery product to activity_product table
                    case 'save_delivery':
                        if(isset($data['links']['delivery_products'])) {
                            $delivery_products = $data['links']['delivery_products'];
                            $isValid = $this->validateStockDelivery($delivery_products);

                            if($isValid){
                                $this->saveTransferActivityProduct($delivery_products, TransferActivityModel::ACTIVITY_TYPE_DELIVERY);
                                $this->messageManager->addSuccessMessage(__('Create delivery successfully!'));
                                $emailNotification = $this->_emailNotificationFactory->create();
                                $emailNotification->notifyCreateDelivery($id);
                            }
                            else{
                                $this->_locatorFactory->create()->setSesionByKey("delivery_products", $delivery_products);
                                $this->messageManager->addErrorMessage(__('Deliver qty must be less than available qty!'));
                            }
                        }
                        else{
                            $this->messageManager->addErrorMessage(__('No product to create deliver!'));
                        }
                        break;
                    //create new receiving and save all receiving product to activity_product table
                    case 'save_receiving':
                        if(isset($data['links']['receiving_products'])) {
                            $receiving_products = $data['links']['receiving_products'];
                            $this->saveTransferActivityProduct($receiving_products, TransferActivityModel::ACTIVITY_TYPE_RECEIVING);
                            $this->messageManager->addSuccessMessage(__('Create receiving successfully!'));
                            $emailNotification = $this->_emailNotificationFactory->create();
                            $emailNotification->notifyCreateReceiving($id);
                        }
                        else{
                            $this->messageManager->addErrorMessage(__('No product to create receive!'));
                        }

                        break;

                    //save all product into transferstock_product table
                    case 'save_product':
                        if(!isset($data['links']['request_products'])){
                            $this->messageManager->addErrorMessage(__('No product selected!'));
                        }
                        else{
                            $request_products = $data['links']['request_products'];
                            $this->saveTransferStockProduct($request_products);
                            $this->messageManager->addSuccessMessage(__('Saved '. count($request_products) . ' product(s) to the requesting list!'));
                        }

                        break;
                    //save all product into transferstock_product table
                    case 'edit_product':
                        $transferstock->setData("status", TransferStockInterface::STATUS_PENDING);
                        $this->messageManager->addSuccessMessage(__('Now you can edit product list for this request again!'));
                        break;
                }
            }
            else{
                //update transfer stock information
                $data = $this->prepareTransferStockData($data);
                $transferstock->setData($data);
            }

            //save transfer stock information
            try {
                $transferstock->save();
                return $resultRedirect->setPath('*/*/edit', ['id' => $transferstock->getTransferstockId(), '_current' => true]);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the page.'));
            }
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param $warehouseId
     * @return string
     */
    public function getWarehouseCode($warehouseId){
        /** @var \Magestore\InventorySuccess\Model\Warehouse $warehouse */
        $warehouse = $this->_warehouseFactory->create()->load($warehouseId);
        return $warehouse->getWarehouseCode();
    }

    /**
     * Create new transfer stock with given data
     * @param $data
     *
     */
    public function prepareTransferStockData($data){

        $adminUser = $this->adminSession->getUser();
        if ($adminUser->getId()) {
            $adminName = $adminUser->getUserName();
        } else {
            $adminName = '';
        }
        if(isset($data['source_warehouse_id'])){
            $data['source_warehouse_code'] = $this->getWarehouseCode($data['source_warehouse_id']);
        }

        if(isset($data['des_warehouse_id'])){
            $data['des_warehouse_code'] = $this->getWarehouseCode($data['des_warehouse_id']);
        }

        $data['type'] = TransferStockModel::TYPE_REQUEST;
        $data['created_by'] = $adminName;
        $data['created_at'] = strftime('%Y-%m-%d %H:%M:%S', $this->timezone->scopeTimeStamp());
        
        return $data;
    }

    /**
     * create new delivery
     */
    public function createTransferActivity($activity_products, $activity_type){

        $adminUser = $this->adminSession->getUser();
        if ($adminUser->getId()) {
            $adminName = $adminUser->getUserName();
        } else {
            $adminName = '';
        }

        $data = [];
        $data['activity_type'] = $activity_type;
        $data['created_by'] = $adminName;
        $data['created_at'] = strftime('%Y-%m-%d %H:%M:%S', $this->timezone->scopeTimeStamp());
        $data['transferstock_id'] = $this->getRequest()->getParam('id');
        $data['total_qty'] = $this->getTransferActivityTotalQty($activity_products);
        $transferActivity = $this->_transferActivityFactory->create();
        $transferActivity->setData($data);

        try {
            $transferActivity->save();
            return $transferActivity;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the page.'));
        }

        return 0;
    }

    /**
     * count total qty of products in a delivery
     * @param $delivery_products
     * @return int
     */
    public function getTransferActivityTotalQty($activity_products){
        $total_qty = 0;
        foreach ($activity_products as $product){
            $total_qty = $total_qty + $product['qty'];
        }
        return $total_qty;
    }

    /**
     * save deliery product.
     * Steps:
     * + create new deliery
     * + reformat post data of delivery_products
     * + save delivery product into model TransferActivityProduct
     * @param $delivery_products
     */
    public function saveTransferActivityProduct($activity_products, $activity_type){
        $transferActivity = $this->createTransferActivity($activity_products,$activity_type);
        $activityId = $transferActivity->getActivityId();
        if($activityId){
            //reformat delivery product data
            $products = [];
            foreach ($activity_products as $item){
                $product = [];
                $product['activity_id'] = $activityId;
                $product['product_id'] = $item['id'];
                $product['product_name'] = $item['name'];
                $product['product_sku'] = $item['sku'];
                $product['qty'] =  $item['qty'];
                $products[$item['id']] = $product;
            }

            /** @var \Magestore\InventorySuccess\Model\TransferStock\TransferActivityManagement $transferActivityManagement */
            $transferActivityManagement = $this->_objectManager->create('\Magestore\InventorySuccess\Model\TransferStock\TransferActivityManagement');
            $transferActivityManagement->setProducts($transferActivity, $products);
            $transferActivityManagement->updateStock($transferActivity);

            $transferstockId = $this->getRequest()->getParam('id');
            $transferActivityManagement->updateTransferstockProductQtySummary($transferstockId, $activity_products, $activity_type);
        }
    }

    public function saveTransferStockProduct($data){
        $data = $this->reformatPostData($data);
        $id = $this->getRequest()->getParam('id');

        /** @var \Magestore\InventorySuccess\Model\TransferStock\TransferStockManagement $transferStockManagement */
        $transferStockManagement = $this->_objectManager->create('\Magestore\InventorySuccess\Model\TransferStock\TransferStockManagement');
        $transferStockManagement->saveTransferStockProduct($id, $data);
    }

    public function reformatPostData($data){
        $id = $this->getRequest()->getParam('id');
        $newData = [];

        foreach ($data as $index => $value){
            $item = [];
            $item['transferstock_id'] = $id;
            $item['product_id'] = $value['id'];
            $item['product_name'] = $value['name'];
            $item['product_sku'] = $value['sku'];
            $item['qty'] =  $value['request_qty'];

            $newData[$value['id']] = $item;
        }
        return $newData;
    }

    /** validate send stock qty
     * @param $send_products
     * @return array|bool
     */
    public function validateStockDelivery($send_products){
        //\Zend_Debug::dump($send_products);die();
        /** @var \Magestore\InventorySuccess\Model\TransferStock\TransferStockManagement $transferStockManagement */
        $transferStockManagement = $this->_objectManager->create('\Magestore\InventorySuccess\Model\TransferStock\TransferStockManagement');
        $id = $this->getRequest()->getParam('id');
        $transferStock = $this->_objectManager->create('\Magestore\InventorySuccess\Model\TransferStock');
        $transferStock->load($id);
        $warehouseId = $transferStock->getSourceWarehouseId();
        $productStocks = [];
        foreach ($send_products as $item){
            $productStocks[$item['id']] = $item['qty'];
        }

        return $transferStockManagement->validateStockDelivery($productStocks, $warehouseId);

    }
}