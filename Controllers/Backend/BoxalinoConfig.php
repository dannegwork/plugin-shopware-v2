<?php

class Shopware_Controllers_Backend_BoxalinoConfig extends Shopware_Controllers_Backend_ExtJs
{

    protected $header = ['name', 'label', 'value', 'exclude', 'id', 'type'];

    public function indexAction()
    {
        $this->View()->loadTemplate('backend/boxalino_config/app.js');
        $this->View()->assign('title', 'Boxalino-Configuration');
    }

    public function getStoresAction(){

        $db = Shopware()->Db();
        $sql = $db->select()->from(array('s' => 's_core_shops'), array('id', 'name'));
        $stmt = $db->query($sql);
        $stores = array();
        while($row = $stmt->fetch()) {
            $row['exclude'] = false;
            $stores[] = $row;
        }

        $this->View()->assign(array(
            'success' => true,
            'data' => $stores,
            'total' => count($stores)
        ));
    }

    public function uploadConfigAction() {

        $this->Front()->Plugins()->Json()->setRenderer(false);

        if ($_FILES['bx_config']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode([
                'success' => false,
                'message' => json_encode($_FILES),
            ]);

            return;
        }

        if (!is_uploaded_file($_FILES['bx_config']['tmp_name'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Unsecure file detected',
            ]);

            return;
        }

        $fileName = basename($_FILES['bx_config']['name']);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if ($extension != 'csv') {
            echo json_encode([
                'success' => false,
                'message' => 'Please upload configuration in CSV format.',
            ]);

            return;
        }
        $destPath = Shopware()->DocPath('media_temp');

        if (!is_dir($destPath)) {
            // Try to create directory with write permissions
            mkdir($destPath, 0777, true);
        }

        $destPath = realpath($destPath);

        if (!file_exists($destPath)) {
            echo json_encode([
                'success' => false,
                'message' => sprintf("Destination directory '%s' does not exist.", $destPath),
            ]);

            return;
        }

        if (!is_writable($destPath)) {
            echo json_encode([
                'success' => false,
                'message' => sprintf("Destination directory '%s' does not have write permissions.", $destPath),
            ]);

            return;
        }

        $filePath = $destPath . "/bx_config.csv";

        if (false === move_uploaded_file($_FILES['bx_config']['tmp_name'], $filePath)) {
            echo json_encode([
                'success' => false,
                'message' => sprintf('Could not move %s to %s.', $_FILES['bx_config']['tmp_name'], $filePath),
            ]);

            return;
        }

        chmod($filePath, 0644);

        $config = new Shopware_Components_CsvIterator($filePath, ',');

        $header = $config->getHeader();
        if(!$this->isCSVFileValid($header)) {
            $config->__destruct();
            unlink($filePath);
            $valid_header = implode($this->header, ',');

            echo json_encode([
                'success' => false,
                'message' => "Format of CSV file is invalid. Header should be \"{$valid_header}\" separated by comma."
            ]);

            return;
        }

        echo json_encode([
            'success' => true,
            'message' => "Successfully uploaded",
        ]);
    }

    protected function isCSVFileValid($header) {

        $valid = true;
        if(is_array($header) && sizeof($header) == sizeof($this->header)) {
            foreach ($header as $col) {
                if(!in_array($col, $this->header)) {
                    $valid = false;
                    break;
                }
            }
        } else {
            $valid = false;
        }
        return $valid;
    }

    protected function getImportConfig() {

        $config = array();
        $filePath = Shopware()->DocPath('media_temp') . "bx_config.csv";
        $csv = new Shopware_Components_CsvIterator($filePath, ',');
        foreach ($csv as $row) {
            $row['exclude'] = intval($row['exclude']);
            $config[$row['name']] = $row;
        }
        $csv->__destruct();
        unlink($filePath);
        return $config;
    }

