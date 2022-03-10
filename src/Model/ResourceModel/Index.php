<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataIndexerDB\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Index extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(
            'index_pattern',
            'id'
        );
    }

    /**
     * @param string $tableName
     * @return string
     */
    public function getTable($tableName): string
    {
        return parent::getTable("document_data_$tableName");
    }
}
