<?php

namespace Maven\Bundle\EbayConnectorBundle\Processor;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Akeneo\Component\Batch\Item\AbstractConfigurableStepElement;
use Akeneo\Component\Batch\Item\ItemProcessorInterface;

use Pim\Component\Catalog\Builder\ProductBuilderInterface;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\BaseConnectorBundle\Validator\Constraints\Channel;

use Maven\Bundle\EbayConnectorBundle\Serializer\Normalizer\TransportConfigInterface;

/**
 * Class ProductToEbayItemProcessor
 *
 * @package Maven\Bundle\EbayConnectorBundle\Processor
 */
class ProductToEbayItemProcessor extends AbstractConfigurableStepElement implements ItemProcessorInterface
{
    const DESCRIPION = 'If you are not satisfied, return the item for refund.';
    const FREE_PRICE = 0.00;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Channel
     *
     * @var string Channel code
     */
    protected $channel;

    /**
     * @var ChannelManager
     */
    private $channelManager;

    /**
     * @var ProductBuilderInterface
     */
    private $productBuilder;

    /**
     * @Assert\Type("string")
     * @var string
     */
    protected $payPalEmail;

    /**
     * @Assert\Type("string")
     * @var string
     */
    protected $baseUrl;

    /**
     * @var NormalizerInterface
     */
    protected $normalizer;

    /**
     * @Assert\Type("numeric")
     * @var int
     */
    protected $postalCode;

    /**
     * @Assert\Type("string")
     * @var string
     */
    protected $country;

    /**
     * @var string
     */
    protected $refundOption;

    /**
     * @var string
     */
    protected $returnDescription;

    /**
     * @Assert\Type("string")
     * @var string
     */
    protected $shippingService;

    /**
     * @var bool
     */
    protected $freeShipping;

    /**
     * @Assert\Type("numeric")
     * @var double
     */
    protected $shippingCost;

    /**
     * @param TransportConfigInterface $normalizer
     * @param ChannelManager           $channelManager
     * @param ProductBuilderInterface  $productBuilder
     */
    public function __construct(
        TransportConfigInterface $normalizer,
        ChannelManager $channelManager,
        ProductBuilderInterface $productBuilder
    ) {
        $this->normalizer = $normalizer;
        $this->channelManager = $channelManager;
        $this->productBuilder = $productBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return [
            'channel' => [
                'type'    => 'choice',
                'options' => [
                    'choices'  => $this->channelManager->getChannelChoices(),
                    'required' => true,
                    'select2'  => true,
                    'label'    => 'pim_base_connector.export.channel.label',
                    'help'     => 'pim_base_connector.export.channel.help',
                ],
            ],
            'country'  => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.country.label',
                    'help'  => 'maven_ebay_connector.ebay.country.help'
                ]
            ],
            'postalCode'  => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.postal_code.label',
                    'help'  => 'maven_ebay_connector.ebay.postal_code.help'
                ]
            ],
            'payPalEmail' => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.paypal.email.label',
                    'help'  => 'maven_ebay_connector.ebay.paypal.email.help'
                ]
            ],
            'baseUrl'     => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.base_url.label',
                    'help'  => 'maven_ebay_connector.ebay.base_url.help'
                ]
            ],
            'returnDescription' => [
                'options' => [
                    'label'    => 'maven_ebay_connector.ebay.return_description.label',
                    'help'     => 'maven_ebay_connector.ebay.return_description.help'
                ]
            ],
            'refundOption' => [
                'type'    => 'choice',
                'options' => array(
                    'choices'  => array(
                        'MoneyBack' => 'MoneyBack',
                        'MoneyBackOrReplacement' => 'MoneyBackOrReplacement',
                    ),
                    'select2'  => true,
                    'label'    => 'maven_ebay_connector.ebay.refund_option.label',
                    'help'     => 'maven_ebay_connector.ebay.refund_option.help'
                )
            ],
            'shippingService' => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.shipping_service.label',
                    'help'  => 'maven_ebay_connector.ebay.shipping_service.help'
                ]
            ],
            'freeShipping' => [
                'type' => 'switch',
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.free_shipping.label',
                ]
            ],
            'shippingCost' => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.shipping_cost.label'
                ]
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function process($product)
    {
        $contextChannel = $this->channelManager->getChannelByCode($this->channel);
        $this->productBuilder->addMissingProductValues(
            $product,
            [$contextChannel],
            $contextChannel->getLocales()
                ->toArray()
        );

        foreach ($product->getCategories() as $category) {
            if (null === $category->getEbayId()) {
                throw new \Exception(sprintf('Category %s must have ebayId', $category->getCode()));
            }
        }

        $normalizer = $this->normalizer;

        $normalizer
            ->addTransportValue('country', $this->getCountry())
            ->addTransportValue('refundOption', $this->getRefundOption())
            ->addTransportValue('returnDescription', $this->getReturnDescription())
            ->addTransportValue('shippingCost', $this->getShippingCost())
            ->addTransportValue('freeShipping', $this->isFreeShipping())
            ->addTransportValue('shippingService', $this->getShippingService())
            ->addTransportValue('baseUrl', $this->getBaseUrl())
            ->addTransportValue('payPalEmail', $this->getPayPalEmail())
            ->addTransportValue('postalCode', $this->getPostalCode());

        return $normalizer->normalize($product, null, ['channel' => $this->channel]);
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country ? $this->country : 'US' ;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getRefundOption()
    {
        return $this->refundOption;
    }

    /**
     * @param string $refundOption
     */
    public function setRefundOption($refundOption)
    {
        $this->refundOption = $refundOption;
    }

    /**
     * @return mixed
     */
    public function getReturnDescription()
    {
        return $this->returnDescription ? $this->returnDescription : self::DESCRIPION;
    }

    /**
     * @param mixed $returnDescription
     */
    public function setReturnDescription($returnDescription)
    {
        $this->returnDescription = $returnDescription;
    }

    /**
     * @return float
     */
    public function getShippingCost()
    {
        return $this->freeShipping ? self::FREE_PRICE : $this->shippingCost;
    }

    /**
     * @param float $shippingCost
     */
    public function setShippingCost($shippingCost)
    {
        $this->shippingCost = $shippingCost;
    }

    /**
     * @return boolean
     */
    public function isFreeShipping()
    {
        return $this->freeShipping;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @return string
     */
    public function getPayPalEmail()
    {
        return $this->payPalEmail;
    }

    /**
     * @param string $payPalEmail
     */
    public function setPayPalEmail($payPalEmail)
    {
        $this->payPalEmail = $payPalEmail;
    }

    /**
     * @return int
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param $postalCode
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
    }

    /**
     * @param boolean $freeShipping
     */
    public function setFreeShipping($freeShipping)
    {
        $this->freeShipping = $freeShipping;
    }

    /**
     * @return string
     */
    public function getShippingService()
    {
        return $this->shippingService;
    }

    /**
     * @param string $shippingService
     */
    public function setShippingService($shippingService)
    {
        $this->shippingService = $shippingService;
    }

    /**
     * Set channel
     *
     * @param string $channelCode Channel code
     *
     * @return $this
     */
    public function setChannel($channelCode)
    {
        $this->channel = $channelCode;

        return $this;
    }

    /**
     * Get channel
     *
     * @return string Channel code
     */
    public function getChannel()
    {
        return $this->channel;
    }
}
