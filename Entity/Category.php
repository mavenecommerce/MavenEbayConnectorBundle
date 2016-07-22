<?php

namespace Maven\Bundle\EbayConnectorBundle\Entity;

use Pim\Bundle\CatalogBundle\Entity\Category as BaseCategory;

//class Category extends BaseCategory
class Category extends BaseCategory
{
    /**
     * @var int $ebayId This is id number of category in ebay store.
     */
    protected $ebayId;

    /**
     * @return int
     */
    public function getEbayId()
    {
        return $this->ebayId;
    }

    /**
     * @param int $ebayId
     *
     * @return $this
     */
    public function setEbayId($ebayId)
    {
        $this->ebayId = $ebayId;

        return $this;
    }
}
