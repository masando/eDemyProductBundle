<?php

namespace eDemy\ProductBundle\Twig;

//use Symfony\Component\EventDispatcher\GenericEvent;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductExtension extends \Twig_Extension
{
    /** @var ContainerInterface $this->container */
    protected $container;
    
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('relatedProducts', array($this, 'relatedProductsFunction'), array('is_safe' => array('html'), 'pre_escape' => 'html')),
        );
    }

    public function relatedProductsFunction($entity)
    {
//        if ($this->container->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            $repository = $this->container->get('anh_taggable.manager')->getTaggingRepository();
            $related = new ArrayCollection();
            $i = 0;
            foreach ($entity->getTags() as $tag) {
                $ids = $repository->getResourcesWithTypeAndTag('product_product', $tag);
                foreach($ids as $id){
                    $product = $this->container->get('doctrine.orm.entity_manager')->getRepository(
                        'eDemyProductBundle:Product'
                    )->findOneById($id);
                    if ($product) {
                        if($i++ < 6) {
                            if (!$related->contains($product)) {
                                $related->add($product);
                            }
                        }
                    }
                }
            }

			if(count($related)) {
				$content = $this->container->get('edemy.product')->render('templates/product/related',array(
					'entities'  => $related
				));
				
				return $content;
			} 
//        }
    }

    public function getName()
    {
        return 'edemy_product_extension';
    }
}
