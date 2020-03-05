<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Component;

class Order extends ExporterComponentAbstract
{

    const EXPORTER_COMPONENT_TYPE = "transactions";
    CONST EXPORTER_COMPONENT_MAIN_FILE = "transactions.csv";

    public function export()
    {
        if (!$this->config->isTransactionsExportEnabled($this->account)) {
            $this->log->info("BxIndexLog: the transactions are disabled for the exporter.");
            return true;
        }

        set_time_limit(7200);
        $this->logger->info("BxIndexLog: Preparing transactions.");
    }

    /**
     * Transactions export
     * Exporting detailed billing and shipping address for the items/order
     *
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function exportTransactions()
    {
        $db = $this->db;
        $account = $this->getAccount();
        $files = $this->getFiles();
        $attributes = $this->getTransactionAttributes($account);
        $addressAttributes = $this->getTransactionAddressAttributes();
        $transaction_properties = array_flip(array_merge($attributes, $addressAttributes));

        $quoted2 = $db->quote(2);
        $oInvoiceAmount = $this->qi('s_order.invoice_amount');
        $oInvoiceShipping = $this->qi('s_order.invoice_shipping');
        $oCurrencyFactor = $this->qi('s_order.currencyFactor');
        $dPrice = $this->qi('s_order_details.price');
        $transaction_properties = array_merge($transaction_properties,
            array(
                'total_order_value' => new Zend_Db_Expr(
                    "ROUND($oInvoiceAmount * $oCurrencyFactor, $quoted2)"),
                'shipping_costs' => new Zend_Db_Expr(
                    "ROUND($oInvoiceShipping * $oCurrencyFactor, $quoted2)"),
                'price' => new Zend_Db_Expr(
                    "ROUND($dPrice * $oCurrencyFactor, $quoted2)"),
                'detail_status' => 's_order_details.status'
            )
        );

        $header = true;
        $data = array();
        $countMax = 10000000;
        $limit = 3000;
        $totalCount = 0;
        $date = date("Y-m-d H:i:s", strtotime("-1 month"));
        $mode = $this->config->getTransactionMode($account);
        $firstShop = true;
        foreach ($this->config->getAccountLanguages($account) as $shop_id => $language) {
            $page = 1;
            while ($countMax > $totalCount + $limit) {
                $sql = $db->select()
                    ->from(
                        array('s_order'),
                        $transaction_properties
                    )
                    ->joinLeft(
                        array('s_order_shippingaddress'),
                        $this->qi('s_order.id') . ' = ' . $this->qi('s_order_shippingaddress.orderID'),
                        array()
                    )
                    ->joinLeft(
                        array('s_order_billingaddress'),
                        $this->qi('s_order.id') . ' = ' . $this->qi('s_order_billingaddress.orderID'),
                        array()
                    )
                    ->joinLeft(
                        array('c_b' => 's_core_countries'),
                        $this->qi('s_order_billingaddress.countryID') . ' = ' . $this->qi('c_b.id'),
                        array("billing_countryiso"=>"countryiso")
                    )
                    ->joinLeft(
                        array('s_b'=>'s_core_countries_states'),
                        $this->qi('s_order_billingaddress.stateID') . ' = ' . $this->qi('s_b.id'),
                        array("billing_statename" => "name")
                    )
                    ->joinLeft(
                        array('c_s' => 's_core_countries'),
                        $this->qi('s_order_shippingaddress.countryID') . ' = ' . $this->qi('c_s.id'),
                        array("shipping_countryiso"=>"countryiso")
                    )
                    ->joinLeft(
                        array('s_s'=>'s_core_countries_states'),
                        $this->qi('s_order_shippingaddress.stateID') . ' = ' . $this->qi('s_s.id'),
                        array("shipping_statename" => "name")
                    )
                    ->joinLeft(
                        array('s_user'),
                        $this->qi('s_order.userId') . ' = ' . $this->qi('s_user.id'),
                        array('email')
                    )
                    ->joinLeft(
                        array('s_order_details'),
                        $this->qi('s_order_details.orderID') . ' = ' . $this->qi('s_order.id'),
                        array()
                    )
                    ->joinLeft(
                        array('a_d' => 's_articles_details'),
                        $this->qi('a_d.ordernumber') . ' = ' . $this->qi('s_order_details.articleordernumber'),
                        array('articledetailsID' => 'id')
                    )
                    ->joinLeft(
                        array('o_s' => 's_core_states'),
                        $this->qi('s_order.status') . ' = ' . $this->qi('o_s.id'),
                        array('statusname' => 'name')
                    )
                    ->joinLeft(
                        array('o_s_c' => 's_core_states'),
                        $this->qi('s_order.cleared') . ' = ' . $this->qi('o_s_c.id'),
                        array('clearedname' => 'name')
                    )
                    ->joinLeft(
                        array('o_p' => 's_core_paymentmeans'),
                        $this->qi('s_order.paymentID') . ' = ' . $this->qi('o_p.id'),
                        array('paymentname' => 'name')
                    )
                    ->joinLeft(
                        array('s_core_locales'),
                        $this->qi('s_order.language') . ' = ' . $this->qi('s_core_locales.id'),
                        array("languagecode" => "locale")
                    )
                    ->joinLeft(
                        array('s_core_shops'),
                        $this->qi('s_order.subshopID') . ' = ' . $this->qi('s_core_shops.id'),
                        array("shopname" => "name")
                    )
                    ->where($this->qi('s_order.subshopID') . ' = ?', $shop_id)
                    ->limit($limit, ($page - 1) * $limit)
                    ->order('s_order.ordertime DESC');

                if ($mode == 1) {
                    $sql->where('s_order.ordertime >= ?', $date);
                }
                $stmt = $db->query($sql);

                if ($stmt->rowCount()) {
                    while ($row = $stmt->fetch()) {
                        /** @note list price at the time of the order is not stored, only the final price **/
                        $row['discounted_price'] = $row['price'];
                        $row['guest_id']="";
                        $data[] = $row;
                        $totalCount++;
                    }
                } else {
                    if ($totalCount == 0 && $firstShop){
                        return;
                    }
                    break;
                }
                if ($header && count($data) > 0) {
                    $data = array_merge(array(array_keys(end($data))), $data);
                    $header = false;
                }
                $files->savePartToCsv('transactions.csv', $data);
                $this->logger->info("BxIndexLog: Transaction export - Current page: {$page}, data count: {$totalCount}");
                $page++;
            }
            $firstShop = false;
        }

        $sourceKey =  $this->bxData->setCSVTransactionFile($files->getPath('transactions.csv'), 'id', 'articledetailsID', 'userID', 'ordertime', 'total_order_value', 'price', 'discounted_price', 'currency', 'email');
        $this->bxData->addSourceCustomerGuestProperty($sourceKey,'guest_id');

        $this->logger->info("BxIndexLog: Transactions - exporting additional tables for account: {$account}");
        $this->exportExtraTables('transactions', $this->config->getAccountExtraTablesByEntityType($account,'transactions'));
    }


    /**
     * Getting a list of transaction attributes and the table it comes from
     * To be used in the general SQL select
     *
     * @return array
     */
    public function getTransactionAttributes()
    {
        $this->logger->info('BxIndexLog: get all transaction attributes for account: ' . $this->getAccount());

        $attributeList = [];
        $excludeFields = ['orderID', 'id', 'ordernumber', 'status'];
        $attributes = $this->getPropertiesByTableList(['s_order', 's_order_details']);
        foreach ($attributes as $attribute) {
            if (in_array($attribute['COLUMN_NAME'], $excludeFields) && $attribute['TABLE_NAME'] == 's_order_details') {
                continue;
            }
            $key = "{$attribute['TABLE_NAME']}.{$attribute['COLUMN_NAME']}";
            $attributeList[$key] = $attribute['COLUMN_NAME'];
        }

        return $this->config->getAccountTransactionsProperties($this->getAccount(), $attributeList, $this->getRequiredProperties());
    }

    public function getRequiredProperties()
    {
        return [
            'id','articleID','userID','ordertime','invoice_amount','currencyFactor','price', 'status'
        ];
    }

    /**
     * Getting a list of transaction address attributes and the table it comes from
     * To be used in the general SQL select
     *
     * @return array
     */
    public function getTransactionAddressAttributes()
    {
        $addressAttributes = [];
        $attributes = $this->getPropertiesByTableList(['s_order_billingaddress', 's_order_shippingaddress']);
        foreach ($attributes as $attribute) {
            if($attribute['COLUMN_NAME'] == 'orderID' || $attribute['COLUMN_NAME'] == 'id' || $attribute['COLUMN_NAME'] == 'userID'){
                continue;
            }

            $key = "{$attribute['TABLE_NAME']}.{$attribute['COLUMN_NAME']}";
            $addressAttributes[$key] = "shipping_" . $attribute['COLUMN_NAME'];
            if($attribute['TABLE_NAME'] == 's_order_billingaddress'){
                $addressAttributes[$key] = "billing_" . $attribute['COLUMN_NAME'];
            }
        }

        return $addressAttributes;
    }



}