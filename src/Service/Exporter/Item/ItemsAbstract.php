<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\ExporterInterface;

class ItemsAbstract implements ExporterInterface
{

    protected $files;
    protected $account;
    protected $shopProductIds;

    public function export()
    {

    }

    /**
     * @param mixed $account
     * @return Product
     */
    public function setAccount($account)
    {
        $this->account = $account;
        return $this;
    }

    /**
     * @param mixed $files
     * @return Product
     */
    public function setFiles($files)
    {
        $this->files = $files;
        return $this;
    }

    public function setShopProductIds($ids)
    {
        $this->shopProductIds = $ids;
        return $this;
    }

}