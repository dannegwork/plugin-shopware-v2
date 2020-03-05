<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\ExporterInterface;

class Voucher extends ItemsAbstract
{

    public function export()
    {
        // TODO: Implement export() method.
    }

    /**
     * Export vouchers
     *
     * @param $account
     * @param $files
     */
    public function exportVouchers()
    {
        $db = Shopware()->Db();
        $account = $this->getAccount();
        $files = $this->getFiles();
        $languages = $this->_config->getAccountLanguages($account);
        $header = true;
        $data = array();
        $doneCases = array();
        $headers = array();
        foreach ($languages as $shop_id => $language) {
            $sql = $db->select()->from(array('v' => 's_emarketing_vouchers'),
                array('v.*',
                    'used_codes' => new Zend_Db_Expr("IF( modus = '0',
                (SELECT count(*) FROM s_order_details as d WHERE articleordernumber =v.ordercode AND d.ordernumber!='0'),
                (SELECT count(*) FROM s_emarketing_voucher_codes WHERE voucherID =v.id AND cashed=1))")))
                ->where('(v.subshopID IS NULL OR v.subshopID = ?)', $shop_id)
                ->where('((CURDATE() BETWEEN v.valid_from AND v.valid_to) OR (v.valid_from IS NULL AND v.valid_to IS NULL) OR (DATE(NOW())<DATE(v.valid_to) AND v.valid_from IS NULL) OR (DATE(NOW())>DATE(v.valid_from) AND v.valid_to IS NULL))');
            $vouchers = $db->fetchAll($sql);
            foreach($vouchers as $row)
            {
                if($header) {
                    $headers = array_keys($row);
                    $data[] = $headers;
                    $header = false;
                }
                if(isset($doneCases[$row['id']])) continue;
                $doneCases[$row['id']] = true;
                $row['id'] = 'voucher_' . $row['id'];
                $data[] = $row;
            }
            if(sizeof($data)) {
                $files->savePartToCsv('voucher.csv', $data);
            }
            $vouchers = null;
        }

        if($header) {
            $data = ['id','description','vouchercode','numberofunits','value','minimumcharge','shippingfree',
                'bindtosupplier','valid_from','valid_to','ordercode','modus','percental','numorder','customergroup',
                'restrictarticles','strict','subshopID','taxconfig','customer_stream_ids','used_codes'];
            $files->savePartToCsv('voucher.csv', $data);
        }
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('voucher.csv'), 'id');
        $this->bxData->addSourceParameter($attributeSourceKey, 'additional_item_source', 'true');
        foreach ($headers as $header){
            $this->bxData->addSourceStringField($attributeSourceKey, 'voucher_'.$header, $header);
        }
        $data = array();
        $header = true;
        $sql = $db->select()->from(array('v_c' => 's_emarketing_voucher_codes'));
        $voucherCodes = $db->fetchAll($sql);
        foreach($voucherCodes as $row)
        {
            if(isset($doneCases[$row['voucherID']])){
                if($header){
                    $data[] = array_keys($row);
                    $header = false;
                }
                $row['voucherID'] = 'voucher_' . $row['voucherID'];
                $data[] = $row;
            }
        }
        $doneCases = array();
        $files->savePartToCsv('voucher_codes.csv', $data);
        $this->bxData->addCSVItemFile($files->getPath('voucher_codes.csv'), 'id');
    }

}