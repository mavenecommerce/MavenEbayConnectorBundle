<?php

namespace Maven\Bundle\EbayConnectorBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Interface TransportConfigInterface
 *
 * @package Maven\Bundle\EbayConnectorBundle\Serializer\Normalizer
 */
interface TransportConfigInterface extends NormalizerInterface
{
    /**
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function addTransportValue($name, $value);
}
