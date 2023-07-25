<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Index extends AbstractDb
{
    public const FIELD_DOCUMENT_ID = 'document_id';
    public const FIELD_NODE_PATH = 'node_path';
    public const FIELD_NODE_VALUE = 'node_value';

    protected function _construct()
    {
        $this->_init(
            'document_data_index_pattern',
            'id'
        );
    }
}
