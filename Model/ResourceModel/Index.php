<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Index extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(
            'document_data_index_pattern',
            'id'
        );
    }
}
