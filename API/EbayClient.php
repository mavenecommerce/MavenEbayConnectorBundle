<?php

namespace Maven\Bundle\EbayConnectorBundle\API;

/**
 * Class EbayClient
 *
 * @package Maven\Bundle\EbayConnectorBundle\API
 */
class EbayClient
{
    /**
     * @var string
     */
    protected $authToken;

    /**
     * @var string
     */
    protected $devId;

    /**
     * @var string
     */
    protected $appId;

    /**
     * @var string
     */
    protected $certId;

    /**
     * @var int
     */
    protected $siteId;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var bool
     */
    protected $test;

    /**
     * @var int
     */
    protected $compatibilityLevel = 971;

    /**
     * @var string
     */
    protected $sandboxUrl = 'https://api.sandbox.ebay.com/ws/api.dll';

    /**
     * @var string
     */
    protected $productionUrl = 'https://api.sandbox.ebay.com/ws/api.dll';

    /**
     * EbayClient constructor.
     *
     * @param string $authToken The authentication token for the user making the call
     * @param string $devId     The developer key which may obtained in account (http://developer.ebay.com)
     * @param string $appId     The application key which may obtained in account (http://developer.ebay.com)
     * @param string $certId    The certification key which may obtained in account (http://developer.ebay.com)
     * @param int    $siteId    The id of the eBay Site associated with this call( 0 = US, 2 = Canada, 3 = UK, ...)
     * @param string $method    The name of calling method
     * @param string $test      Use sandbox or production server
     */
    public function __construct(
        $authToken,
        $devId,
        $appId,
        $certId,
        $siteId,
        $method,
        $test
    ) {
        $this->authToken = $authToken;
        $this->devId = $devId;
        $this->appId = $appId;
        $this->certId = $certId;
        $this->siteId = $siteId;
        $this->method = $method;
        $this->test = $test;
    }

    /**
     * @param string      $request
     *
     * @param null|string $method
     *
     * @return mixed
     */
    public function sendRequest($request, $method = null)
    {
        if (!is_null($method)) {
            $this->method = $method;
        }

        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $this->getServerUrl());
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($connection, CURLOPT_HTTPHEADER, $this->getEbayHeaders());
        curl_setopt($connection, CURLOPT_POST, 1);
        curl_setopt($connection, CURLOPT_POSTFIELDS, $request);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($connection);
        curl_close($connection);

        return $response;
    }

    /**
     * Return production url if test false or sandbox url in another case.
     *
     * @return string
     */
    private function getServerUrl()
    {
        return $this->test ? $this->sandboxUrl: $this->productionUrl;
    }

    /**
     * @return array
     */
    private function getEbayHeaders()
    {
        return [
            'X-EBAY-API-COMPATIBILITY-LEVEL: ' . $this->compatibilityLevel,
            'X-EBAY-API-DEV-NAME: ' . $this->devId,
            'X-EBAY-API-APP-NAME: ' . $this->appId,
            'X-EBAY-API-CERT-NAME: ' . $this->certId,
            'X-EBAY-API-CALL-NAME:' . $this->method,
            'X-EBAY-API-SITEID:' . $this->siteId
        ];
    }
}
