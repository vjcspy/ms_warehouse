<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Created by PhpStorm.
 * User: steve
 * Date: 07/09/2016
 * Time: 23:07
 */

namespace Magestore\InventorySuccess\Model\TransferStock\Email;

use Magestore\InventorySuccess\Api\Data\TransferStock\Email as TransferEmailNotifyData;
use Magestore\InventorySuccess\Api\Data\TransferStock\TransferStockInterface;

class EmailNotification extends \Magestore\InventorySuccess\Model\Email\EmailManagement
{

    /** @var  \Magestore\InventorySuccess\Model\TransferStockFactory */
    protected $_transferStockFactory;

    /** @var   */
    protected $_urlBuilder;

    public function __construct(
        \Magestore\InventorySuccess\Model\TransferStockFactory $transferStockFactory,
        \Magento\Backend\Model\UrlInterface $urlBuilder
    )
    {
        $this->_transferStockFactory = $transferStockFactory;
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * send email to notifierEmails when a transfer stock is created.
     * @param $transferstockId
     */
    public function notifyCreateNewTransfer($transferstockId){

        $transferStock = $this->_transferStockFactory->create()->load($transferstockId);
        $this->setReceivers($transferStock->getNotifierEmails());
        $this->setEmailTemplate(TransferEmailNotifyData::EMAIL_TEMPLATE_TRANSFERSTOCK_CREATE);

        $templateVars = [];
        $templateVars['transferstock_id'] = $transferstockId;
        $templateVars['transferstock_code'] = $transferStock->getTransferstockCode();
        $templateVars['total_items'] = $transferStock->getQty();
        $templateVars['created_by'] = $transferStock->getCreatedBy();

        switch ($transferStock->getType()){
            case TransferStockInterface::TYPE_REQUEST:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_request/edit/id/" . $transferstockId);
                break;
            case TransferStockInterface::TYPE_SEND:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_send/edit/id/" . $transferstockId);
                break;
            case TransferStockInterface::TYPE_TO_EXTERNAL:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_external/type/to_external/edit/id/" . $transferstockId);
                break;
            case TransferStockInterface::TYPE_FROM_EXTERNAL:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_external/type/from_external/edit/id/" . $transferstockId);
                break;
        }

        $this->setTemplateVars($templateVars);

        //send email
        $this->sendEmail();
    }

    /**
     * send email to notifierEmails when a transfer stock is created.
     * @param $transferstockId
     */
    public function notifyCreateDelivery($transferstockId){

        $transferStock = $this->_transferStockFactory->create()->load($transferstockId);
        $this->setReceivers($transferStock->getNotifierEmails());
        $this->setEmailTemplate(TransferEmailNotifyData::EMAIL_TEMPLATE_TRANSFERSTOCK_DELIVERY);

        $templateVars = [];
        $templateVars['transferstock_id'] = $transferstockId;
        $templateVars['transferstock_code'] = $transferStock->getTransferstockCode();
        $templateVars['total_items'] = $transferStock->getQty();
        $templateVars['created_by'] = $transferStock->getCreatedBy();

        switch ($transferStock->getType()){
            case TransferStockInterface::TYPE_REQUEST:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_request/edit/id/" . $transferstockId);
                break;
            case TransferStockInterface::TYPE_SEND:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_send/edit/id/" . $transferstockId);
                break;
            case TransferStockInterface::TYPE_TO_EXTERNAL:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_external/type/to_external/edit/id/" . $transferstockId);
                break;
            case TransferStockInterface::TYPE_FROM_EXTERNAL:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_external/type/from_external/edit/id/" . $transferstockId);
                break;
        }

        $this->setTemplateVars($templateVars);

        //send email
        $this->sendEmail();
    }

    /**
     * send email to notifierEmails when a transfer stock is created.
     * @param $transferstockId
     */
    public function notifyCreateReceiving($transferstockId){

        $transferStock = $this->_transferStockFactory->create()->load($transferstockId);
        $this->setReceivers($transferStock->getNotifierEmails());
        $this->setEmailTemplate(TransferEmailNotifyData::EMAIL_TEMPLATE_TRANSFERSTOCK_RECEIVING);

        $templateVars = [];
        $templateVars['transferstock_id'] = $transferstockId;
        $templateVars['transferstock_code'] = $transferStock->getTransferstockCode();
        $templateVars['total_items'] = $transferStock->getQty();
        $templateVars['created_by'] = $transferStock->getCreatedBy();

        switch ($transferStock->getType()){
            case TransferStockInterface::TYPE_REQUEST:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_request/edit/id/" . $transferstockId);
                break;
            case TransferStockInterface::TYPE_SEND:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_send/edit/id/" . $transferstockId);
                break;
            case TransferStockInterface::TYPE_TO_EXTERNAL:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_external/type/to_external/edit/id/" . $transferstockId);
                break;
            case TransferStockInterface::TYPE_FROM_EXTERNAL:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_external/type/from_external/edit/id/" . $transferstockId);
                break;
        }

        $this->setTemplateVars($templateVars);

        //send email
        $this->sendEmail();
    }


    /**
     * @param $transferstockId
     */
    public function notifyCreateDirectTransfer($transferstockId){

        $transferStock = $this->_transferStockFactory->create()->load($transferstockId);
        $this->setReceivers($transferStock->getNotifierEmails());
        $this->setEmailTemplate(TransferEmailNotifyData::EMAIL_TEMPLATE_TRANSFERSTOCK_DIRECT_TRANSFER);

        $templateVars = [];
        $templateVars['transferstock_id'] = $transferstockId;
        $templateVars['transferstock_code'] = $transferStock->getTransferstockCode();
        $templateVars['total_items'] = $transferStock->getQty();
        $templateVars['created_by'] = $transferStock->getCreatedBy();

        switch ($transferStock->getType()){
            case TransferStockInterface::TYPE_REQUEST:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_request/edit/id/" . $transferstockId);
                break;
            case TransferStockInterface::TYPE_SEND:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_send/edit/id/" . $transferstockId);
                break;
            case TransferStockInterface::TYPE_TO_EXTERNAL:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_external/type/to_external/edit/id/" . $transferstockId);
                break;
            case TransferStockInterface::TYPE_FROM_EXTERNAL:
                $templateVars['transfer_link'] = $this->_urlBuilder->getUrl("inventorysuccess/transferstock_external/type/from_external/edit/id/" . $transferstockId);
                break;
        }

        $this->setTemplateVars($templateVars);

        //send email
        $this->sendEmail();
    }


}