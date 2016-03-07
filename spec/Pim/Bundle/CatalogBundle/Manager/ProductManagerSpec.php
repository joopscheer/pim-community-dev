<?php

namespace spec\Pim\Bundle\CatalogBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use PhpSpec\ObjectBehavior;
use Pim\Component\Catalog\Repository\AttributeRepositoryInterface;
use Pim\Component\Catalog\Repository\ProductRepositoryInterface;

class ProductManagerSpec extends ObjectBehavior
{
    function let(
        ObjectManager $objectManager,
        ProductRepositoryInterface $productRepository,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->beConstructedWith(
            $objectManager,
            $productRepository,
            $attributeRepository
        );
    }

    function it_has_a_product_repository(ProductRepositoryInterface $productRepository)
    {
        $this->getProductRepository()->shouldReturn($productRepository);
    }
}
