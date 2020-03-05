<?php

/**
 * Class Shopware_Plugins_Frontend_Boxalino_DataExporter
 * Data exporter
 * Updated to export the stores serialized instead of in a loop
 */
class Shopware_Plugins_Frontend_Boxalino_DataExporter
{

    CONST BOXALINO_EXPORTER_TYPE_DELTA = "delta";
    CONST BOXALINO_EXPORTER_TYPE_FULL = "full";

    CONST BOXALINO_EXPORTER_STATUS_SUCCESS = "success";
    CONST BOXALINO_EXPORTER_STATUS_FAIL = "fail";
    CONST BOXALINO_EXPORTER_STATUS_PROCESSING = "processing";

    protected $request;
    protected $manager;

    protected $propertyDescriptions = array();
    protected $dirPath = null;
    protected $db;
    protected $log;
    protected $delta = false;
    protected $deltaLast;
    protected $fileHandle;
    protected $deltaIds = array();
    protected $_config;
    protected $bxData;
    protected $_attributes = array();
    protected $shopProductIds = array();
    protected $rootCategories = array();

    protected $account = null;
    protected $files = null;

    protected $translationFields = array(
        'name',
        'keywords',
        'description',
        'description_long',
        'attr1',
        'attr2',
        'attr3',
        'attr4',
        'attr5'
    );

    /**
     * Data Exporter constructor
     *
     * @param string $dirPath
     * @param bool   $delta
     */
    public function __construct()
    {
        $this->dirPath = Shopware()->DocPath('media_temp_boxalinoexport');
        $this->db = Shopware()->Db();
        $this->log = Shopware()->Container()->get('pluginlogger');
        $libPath = __DIR__ . '/lib';
        require_once($libPath . '/BxClient.php');
        \com\boxalino\bxclient\v1\BxClient::LOAD_CLASSES($libPath);
    }



    /**
     * @param $id
     * @return array
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    protected function getShopCategoryIds($id)
    {
        $shopCat = array();
        $db = $this->db;
        $sql = $db->select()
            ->from('s_core_shops', array('id', 'category_id'))
            ->where($this->qi('id') . ' = ?', $id)
            ->orWhere($this->qi('main_id') . ' = ?', $id);
        $stmt = $db->query($sql);
        if($stmt->rowCount()) {
            while($row = $stmt->fetch()) {
                $shopCat[$row['id']] = $row['category_id'];
            }
        }
        return $shopCat;
    }






























    /**
     * @return string
     */
    public function getLastDelta()
    {
        if (empty($this->deltaLast)) {
            $this->deltaLast = date("Y-m-d H:i:s", strtotime("-30 minutes"));
            $sql = $this->db->select()
                ->from('boxalino_exports', array('export_date'))
                ->where('account = ?', $this->getAccount())
                ->where('status = ?', self::BOXALINO_EXPORTER_STATUS_SUCCESS)
                ->order('export_date', "DESC")
                ->limit(1);
            $latestRecord = $this->db->fetchOne($sql);
            if($latestRecord)
            {
                $this->deltaLast = $latestRecord;
            }
        }

        return $this->deltaLast;
    }


    /**
     * wrapper to quote database identifiers
     *
     * @param  string $identifier
     * @return string
     */
    protected function qi($identifier) {
        return $this->db->quoteIdentifier($identifier);
    }


}