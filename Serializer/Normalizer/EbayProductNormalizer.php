<?php

namespace Maven\Bundle\EbayConnectorBundle\Serializer\Normalizer;

use Akeneo\Component\Batch\Item\InvalidItemException;
use Pim\Component\Catalog\Model\Product;

/**
 * Class EbayProductNormalizer
 *
 * @package Maven\Bundle\EbayConnectorBundle\Serializer\Normalizer
 */
class EbayProductNormalizer implements TransportConfigInterface
{
    const IMAGE_PLACEHOLDER = 'https://cdn.shopify.com/s/files/1/1118/7224/products/'.
        'Image-Placeholder-Rockin-Monkey-Designs-1164x1164_419506c5-0064-429e-a9e8-3a5a433489f4.jpg?v=1454878322';
    /**
     * @var array
     */
    protected $transport;

    /**
     * EbayProductNormalizer constructor.
     */
    public function __construct()
    {
        $this->transport = [];
    }

    /**
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function addTransportValue($name, $value)
    {
        if (! array_key_exists($name, $this->transport)) {
            $this->transport[$name] = $value;
        }

        return $this;
    }

    /**
     * @param $name
     *
     * @return mixed|null
     */
    protected function getTransportValue($name)
    {
        if (array_key_exists($name, $this->transport)) {
            return $this->transport[$name];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $picture = $object->getValue('picture');
        if (null !== $picture && null !== $picture->getMedia()) {
            $path = sprintf(
                '%s/media/cache/preview/%s',
                $this->getTransportValue('baseUrl'),
                $picture->getMedia()
                    ->getKey()
            );
        }

        if (is_null($object->name)) {
            throw new \Exception(sprintf('Please add name to product with id = %d', $object->getId()));
        }

        $item =  [
                'Title'                  => $object->name->getData(),
                'Description'            => $object->getValue('description', 'en_US', $context['channel'])->getData(),
                'PrimaryCategory'        => [
                    'CategoryID' => $object->getCategories()
                        ->first()
                        ->getEbayId(),
                ],
                'CategoryMappingAllowed' => true,
                'Country'                => $this->getTransportValue('country'),
                'Quantity'               => $this->getValue($object, 'quantity', 1),
                'StartPrice'             => $object->price->getPrice('USD')
                    ->getData(),
                'Currency'               => 'USD',
                'PaymentMethods'         => 'PayPal',
                'PayPalEmailAddress'     => $this->getTransportValue('payPalEmail'),
                'ListingType'            => 'FixedPriceItem',
                'ListingDuration'        => $this->getValue($object, 'listingDuration', 'Days_3'),
                'SKU'                    => $this->getValue($object, 'sku'),
                'InventoryTrackingMethod'=> 'SKU',
                'ConditionID'            => $this->getValue($object, 'condition', 1000),
                'DispatchTimeMax'        => $this->getValue($object, 'dispatchTimeMax', 1),
                'PostalCode'             => $this->getTransportValue('postalCode'),

                'ReturnPolicy'           => [
                    'ReturnsAcceptedOption' => 'ReturnsAccepted',
                    'RefundOption'          => $this->getTransportValue('refundOption'),
                    'ReturnsWithinOption'   => 'Days_30',
                    'Description'           => $this->getTransportValue('returnDescription'),
                    'ShippingCostPaidByOption' => 'Buyer'
                ],
                'ShippingDetails' => [
                    'ShippingType' => 'Flat',
                    'ShippingServiceOptions' => [
                        'ShippingServicePriority' => 1,
                        'ShippingService'         => $this->getTransportValue('shippingService'),
                        'FreeShipping'            => $this->getTransportValue('freeShipping'),
                        'ShippingServiceAdditionalCost' => $this->getTransportValue('shippingCost')
                    ]
                ],
                'PictureDetails' => [
                    'PictureURL' => isset($path) ? $path : self::IMAGE_PLACEHOLDER
                ]
        ];

        if (!empty($specifics =  $this->getSpecifics($object))) {
            $item['ItemSpecifics'] = $specifics;
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Product;
    }

    /**
     * @param Object $object
     * @param string $value
     * @param null   $default
     *
     * @return null
     */
    private function getValue($object, $value, $default = null)
    {
        return $object->getValue($value) ? $object->getValue($value)->getData() : $default;
    }

    /**
     * @param Object $object
     *
     * @return array
     */
    private function getSpecifics($object)
    {
        $result = [];
        foreach ($this->getEbayAttributes($object) as $item) {
            $result[] = [
                'NameValueList' => [
                    'Name'  => $item->getAttribute()->getTranslation('en_US')->getLabel(),
                    'Value' => $item->getOption()->getCode()
                ]
            ];
        }

        return $result;
    }

    /**
     * @param Object $object
     *
     * @return array
     */
    private function getEbayAttributes($object)
    {
        $pattern = '/(ebay_.\w+)/';

        return $this->pregGrepKeys($pattern, $object->getValues()->toArray());
    }

    /**
     * @param string $pattern
     * @param array  $input
     *
     * @return array
     */
    private function pregGrepKeys($pattern, $input)
    {
        $result = [];
        $keys =  preg_grep($pattern, array_keys($input));
        foreach ($keys as $key) {
            $result[$key] = $input[$key];
        }

        return $result;
    }
}
