<?php

namespace Maven\Bundle\EbayConnectorBundle\Writer\Ebay;

use Akeneo\Component\Batch\Model\StepExecution;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Akeneo\Component\Batch\Step\StepExecutionAwareInterface;
use Akeneo\Component\Batch\Item\AbstractConfigurableStepElement;
use Akeneo\Component\Batch\Item\ItemWriterInterface;

use Maven\Bundle\EbayConnectorBundle\Serializer\Encoder\EbayListItemsEncoder;
use Maven\Bundle\EbayConnectorBundle\API\EbayClient;

/**
 * Class ProductWriter
 *
 * @package Maven\Bundle\EbayConnectorBundle\Writer\Ebay
 */
class EbayProductWriter extends AbstractConfigurableStepElement implements
    ItemWriterInterface,
    StepExecutionAwareInterface
{
    const METHOD = 'AddItems';
    const WARNING = 'Warning';
    const US = 0;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Assert\Type("string")
     * @var string
     */
    protected $sandboxDevId;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Assert\Type("string")
     * @var string
     */
    protected $sandboxAppId;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Assert\Type("string")
     * @var string
     */
    protected $sandboxCertId;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Assert\Type("string")
     * @var string
     */
    protected $sandboxAuthToken;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Assert\Type("string")
     * @var string
     */
    protected $productionDevId;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Assert\Type("string")
     * @var string
     */
    protected $productionAppId;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Assert\Type("string")
     * @var string
     */
    protected $productionCertId;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Assert\Type("string")
     * @var string
     */
    protected $productionAuthToken;

    /**
     * @Assert\Type(type="bool")
     * @var bool
     */
    protected $production;

    /**
     * @var   EbayClient
     */
    protected $ebayClient;

    /**
     * @var EncoderInterface
     */
    protected $encoder;

    /**
     * @Assert\NotBlank(groups={"Execution"})
     * @Assert\Type(type="numeric")
     * @var string
     */
    protected $siteId;

    /**
     * @var StepExecution
     */
    protected $stepExecution;

    /**
     * EbayProductWriter constructor.
     *
     * @param EncoderInterface $encoder
     */
    public function __construct(EncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationFields()
    {
        return [
            'sandboxDevId' => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.sandbox.dev_id.label',
                    'help'  => 'maven_ebay_connector.ebay.sandbox.dev_id.help'
                ]
            ],
            'sandboxAppId' => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.sandbox.app_id.label',
                    'help'  => 'maven_ebay_connector.ebay.sandbox.app_id.help'
                ]
            ],
            'sandboxCertId' => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.sandbox.cert_id.label',
                    'help'  => 'maven_ebay_connector.ebay.sandbox.cert_id.help'
                ]
            ],
            'sandboxAuthToken' => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.sandbox.auth_token.label',
                    'help'  => 'maven_ebay_connector.ebay.sandbox.auth_token.help'
                ]
            ],
            'productionDevId' => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.production.dev_id.label',
                    'help'  => 'maven_ebay_connector.ebay.production.dev_id.help'
                ]
            ],
            'productionAppId' => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.production.app_id.label',
                    'help'  => 'maven_ebay_connector.ebay.production.app_id.help'
                ]
            ],
            'productionCertId' => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.production.cert_id.label',
                    'help'  => 'maven_ebay_connector.ebay.production.cert_id.help'
                ]
            ],
            'productionAuthToken' => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.production.auth_token.label',
                    'help'  => 'maven_ebay_connector.ebay.production.auth_token.help'
                ]
            ],
            'production' => [
                'type'    => 'switch',
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.production.label',
                    'help'  => 'maven_ebay_connector.ebay.production.help'
                ],
            ],
            'siteId'     => [
                'options' => [
                    'label' => 'maven_ebay_connector.ebay.siteId.label',
                    'help'  => 'maven_ebay_connector.ebay.siteId.help'
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     *
     */
    public function write(array $items)
    {
        if (!is_numeric($this->getSiteId()) || empty($this->getToken())) {
            throw new \Exception('Please provide all data for connection with eBay');
        }

        $sequences = $this->getEbaySequence($items);

        $ebay = $this->getEbayClient();
        $errors = [];

        foreach ($sequences as $sequence) {
            $response = $ebay->sendRequest($sequence, 'AddItems');
            $response = simplexml_load_string($response);
            if (count($response->Errors) > self::US && current($response->Errors->SeverityCode) !== self::WARNING) {
                array_push($errors, current($response->Errors->LongMessage));
                continue;
            }
        }

        if (count($errors) > self::US) {
            throw new \Exception(implode('.', $errors));
        }

        $this->stepExecution->incrementSummaryInfo('write', count($items));
    }

    /**
     * @return int
     */
    public function getSiteId()
    {
        return $this->siteId ? $this->siteId : self::US;
    }

    /**
     * @param int $siteId
     *
     * @return $this
     */
    public function setSiteId($siteId)
    {
        $this->siteId = $siteId;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->isProduction() ? $this->getProductionAuthToken(): $this->getSandboxAuthToken();
    }

    /**
     * @return bool
     */
    public function isProduction()
    {
        return (bool)$this->production;
    }

    /**
     * @param bool $production
     */
    public function setProduction($production)
    {
        $this->production = $production;
    }

    /**
     * @return string
     */
    public function getProductionAuthToken()
    {
        return $this->productionAuthToken;
    }

    /**
     * @param string $productionAuthToken
     */
    public function setProductionAuthToken($productionAuthToken)
    {
        $this->productionAuthToken = $productionAuthToken;
    }

    /**
     * @return string
     */
    public function getSandboxAuthToken()
    {
        return $this->sandboxAuthToken;
    }

    /**
     * @param string $sandboxAuthToken
     */
    public function setSandboxAuthToken($sandboxAuthToken)
    {
        $this->sandboxAuthToken = $sandboxAuthToken;
    }

    /**
     * @param $items
     *
     * @return array
     */
    private function getEbaySequence($items)
    {
        $encoder = new EbayListItemsEncoder('AddItemsRequest');
        $index = $offset = self::US;
        $length = 5;
        $result = [];
        while (array_key_exists($index+1, $items)) {
            if (array_key_exists($offset, $items)) {
                foreach (array_slice($items, $offset, $length) as $index => $item) {
                    $part[] = [
                        'Item' => $item,
                        'MessageID' => $index+1
                    ];
                }
                $part['RequesterCredentials'] = [
                    'eBayAuthToken' => $this->getToken()
                ];

                $result[] = $encoder->encode($part, 'xml');
                unset($part);
            }

            $offset+=$length;
            $index = $offset;
        }

        return $result;
    }

    /**
     * @return EbayClient
     */
    protected function getEbayClient()
    {
        if (is_null($this->ebayClient)) {
            $this->ebayClient = new EbayClient(
                $this->getToken(),
                $this->getDevId(),
                $this->getAppId(),
                $this->getCertId(),
                $this->getSiteId(),
                self::METHOD,
                !$this->isProduction()
            );
        }

        return $this->ebayClient;
    }

    /**
     * @return string
     */
    public function getDevId()
    {
        return $this->isProduction() ? $this->getProductionDevId() : $this->getSandboxDevId();
    }

    /**
     * @return string
     */
    public function getProductionDevId()
    {
        return $this->productionDevId;
    }

    /**
     * @param string $productionDevId
     */
    public function setProductionDevId($productionDevId)
    {
        $this->productionDevId = $productionDevId;
    }

    /**
     * @return string
     */
    public function getSandboxDevId()
    {
        return $this->sandboxDevId;
    }

    /**
     * @param string $sandboxDevId
     */
    public function setSandboxDevId($sandboxDevId)
    {
        $this->sandboxDevId = $sandboxDevId;
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->isProduction() ? $this->getProductionAppId(): $this->getSandboxAppId();
    }

    /**
     * @return string
     */
    public function getProductionAppId()
    {
        return $this->productionAppId;
    }

    /**
     * @param string $productionAppId
     */
    public function setProductionAppId($productionAppId)
    {
        $this->productionAppId = $productionAppId;
    }

    /**
     * @return string
     */
    public function getSandboxAppId()
    {
        return $this->sandboxAppId;
    }

    /**
     * @param string $sandboxAppId
     */
    public function setSandboxAppId($sandboxAppId)
    {
        $this->sandboxAppId = $sandboxAppId;
    }

    /**
     * @return string
     */
    public function getCertId()
    {
        return $this->isProduction() ? $this->getProductionCertId(): $this->getSandboxCertId();
    }

    /**
     * @return string
     */
    public function getProductionCertId()
    {
        return $this->productionCertId;
    }

    /**
     * @param string $productionCertId
     */
    public function setProductionCertId($productionCertId)
    {
        $this->productionCertId = $productionCertId;
    }

    /**
     * @return string
     */
    public function getSandboxCertId()
    {
        return $this->sandboxCertId;
    }

    /**
     * @param string $sandboxCertId
     */
    public function setSandboxCertId($sandboxCertId)
    {
        $this->sandboxCertId = $sandboxCertId;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return get_class($this);
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }
}
