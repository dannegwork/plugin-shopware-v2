<?php
namespace Boxalino\IntelligenceFramework\Service\Exporter\Item;

class Stream extends ItemsAbstract
{

    public function export()
    {
        // TODO: Implement export() method.
    }

    /**
     * Export product streams from s_product_streams_selection
     * Save the data to the product_stream.csv file
     *
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function exportProductStreams()
    {
        $files = $this->getFiles();
        $db = $this->db;
        $data = array();
        $header = true;
        $count = 0;
        $sql = $db->select()
            ->from(array('a' => 's_articles'), array())
            ->join(
                array('d' => 's_articles_details'),
                $this->qi('d.articleID') . ' = ' . $this->qi('a.id') . ' AND ' .
                $this->qi('d.kind') . ' <> ' . $db->quote(3),
                array('id')
            )
            ->join(
                array('s_s' => 's_product_streams_selection'),
                $this->qi('s_s.article_id') . ' = ' . $this->qi('a.id'),
                array('stream_id')
            );
        if ($this->delta) {
            $sql->where('a.id IN(?)', $this->deltaIds);
        }
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch()) {
            if (!isset($this->shopProductIds[$row['id']])) {
                continue;
            }
            if ($header) {
                $data[] = array_keys($row);
                $header = false;
            }

            $data[] = $row;
            if(sizeof($data) > 1000) {
                $files->savepartToCsv('product_stream.csv', $data);
                $data = [];
            }
        }

        $files->savepartToCsv('product_stream.csv', $data);
        $attributeSourceKey = $this->bxData->addCSVItemFile($files->getPath('product_stream.csv'), 'id');
        $this->bxData->addSourceStringField($attributeSourceKey, "stream_id", "stream_id");
    }

}