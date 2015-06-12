<?php
namespace eDemy\ProductBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use eDemy\ProductBundle\Entity\ProductCategory;

class LoadProductCategoryData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
	
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /*
		for($i=1;$i<=2;$i++) {
			$translatable = $this->container->get('stof_doctrine_extensions.listener.translatable');
			$translatable->setTranslatableLocale('es');

			$pc = new ProductCategory();
			$pc->setName('categoría'.$i);

			$manager->persist($pc);
			$manager->flush();
			
			$translatable->setTranslatableLocale('en');
			$pc->setName('category'.$i);

			//$manager->persist($pc);
			//$manager->flush();

			$this->addReference('product-category'.$i, $pc);
		}
        */
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }
}
