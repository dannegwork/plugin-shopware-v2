<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Helper;

class Content
{

    /**
     * @var string
     */
    public $XML_DELIMITER = ',';

    /**
     * @var string
     */
    public $XML_ENCLOSURE = '"';

    /**
     * @var string
     */
    protected $_mainDir;

    /**
     * @var string
     */
    protected $account;
    protected $_dir;
    protected $type;

    public function __construct()
    {
        $this->init();
    }

    protected function init() : void
    {
        $this->cleanDir($this->_mainDir);
        $this->_dir = $this->_mainDir . $this->account . '_' . $this->type . '_' . microtime(true);
        if (!file_exists($this->_dir)) {
            mkdir($this->_dir, 0777, true);
        }else{
            $this->delTree($this->_dir);
            mkdir($this->_dir, 0777, true);
        }
    }

    public function setAccount($account) : void
    {
        $this->account = $account;
    }

    public function setType($type) : void
    {
        $this->type = $type;
    }

    public function setMainDir($dirPath) : void
    {
        $this->_mainDir = $dirPath;
    }

    protected function cleanDir($dir) : void
    {
        foreach (scandir($dir) as $el) {
            if(!is_dir($el)) {
                rmdir($dir . $el);
            }
        }
    }

    protected function delTree($dir) : void
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            if (is_dir("$dir/$file")) {
                self::delTree("$dir/$file");
            } else if (file_exists("$dir/$file")) {
                @unlink("$dir/$file");
            }
        }

        return rmdir($dir);
    }

    public function savePartToCsv($file, &$data) : void
    {
        $path = $this->getPath($file);
        $fh = fopen($path, 'a');
        foreach($data as $dataRow){
            fputcsv($fh, $dataRow, $this->XML_DELIMITER, $this->XML_ENCLOSURE);
        }

        fclose($fh);
        $data = null;
    }


    public function getFileContents($file)
    {
        return file_get_contents($this->getPath($file));
    }

    public function getPath($file)
    {
        //save
        if (!in_array($file, $this->_files)) {
            $this->_files[] = $file;
        }

        return $this->_dir . '/' . $file;
    }

}