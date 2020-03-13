<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Component;

use Doctrine\DBAL\ParameterType;

/**
 * Class Customer
 * Customer component exporting logic
 *
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Component
 */
class Customer extends ExporterComponentAbstract
{

    CONST EXPORTER_COMPONENT_ID_FIELD = "id";
    CONST EXPORTER_COMPONENT_MAIN_FILE = "customers.csv";
    CONST EXPORTER_COMPONENT_TYPE = "customers";

    public function export()
    {
        if (!$this->config->isCustomersExportEnabled($this->getAccount())) {
            $this->logger->info("BxIndexLog: the customers are not to be exported;");
            return true;
        }

        parent::export();
    }

    /**
     * Customers export
     */
    public function exportComponent()
    {
        $this->logger->debug("BxIndexLog: Customers - start collecting customers for account {$this->getAccount()}");
        $attributes = $this->getFields();
        $properties = array_merge([
            'locale.code as languagecode',
            'country.iso as countryiso',
            'country_state_translation.name as statename',
            'sale_channel_translation.name as shopname',
            'payment_method_translation.name as preferred_payment_method',
            'salutation_translation.display_name as salutation'
        ], array_flip($attributes));
        $header = true;
        $firstShop = true;


        $latestAddressSQL = $this->connection->createQueryBuilder()
            ->select(['MAX(id) as max_id'])
            ->from("order_address")
            ->groupBy("customer_id");

        $latestOrderSQL = $this->connection->createQueryBuilder()
            ->select(["*"])
            ->from("(" . $latestAddressSQL->__toString() .")", "latest_address" )
            ->leftJoin("latest_address", "order_address", 'oc', 'oc.id=latest_address.max_id');

        foreach ($this->config->getAccountLanguages($this->getAccount()) as $languageId => $language)
        {
            $data = [];
            $countMax = 1000000;
            $limit = 3000;
            $totalCount = 0;
            $page = 1;

            while ($countMax > $totalCount + $limit)
            {
                $query = $this->connection->createQueryBuilder();
                $query->select($properties)
                    ->from('customer')
                    ->leftJoin(
                        'customer',
                        "( " . $latestOrderSQL->__toString() . ") ",
                        'customer_address',
                        'customer_address.customer_id = customer.id'
                    )
                    ->leftJoin(
                        'customer_address',
                        'country',
                        'country',
                        'customer_address.country_id = country.id'
                    )
                    ->leftJoin(
                        'customer_address',
                        'country_state_translation',
                        'country_state_translation',
                        'customer_address.country_state_id = country_state_translation.country_state_id AND customer.language_id = country_state_translation.language_id'
                    )
                    ->leftJoin(
                        'sales_channel',
                        'sales_channel_translation',
                        'channel',
                        'sales_channel.id = channel.sales_channel_id'
                    )
                    ->leftJoin(
                        'customer',
                        'payment_method_translation',
                        'payment_method_translation',
                        'customer.default_payment_method_id = payment_method_translation.payment_method_id'
                    )
                    ->leftJoin(
                        'customer',
                        'salutation_translation',
                        'salutation_translation',
                        'customer.salutation_id = salutation_translation.salutation_id AND customer.language_id = salutation_translation.language_id'
                    )
                    ->andWhere("customer.language_id=:languageId")
                    ->andWhere("sales_channel.id=:channelId")
                    ->groupBy('customer.id')
                    ->setParameter('channelId', $this->config->getAccountChannelId($this->getAccount()), ParameterType::BINARY)
                    ->setParameter('languageId', $languageId, ParameterType::BINARY)
                    ->setFirstResult(($page - 1) * $limit)
                    ->setMaxResults($limit);

                $count = $query->execute()->rowCount();
                $totalCount+=$count;
                if($totalCount == 0 && $firstShop)
                {
                    break;
                }
                $data = $query->execute()->fetchAll();
                if ($header && count($data) > 0)
                {
                    $data = array_merge(array(array_keys(end($data))), $data);
                    $header = false;
                }
                $this->getFiles()->savePartToCsv($this->getComponentMainFile(), $data);
                $this->logger->info("BxIndexLog: Customer export - Current page: {$page}, data count: {$totalCount}");
                $page++;
            }

            $firstShop = false;
        }

        $customerSourceKey = $this->getLibrary()->addMainCSVCustomerFile($this->getFiles()->getPath($this->getComponentMainFile()), $this->getComponentIdField());
        foreach ($attributes as $attribute)
        {
            if ($attribute == $this->getComponentIdField()) continue;
            $this->getLibrary()->addSourceStringField($customerSourceKey, $attribute, $attribute);
        }
    }

    /**
     * Getting the customer attributes list
     * @return array
     * @throws \Exception
     */
    public function getFields() : array
    {
        $this->logger->info('BxIndexLog: get all customer attributes for account: ' . $this->getAccount());

        $attributesList = [];
        $attributes = $this->getPropertiesByTableList(['customer', 'customer_address']);
        $excludeFieldsFromMain = ['salutation_id', 'title', $this->getComponentIdField(), 'customer_id', 'country_id', 'country_state_id', 'custom_fields'];
        foreach ($attributes as $attribute)
        {
            if (in_array($attribute['COLUMN_NAME'], $excludeFieldsFromMain) && $attribute['TABLE_NAME'] != 'customer') {
                continue;
            }
            $key = "{$attribute['TABLE_NAME']}.{$attribute['COLUMN_NAME']}";
            $attributesList[$key] = $attribute['COLUMN_NAME'];
        }

        return $this->config->getAccountCustomersProperties($this->getAccount(), $attributesList, $this->getRequiredProperties(), $this->getExcludedProperties());
    }

    /**
     * @return array
     */
    public function getRequiredProperties() : array
    {
        return [
            'id',
            'title',
            'first_name',
            'last_name',
            'email',
            'customer_number',
            'birthday',
            'created_at'
        ];
    }

    /**
     * @return array
     */
    public function getExcludedProperties() : array
    {
        return [
            'password',
            'legacy_password',
            'legacy_encoder',
            'hash'
        ];
    }

}
