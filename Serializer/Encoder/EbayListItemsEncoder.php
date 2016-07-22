<?php

namespace Maven\Bundle\EbayConnectorBundle\Serializer\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\scalar;
use Symfony\Component\Serializer\Encoder\SerializerAwareEncoder;
use UnexpectedValueException;

/**
 * Class EbayListItemsEncoder
 *
 * @package Maven\Bundle\EbayConnectorBundle\Serializer\Encoder
 */
class EbayListItemsEncoder extends SerializerAwareEncoder implements EncoderInterface
{
    /**
     * @var \DOMDocument
     */
    private $dom;
    private $format;
    private $context;
    private $rootNodeName = 'response';
    private $key;

    /**
     * Construct new XmlEncoder and allow to change the root node element name.
     *
     * @param string $rootNodeName
     * @param string $key
     */
    public function __construct($rootNodeName = 'AddItemsRequest', $key = 'AddItemRequestContainer')
    {
        $this->rootNodeName = $rootNodeName;
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format, array $context = array())
    {
        if ($data instanceof \DOMDocument) {
            return $data->saveXML();
        }

        $xmlRootNodeName = $this->resolveXmlRootName($context);

        $this->dom = $this->createDomDocument($context);
        $this->format = $format;
        $this->context = $context;

        if (null !== $data && !is_scalar($data)) {
            $root = $this->dom->createElementNS('urn:ebay:apis:eBLBaseComponents', $xmlRootNodeName);
            $this->dom->appendChild($root);
            $this->buildXml($root, $data, $xmlRootNodeName);

            return $this->dom->saveXML();
        }

        $this->appendNode($this->dom, $data, $xmlRootNodeName);

        return $this->dom->saveXML();
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding($format)
    {
        return 'xml' === $format;
    }

    /**
     * Sets the root node name.
     *
     * @param string $name root node name
     */
    public function setRootNodeName($name)
    {
        $this->rootNodeName = $name;
    }

    /**
     * Returns the root node name.
     *
     * @return string
     */
    public function getRootNodeName()
    {
        return $this->rootNodeName;
    }

    /**
     * @param \DOMNode $node
     * @param string   $val
     *
     * @return bool
     */
    final protected function appendXMLString(\DOMNode $node, $val)
    {
        if (strlen($val) > 0) {
            $frag = $this->dom->createDocumentFragment();
            $frag->appendXML($val);
            $node->appendChild($frag);

            return true;
        }

        return false;
    }

    /**
     * @param \DOMNode $node
     * @param string   $val
     *
     * @return bool
     */
    final protected function appendText(\DOMNode $node, $val)
    {
        $nodeText = $this->dom->createTextNode($val);
        $node->appendChild($nodeText);

        return true;
    }

    /**
     * @param \DOMNode $node
     * @param string   $val
     *
     * @return bool
     */
    final protected function appendCData(\DOMNode $node, $val)
    {
        $nodeText = $this->dom->createCDATASection($val);
        $node->appendChild($nodeText);

        return true;
    }

    /**
     * @param \DOMNode             $node
     * @param \DOMDocumentFragment $fragment
     *
     * @return bool
     */
    final protected function appendDocumentFragment(\DOMNode $node, $fragment)
    {
        if ($fragment instanceof \DOMDocumentFragment) {
            $node->appendChild($fragment);

            return true;
        }

        return false;
    }

    /**
     * Checks the name is a valid xml element name.
     *
     * @param string $name
     *
     * @return bool
     */
    final protected function isElementNameValid($name)
    {
        return $name &&
        false === strpos($name, ' ') &&
        preg_match('#^[\pL_][\pL0-9._:-]*$#ui', $name);
    }

    /**
     * Parse the data and convert it to DOMElements.
     *
     * @param \DOMNode     $parentNode
     * @param array|object $data
     * @param string|null  $xmlRootNodeName
     *
     * @return bool
     *
     * @throws UnexpectedValueException
     */
    private function buildXml(\DOMNode $parentNode, $data, $xmlRootNodeName = null)
    {
        $this->checkData($data);

        if (is_array($data) || $data instanceof \Traversable) {
            return $this->buildXmlForArray($parentNode, $data);
        }

        $data = $this->serializer->normalize($data, $this->format, $this->context);

        if (null !== $data && !is_scalar($data)) {
            return $this->buildXml($parentNode, $data, $xmlRootNodeName);
        }

        $this->setParentNodeExists($parentNode, $xmlRootNodeName);

        return $this->appendNode($parentNode, $data, $xmlRootNodeName);
    }

    /**
     * Selects the type of node to create and appends it to the parent.
     *
     * @param \DOMNode     $parentNode
     * @param array|object $data
     * @param string       $nodeName
     * @param string       $key
     *
     * @return bool
     */
    private function appendNode(\DOMNode $parentNode, $data, $nodeName, $key = null)
    {
        $node = $this->dom->createElement($nodeName);
        if (null !== $key) {
            $node->setAttribute('key', $key);
        }
        $appendNode = $this->selectNodeType($node, $data);
        // we may have decided not to append this node, either in error or if its $nodeName is not valid
        if ($appendNode) {
            $parentNode->appendChild($node);
        }

        return $appendNode;
    }

    /**
     * Checks if a value contains any characters which would require CDATA wrapping.
     *
     * @param string $val
     *
     * @return bool
     */
    private function needsCdataWrapping($val)
    {
        return preg_match('/[<>&]/', $val);
    }

    /**
     * Tests the value being passed and decide what sort of element to create.
     *
     * @param \DOMNode $node
     * @param mixed    $val
     *
     * @return bool
     */
    private function selectNodeType(\DOMNode $node, $val)
    {
        if (is_array($val) || $val instanceof \Traversable) {
            return $this->buildXml($node, $val);
        }
        if ($val instanceof \SimpleXMLElement) {
            $child = $this->dom->importNode(dom_import_simplexml($val), true);
            $node->appendChild($child);
            return true;
        }
        if (is_object($val)) {
            return $this->buildXml($node, $this->serializer->normalize($val, $this->format, $this->context));
        }

        if (is_string($val) && $this->needsCdataWrapping($val)) {
            return $this->appendCData($node, $val);
        }

        if ($val instanceof \DOMNode) {
            $child = $this->dom->importNode($val, true);
            $node->appendChild($child);
            return true;
        }

        return $this->appendText($node, $this->getTypedValue($val));
    }

    /**
     * Get real XML root node name, taking serializer options into account.
     *
     * @param array $context
     *
     * @return string
     */
    private function resolveXmlRootName(array $context = array())
    {
        return isset($context['xml_root_node_name'])
            ? $context['xml_root_node_name']
            : $this->rootNodeName;
    }

    /**
     * Create a DOM document, taking serializer options into account.
     *
     * @param array $context options that the encoder has access to.
     *
     * @return \DOMDocument
     */
    private function createDomDocument(array $context)
    {
        $document = new \DOMDocument('1.0', 'utf-8');

        // Set an attribute on the DOM document specifying, as part of the XML declaration,
        $xmlOptions = array(
            // nicely formats output with indentation and extra space
            'xml_format_output' => 'formatOutput',
            // the version number of the document
            'xml_version' => 'xmlVersion',
            // the encoding of the document
            'xml_encoding' => 'encoding',
            // whether the document is standalone
            'xml_standalone' => 'xmlStandalone',
        );
        foreach ($xmlOptions as $xmlOption => $documentProperty) {
            if (isset($context[$xmlOption])) {
                $document->$documentProperty = $context[$xmlOption];
            }
        }

        return $document;
    }

    /**
     * @param $val
     *
     * @return int|string
     */
    private function getTypedValue($val)
    {
        return is_bool($val) ? (int)$val : (string)$val;
    }

    /**
     * @param $data
     */
    private function checkData($data)
    {
        if (!is_array($data) && !is_object($data)) {
            throw new UnexpectedValueException(
                sprintf('An unexpected value could not be serialized: %s', var_export($data, true))
            );
        }
    }

    /**
     * @param $key
     * Use this function only for numeric keys!
     * @return string
     */
    private function setValidKey($key)
    {
        if (is_numeric($key) || !$this->isElementNameValid($key)) {
            $key = $this->key;
        }

        return $key;
    }

    /**
     * @param \DOMNode $parentNode
     * @param          $data
     *
     * @return bool
     */
    private function buildXmlForArray(\DOMNode $parentNode, $data)
    {
        foreach ($data as $key => $data) {
            if (0 === strpos($key, '@')
                && is_scalar($data)
                && $this->isElementNameValid(
                    $attributeName = substr($key, 1)
                )
            ) {
                $parentNode->setAttribute($attributeName, $data);
                continue;
            }

            if ($key === '#') {
                $append = $this->selectNodeType($parentNode, $data);
                continue;
            }

            if (is_array($data) && false === is_numeric($key) && ctype_digit(implode('', array_keys($data)))) {
                foreach ($data as $subData) {
                    $append = $this->appendNode($parentNode, $subData, $key);
                }
                continue;
            }
            $key = $this->setValidKey($key);

            $append = $this->appendNode($parentNode, $data, $key);
        }

        return $append;
    }

    /**
     * @param $parentNode
     * @param $xmlRootNodeName
     */
    private function setParentNodeExists(&$parentNode, &$xmlRootNodeName)
    {
        if (!$parentNode->parentNode->parentNode) {
            $root = $parentNode->parentNode;
            $root->removeChild($parentNode);
            $parentNode = $root;
            return;
        }

        $xmlRootNodeName = 'data';
    }
}
