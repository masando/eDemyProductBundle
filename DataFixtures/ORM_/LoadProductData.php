<?php
namespace eDemy\ProductBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use eDemy\ProductBundle\Entity\Product;
use eDemy\ProductBundle\Entity\ProductCategory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class LoadProductData  extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
			$p = new Product();
			$p->setName('producto'.$i);
			$p->setDescription('descripción'.$i);
			$p->setPrice(10.02+$i);
            $p->setCategory($this->getReference('product-category'.$i));
            $path = __DIR__."/../../Resources/public/images/";
            $filename = "test_image".$i.".jpg";
            $tmpname = "tmp.jpg";
            copy($path . $filename, $path . $tmpname);
            $file = new UploadedFile($path . $tmpname, 'Image1', null, null, null, true);
            $p->setFile($file);
            
			$manager->persist($p);
			$manager->flush();
			
			$translatable->setTranslatableLocale('en');
			$p->setName('product'.$i);
			$p->setDescription('description'.$i);

			//$manager->persist($p);
			//$manager->flush();

			$this->addReference('product'.$i, $p);
		}
            */
    }
    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 2; // the order in which fixtures will be loaded
    }	
}
