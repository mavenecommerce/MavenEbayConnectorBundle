<?php

namespace Maven\Bundle\EbayConnectorBundle\Form\Type;

use Pim\Bundle\EnrichBundle\Form\Type\CategoryType as BaseCategoryType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CategoryType
 *
 * @package Maven\Bundle\EbayConnectorBundle\Form\Type
 */
class CategoryType extends BaseCategoryType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('ebayId', 'integer', ['required' => true]);
    }
}
