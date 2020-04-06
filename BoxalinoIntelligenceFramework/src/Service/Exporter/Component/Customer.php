<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Component;

use Doctrine\DBAL\ParameterType;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class Customer
 * Customer component exporting logic
 *
 * @package Boxalino\IntelligenceFramework\Service\Exporter\Component
 */
class Customer extends ExporterComponentAbstract
{

    CONST EXPORTER_LIMIT = 10000000;
    CONST EXPORTER_STEP = 10000;
    CONST EXPORTER_DATA_SAVE_STEP = 1000;

    CONST EXPORTER_COMPONENT_ID_FIELD = "id";
    CONST EXPORTER_COMPONENT_MAIN_FILE = "customers.csv";
    CONST EXPORTER_COMPONENT_TYPE = "customers";

    public function export()
    {
        if (!$this->config->isCustomersExportEnabled($this->getAccount())) {
            $this->logger->info("BxIndexLog: the customers export is disabled.");
            return true;
        }

        parent::export();
    }

    /**
     * Customers export
     */
    public function exportComponent()
    {
        $attributes = $this->getFields();
        $properties = array_merge([
            'locale.code as languagecode',
            'country.iso as countryiso',
            'country_state_translation.name as statename',
            'sales_channel_translation.name as shopname',
            'payment_method_translation.name as preferred_payment_method',
            'salutation_translation.display_name as salutation'
        ], array_flip($attributes));
        $latestAddressSQL = $this->connection->createQueryBuilder()
            ->select(['MAX(order_id) as max_id', 'order_version_id AS latest_address_order_version_id', 'customer_id'])
            ->from("order_customer")
            ->groupBy("customer_id");

        $latestOrderSQL = $this->connection->createQueryBuilder()
            ->select(["*"])
            ->from("(" . $latestAddressSQL->__toString() .")", "latest_order" )
            ->leftJoin("latest_order", "order_address", 'oc', 'oc.order_id=latest_order.max_id AND oc.order_version_id=latest_order.latest_address_order_version_id')
            ->andWhere("latest_order.latest_address_order_version_id = :live");

        $header = true;  $totalCount = 0;  $page = 1;
        while (self::EXPORTER_LIMIT > $totalCount + self::EXPORTER_STEP)
        {
            $data = [];
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
                    'customer',
                    'sales_channel_translation',
                    'sales_channel_translation',
                    'customer.sales_channel_id = sales_channel_translation.sales_channel_id'
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
                ->leftJoin(
                    'customer',
                    'language',
                    'language',
                    'customer.language_id = language.id'
                )
                ->leftJoin(
                    'language',
                    'locale',
                    'locale',
                    'locale.id = language.locale_id'
                )
                #->andWhere("customer.language_id=:languageId")
                ->andWhere("customer.sales_channel_id=:channelId")
                ->groupBy('customer.id')
                ->setParameter("live",  Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY)
                ->setParameter('channelId', Uuid::fromHexToBytes($this->config->getAccountChannelId($this->getAccount())), ParameterType::BINARY)
                #->setParameter('languageId', $languageId, ParameterType::BINARY)
                ->setFirstResult(($page - 1) * self::EXPORTER_STEP)
                ->setMaxResults(self::EXPORTER_STEP);

            $count = $query->execute()->rowCount();
            $totalCount+=$count;
            if($totalCount == 0)
            {
                break;
            }
            $results = $this->processExport($query);
            foreach($results as $row)
            {
                if($header)
                {
                    $exportFields = array_keys($row);
                    $data[] = $exportFields;
                    $header = false;
                }
                $data[] = $row;
                if(count($data) > self::EXPORTER_DATA_SAVE_STEP)
                {
                    $this->getFiles()->savePartToCsv($this->getComponentMainFile(), $data);
                    $data = [];
                }
            }

            $this->getFiles()->savePartToCsv($this->getComponentMainFile(), $data);
            $this->logger->info("BxIndexLog: Customer export - Current page: {$page}, data count: {$totalCount}");
            $data=[]; $page++;
            if($totalCount < self::EXPORTER_STEP - 1)
            {
                $this->setSuccessOnComponentExport(true);
                break;
            }
        }

        if($this->getSuccessOnComponentExport())
        {
            $customerSourceKey = $this->getLibrary()->addMainCSVCustomerFile($this->getFiles()->getPath($this->getComponentMainFile()), $this->getComponentIdField());
            foreach ($attributes as $attribute)
            {
                if ($attribute == $this->getComponentIdField()) continue;
                $this->getLibrary()->addSourceStringField($customerSourceKey, $attribute, $attribute);
            }
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
        $excludeFieldsFromMain = ['id','salutation_id', 'title', $this->getComponentIdField(), 'customer_id', 'country_id', 'country_state_id', 'custom_fields'];
        foreach ($attributes as $attribute)
        {
            if (in_array($attribute['COLUMN_NAME'], $this->getExcludedProperties())) {
                continue;
            }
            if (in_array($attribute['COLUMN_NAME'], $excludeFieldsFromMain) && $attribute['TABLE_NAME'] != 'customer') {
                continue;
            }
            $attributesList["{$attribute['TABLE_NAME']}.{$attribute['COLUMN_NAME']}"] = $attribute['COLUMN_NAME'];
        }

        return $attributesList;
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