    public function applyConfigAction() {

        $stores = json_decode($this->Request()->getParam('stores'), true);
        if(!empty($stores)) {

            $config = json_decode($this->Request()->getParam('exportedConfig'), true);
            $db = Shopware()->Db();
            try{
                foreach ($config as $element) {
                    if(!$element['exclude']) {
                        if($element['type'] != 'text') {
                            $value = serialize(intval($element['value']));
                        } else {
                            $value = serialize($element['value']);
                        }
                        $element_id = $element['id'];
                        foreach ($stores as $storeId)  {
                            $sql = "INSERT INTO s_core_config_values (element_id, shop_id, value) VALUES('" . $element_id .
                                "', '" . $storeId . "', '" . $value . "') ON DUPLICATE KEY UPDATE value=VALUES(value)";

                            $db->query($sql);
                        }
                    }
                }
            } catch (\Exception $e) {
                return;
            }
        }
        echo "applied";
        exit;
    }

    protected function getDefaultStoreId() {
        $db = Shopware()->Db();
        $sql = $db->select()->from(array('s' => 's_core_shops'), array('id'))
            ->where('s.default = 1');
        $stmt = $db->query($sql);
        if($stmt->rowCount()) {
            $row = $stmt->fetch();
            $id = $row['id'];
        } else {
            $id = 1;
        }
        return $id;
    }

    public function getStoreConfigAction() {

        $store_id = $this->Request()->getParam('store_id', $this->getDefaultStoreId());
        $load_import = $this->Request()->getParam('load_import');
        $configForm = array();
        $importedConfig = array();
        if($load_import) {
            $importedConfig = $this->getImportConfig();
        }

        $db = Shopware()->Db();
        $sql = $db->select()->from(array('c_f' => 's_core_config_forms'),
            array('c_e.name',
                'value' => new \Zend_Db_Expr("CASE WHEN c_v.value IS NULL THEN c_e.value ELSE c_v.value END"),
                'c_e.label', 'c_e.type', 'c_e.id'))
            ->joinLeft(array('c_e' => 's_core_config_elements'), 'c_e.form_id = c_f.id', array())
            ->joinLeft(array('c_v' => 's_core_config_values'), 'c_v.element_id = c_e.id AND c_v.shop_id = ' . intval($store_id), array())
            ->where('c_f.name = ?', 'Boxalino')
            ->order('c_e.position')
        ;
        $stmt = $db->query($sql);
        while($row = $stmt->fetch()) {
            $row['value'] = !empty($importedConfig) && isset($importedConfig[$row['name']]) ? $importedConfig[$row['name']]['value'] : unserialize($row['value']);
            $row['exclude'] = !empty($importedConfig) && isset($importedConfig[$row['name']]) ? $importedConfig[$row['name']]['exclude'] : 0;
            $configForm[] = $row;
        }

        $this->View()->assign(array(
            'success' => true,
            'data' => $configForm
        ));
    }

    public function saveConfigAction() {

        $config = json_decode($this->Request()->getParam('exportedConfig'), true);
        if(!is_null($config)) {
            $file_name = Shopware()->DocPath('media_temp') . "bx_temp_config.csv";
            $fh = fopen($file_name, 'a');
            fputcsv($fh, $this->header, ',', '"');
            foreach($config as $dataRow){
                $data = array();
                foreach ($this->header as $key) {
                    $data[] = $dataRow[$key];
                }
                fputcsv($fh, $data, ',', '"');
            }
            fclose($fh);
            echo "saved";
        }
        exit();
    }

    public function exportConfigAction() {

        $file_name = Shopware()->DocPath('media_temp') . "bx_temp_config.csv";
        if(ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }
        $mime = 'application/force-download';
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private',false);
        header('Content-Type: '.$mime);
        header('Content-Disposition: attachment; filename="'.basename($file_name).'"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.filesize($file_name));
        readfile($file_name);
        unlink($file_name);
        exit();
    }
}
