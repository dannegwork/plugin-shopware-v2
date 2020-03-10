<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

use Boxalino\IntelligenceFramework\Service\Exporter\ExporterInterface;

class Review extends ItemsAbstract
{

    public function export()
    {
        // TODO: Implement export() method.
    }

    /**
     * Export item votes to vote.csv and product_vote.csv
     *
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function exportItemVotes()
    {
        $files = $this->getFiles();
        $db = $this->db;
        $data = array();
        $header = true;
        $sql = $db->select()
            ->from(array('a' => 's_articles_vote'),
                array('average' => new Zend_Db_Expr("SUM(a.points) / COUNT(a.id)"), 'articleID'))
            ->where('a.active = 1')
            ->group('a.articleID');
        if ($this->delta) {
            $sql->where('a.articleID IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            if ($header) {
                $data[] = array_keys($row);
                $header = false;
            }
            if(sizeof($data) > 1000) {
                $files->savepartToCsv('vote.csv', $data);
                $data = [];
            }
            $data[] = $row;
        }
        if($header) {
            $data[] = array('average', 'articleID');
        }
        $files->savepartToCsv('vote.csv', $data);
        $referenceKey = $this->bxData->addResourceFile($files->getPath('vote.csv'), 'articleID', ['average']);

        $data = array();
        $header = true;
        $sql = $db->select()
            ->from(array('a' => 's_articles'), array())
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id', 'articleID')
            );
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        if ($stmt->rowCount()) {
            while ($row = $stmt->fetch()) {
                if(!isset($this->shopProductIds[$row['id']])) {
                    continue;
                }
                if ($header) {
                    $data[] = array_keys($row);
                    $header = false;
                }
                $data[$row['id']] = array('id' => $row['id'], 'articleID' => $row['articleID']);
                if(sizeof($data) > 1000) {
                    $files->savepartToCsv('product_vote.csv', $data);
                    $data = array();
                }
            }
        }
        $files->savepartToCsv('product_vote.csv', $data);
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_vote.csv'), 'id');
        $this->bxData->addSourceNumberField($attributeSourceKey, "vote", "articleID", $referenceKey);
    }

}