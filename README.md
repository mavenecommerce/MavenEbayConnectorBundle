MavenEbayConnectorBundle [![Build Status](https://travis-ci.org/mavenecommerce/MavenEbayConnectorBundle.svg?branch=develop)](https://travis-ci.org/mavenecommerce/MavenEbayConnectorBundle)
=======

This bundle provide ebay exporter functional for Akeneo PIM.

Installation
=======

Add this repository to repositories section in your composer.json file:
```JSON
    "repositories" : [
        {
            "url": "https://github.com/mavenecommerce/MavenEbayConnectorBundle",
            "type": "git"
        }
    ]
```
Then register bundle in AppKernel.php:
```
$bundles = [
    new \Maven\Bundle\EbayConnectorBundle\MavenEbayConnectorBundle()
];
```
Put to #app/config/config.yml:
```
akeneo_storage_utils:
    mapping_overrides:
        -
            original: Pim\Bundle\CatalogBundle\Entity\Category
            override: Maven\Bundle\EbayConnectorBundle\Entity\Category
```
Create database dump. In symfony console run command for update db structure:

```
    app/console d:s:u --force
```
Or
```
    app/console doctrine:schema:update  --force
```

After installation you must configure exporter profile. The valid values for eBay Tranding API you can find by [Reference](http://developer.ebay.com/devzone/xml/docs/Reference/eBay/index.html "Reference").
