<?php declare(strict_types=1);

namespace MateuszMesek\DocumentDataAdapterDB\Model;

use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;

class Serializer implements SerializerInterface
{
    public function __construct(
        private readonly Json $json
    )
    {
    }

    public function serialize($data)
    {
        $result = json_encode($data, JSON_PRESERVE_ZERO_FRACTION + JSON_INVALID_UTF8_SUBSTITUTE);

        if (false === $result) {
            $error = json_last_error_msg();

            throw new InvalidArgumentException("Unable to serialize value. Error: $error");
        }

        return $result;
    }

    public function unserialize($string)
    {
        return $this->json->unserialize($string);
    }
}
