<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Component;

use Doctrine\DBAL\ParameterType;

class Order extends ExporterComponentAbstract
{

    CONST EXPORTER_COMPONENT_ID_FIELD = "id";
    CONST EXPORTER_COMPONENT_TYPE = "transactions";
    CONST EXPORTER_COMPONENT_MAIN_FILE = "transactions.csv";

    public function export()
    {
        if (!$this->config->isTransactionsExportEnabled($this->account)) {
            $this->logger->info("BxIndexLog: the transactions are disabled for the exporter.");
            return true;
        }

        parent::export();
    }

    /**
     * Transactions export
     * Exporting detailed billing and shipping address for the items/order
     */
    public function exportComponent()
    {
        $properties = $this->getFields();
        $orderStateMachineId = $this->getOrderStateMachineId();
        $orderDeliveryStateMachineId = $this->getOrderDeliveryStateMachineId();
        $orderTransactionStateMachineId = $this->getOrderTransactionStateMachineId();
        $defaultLanguageId = $this->config->getChannelDefaultLanguageId($this->getAccount());
        $header = true;
        $countMax = 10000000;
        $limit = 3000;
        $totalCount = 0;
        $isIncremental = $this->config->getExportTransactionIncremental($this->getAccount());
        $firstShop = true;
        foreach ($this->config->getAccountLanguages($this->getAccount()) as $languageId => $language) {
            $page = 1;
            while ($countMax > $totalCount + $limit)
            {
                $data = [];
                $query = $this->connection->createQueryBuilder();
                $query->select($properties)
                    ->from("order_line_item", "order_line_item")
                    ->leftJoin(
                        'order_line_item', 'order', 'o', 'order.id=order_line_item.order_id AND order.version_id=order_line_item.order_version_id'
                    )
                    ->leftJoin(
                        'order', 'state_machine_state', 'smso', "smso.id=order.state_id AND smso.state_machine_id = $orderStateMachineId"
                    )
                    ->leftJoin(
                        'order', 'currency', 'c', "order.currency_id = c.id"
                    )
                    ->leftJoin(
                        'order', 'language', 'language', 'language.id = order.language_id'
                    )
                    ->leftJoin(
                        'language', 'locale', 'locale', 'locale.id = language.locale_id'
                    )
                    ->leftJoin(
                        'order', 'order_customer', 'oc', 'oc.order_id=order.id AND oc.order_version_id=order.version_id'
                    )
                    ->leftJoin(
                        'order_address', 'country', 'country', 'order_address.country_id = country.id'
                    )
                    ->leftJoin(
                        'order_address', 'country_state_translation', 'cst', 'order_address.country_state_id = cst.country_state_id'
                    )
                    ->leftJoin(
                        'order', 'order_address', 'oab', 'order.billing_address_id = oab.id AND order.billing_address_version_id=oab.version_id'
                    )
                    ->leftJoin(
                        'order', 'order_delivery', 'od', 'order.id = od.order_id AND order.version_id=od.order_version_id'
                    )
                    ->leftJoin(
                        'order_delivery', 'order_address', 'oas', 'order_delivery.shipping_order_address_id = oas.id AND order_delivery.shipping_order_address_version_id=oas.version_id'
                    )
                    ->leftJoin(
                        'order_delivery', 'state_machine_state', 'smsd', "smsd.id=order_delivery.state_id AND smsd.state_machine_id = $orderDeliveryStateMachineId"
                    )
                    ->leftJoin(
                        'order', 'order_transaction', 'ot', 'order.id = ot.order_id AND order.version_id=ot.order_version_id'
                    )
                    ->leftJoin(
                        'order_transaction', 'state_machine_state', 'smst', "smst.id=order_transaction.state_id AND smst.state_machine_id = $orderTransactionStateMachineId"
                    )
                    ->leftJoin(
                        'order_transaction', 'payment_method_transaction', 'pmt', "pmt.id=order_transaction.payment_method_id AND pmt.language_id = $defaultLanguageId"
                    )
                    ->andWhere("locale.code=:language")
                    ->andWhere("order.sales_channel_id=:channelId")
                    ->orderBy('order.order_date_time', 'DESC')
                    ->setParameter('channelId', Uuid::fromHexToBytes($this->config->getAccountChannelId($this->getAccount())), ParameterType::BINARY)
                    ->setParameter('language', $language, ParameterType::STRING)
                    ->setFirstResult(($page - 1) * $limit)
                    ->setMaxResults($limit);

                if ($isIncremental == 1) {
                    $query->andWhere('order.order_time >= ?', date("Y-m-d", strtotime("-1 month")));
                }

                $count = $query->execute()->rowCount();
                $totalCount+=$count;
                if($totalCount == 0 && $firstShop)
                {
                    break;
                }
                $data = $query->execute()->fetchAll();

                if ($header && count($data) > 0) {
                    $data = array_merge(array(array_keys(end($data))), $data);
                    $header = false;
                }
                $this->getFiles()->savePartToCsv($this->getComponentMainFile(), $data);
                $this->logger->info("BxIndexLog: Transaction export - Current page: {$page}, data count: {$totalCount}");
                $page++;
            }
            $firstShop = false;
        }

        $sourceKey =  $this->getLibrary()->setCSVTransactionFile($this->getFiles()->getPath($this->getComponentMainFile()), $this->getComponentIdField(), 'product_id', 'customer_id', 'order_date_time', 'ammount_total', 'price', 'discounted_price', 'currency', 'email');
        $this->getLibrary()->addSourceCustomerGuestProperty($sourceKey, 'guest_id');
    }


    /**
     * @return false|mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getOrderStateMachineId()
    {
        return $this->connection->fetchColumn("SELECT id FROM state_machine WHERE technical_name='order.state'");
    }

    /**
     * @return false|mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getOrderDeliveryStateMachineId()
    {
        return $this->connection->fetchColumn("SELECT id FROM state_machine WHERE technical_name='order_delivery.state'");
    }

    /**
     * @return false|mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getOrderTransactionStateMachineId()
    {
        return $this->connection->fetchColumn("SELECT id FROM state_machine WHERE technical_name='order_transaction.state'");
    }

    /**
     * Getting a list of transaction attributes and the table it comes from
     * To be used in the general SQL select
     *
     * @return array
     * @throws \Exception
     */
    public function getFields() : array
    {
        $this->logger->info('BxIndexLog: get all transaction attributes for account: ' . $this->getAccount());
        return $this->config->getAccountTransactionsProperties($this->getAccount(), $this->getRequiredProperties(), []);
    }

    /**
     * @return array
     */
    public function getRequiredProperties() : array
    {
        return [
            'LOWER(HEX(order_line_item.id)) AS order_line_item_id', 'LOWER(HEX(order_line_item.order_id)) AS id',
            'order_line_item.identifier', 'LOWER(HEX(order_line_item.product_id))', 'order_line_item.label',
            'order_line_item.type', 'order_line_item.quantity', 'order_line_item.unit_price', 'order_line_item.total_price',
            'order_line_item.stackable', 'order_line_item.removable', 'order_line_item.good', 'order_line_item.position',
            'order_line_item.created_at AS order_item_created_at',
            'order.auto_increment', 'order.order_number', 'LOWER(HEX(order.billing_address_id))', 'LOWER(HEX(order.sales_channel_id))',
            'order.order_date_time', 'order.order_date', 'order.ammount_total AS total_order_value', 'order.ammount_net',
            'order.tax_status', 'order.shipping_total as shipping_costs', 'order.affiliate_code', 'order.campaign_code', 'order.created_at',
            'smso.technical_name AS order_state', 'smsd.technical_name AS shipping_state', 'smst.technical_name AS transaction_state','c.iso_code AS currency', 'locale.code as language',
            'oc.email', 'oc. first_name', 'oc.last_name', 'oc.title', 'oc.company', 'oc.customer_number', 'LOWER(HEX(oc.customer_id))', 'oc.custom_fields AS customer_custom_fields',
            'country.iso as country_iso', 'cst.name as state_name',
            'oab.company AS billing_company', 'oab.title as billing_title', 'oab.first_name AS billing_first_name',
            'oab.last_name AS billing_last_name', 'oab.street AS billing_street', 'oab.zipcode AS billing_zipcode',
            'oab.city AS billing_city', 'oab.vat_id AS billing_vat_id', 'oab.phone_number AS billing_phone_nr',
            'od.tracking_codes AS shipping_tracking_codes', 'od.shipping_date_earliest', 'od.shipping_date_last', 'od.shipping_costs',
            'oas.company AS shipping_company', 'oas.title as shipping_title', 'oas.first_name AS shipping_first_name',
            'oas.last_name AS shipping_last_name', 'oas.street AS shipping_street', 'oas.zipcode AS shipping_zipcode',
            'oas.city AS shipping_city', 'oas.vat_id AS shipping_vat_id', 'oas.phone_number AS shipping_phone_nr',
            'ot.ammount as transaction_ammount', 'ot.payment_method_id', 'pmt.name AS payment_name', '"" AS guest_id'
        ];
    }

    /**
     * @return array
     */
    public function getExcludedProperties() : array
    {
        return [
            'depp_link_code',
        ];
    }

}
