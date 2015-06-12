<?php

namespace eDemy\ProductBundle\Controller;

use eDemy\MainBundle\Controller\BaseController;
use eDemy\MainBundle\Event\ContentEvent;

class ProductController extends BaseController
{
    public static function getSubscribedEvents()
    {
        return self::getSubscriptions('product', ['product', 'category'], array(
            'edemy_product_category_frontpage_lastmodified' => array('onCategoryFrontpageLastModified', 0),
            'edemy_product_frontpage_lastmodified' => array('onProductFrontpageLastModified', 0),
            'edemy_product_product_details' => array('onProductDetails', 0),
            'edemy_product_product_details_lastmodified' => array('onProductDetailsLastModified', 0),
            'edemy_product_category_details' => array('onCategoryDetails', 0),
            'edemy_product_category_details_lastmodified' => array('onCategoryDetailsLastModified', 0),
        ));
    }

    public function onFrontpage(ContentEvent $event)
    {
        $this->onCategoryFrontpage($event);
    }

    public function onProductFrontpageLastModified(ContentEvent $event)
    {
        $product = $this->getRepository('edemy_product_category_index')->findLastModified($this->getNamespace());

        if($product->getUpdated()) {
            //die(var_dump($entity->getUpdated()));
            $event->setLastModified($product->getUpdated());
        }
    }

    public function onProductFrontpage(ContentEvent $event)
    {
        $this->get('edemy.meta')->setTitlePrefix("Catálogo");
        $query = $this->getRepository($event->getRoute())->findAllOrderedByName($this->getNamespace(), true);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1)/*page number*/,
            24/*limit per page*/
        );


        $this->addEventModule($event, "product_frontpage.html.twig", array(
            'pagination' => $pagination
        ));
    }

    public function onProductDetailsLastModified(ContentEvent $event)
    {
        $entity = $this->getRepository($event->getRoute())->findOneBy(array(
            'slug'        => $this->getRequestParam('slug'),
            'namespace' => $this->getNamespace(),
        ));
        $lastmodified = $entity->getUpdated();
        $lastmodified_files = $this->getLastModifiedFiles('/../../ProductBundle/Resources/views', '*.html.twig');
        if($lastmodified_files > $lastmodified) {
            $lastmodified = $lastmodified_files;
        }

        $event->setLastModified($lastmodified);
    }
    
    public function onProductDetails(ContentEvent $event) {
        $cart_url = null;
        $cart_button = null;
        if($this->getParam('add_to_cart_button') != 'add_to_cart_button') {
            $cart_button = $this->getParam('add_to_cart_button');
        }
        if($this->getParam('add_to_cart_url') != 'add_to_cart_url') {
            $cart_url = $this->getParam('add_to_cart_url');
        }
        $entity = $this->getRepository($event->getRoute())->findOneBy(array(
            'slug'        => $this->getRequestParam('slug'),
            'namespace' => $this->getNamespace(),
        ));
        $this->get('edemy.meta')->setTitlePrefix($entity->getName());
        $this->get('edemy.meta')->setDescription($entity->getMetaDescription());
        $this->get('edemy.meta')->setKeywords($entity->getMetaKeywords());

        $this->addEventModule($event, "product_details.html.twig", array(
            'entity' => $entity,
            'cart_button' => $cart_button,
            'cart_url' => $cart_url,
        ));
    }

    public function onCategoryFrontpageLastModified(ContentEvent $event)
    {
        $category = $this->getRepository('edemy_product_category_frontpage')->findLastModified($this->getNamespace());
        //die(var_dump($category->getUpdated()));        
        if($category->getUpdated()) {
            $event->setLastModified($category->getUpdated());
        }

        return true;
    }
    
    public function onCategoryFrontpage(ContentEvent $event)
    {
        $this->get('edemy.meta')->setTitlePrefix("Categorías de Productos");

        $this->addEventModule($event, "category_frontpage.html.twig", array(
            'entities' => $this->getRepository('edemy_product_category_frontpage')->findBy(array(
                'namespace' => $this->getNamespace(),
            )),
        ));
        
        return true;
    }

    public function onCategoryDetailsLastModified(ContentEvent $event)
    {
        // get the category
        $request = $this->getRequest();
        $category_slug = $request->attributes->get('slug');
        $category = $this->getRepository($event->getRoute())->findOneBySlug($category_slug);
        
        // we get last modified product
        $product = $this->get('doctrine.orm.entity_manager')->getRepository('eDemyProductBundle:Product')->findLastModified($category->getId(), $this->getNamespace());
        
        if($product) {
            if($product->getUpdated()) {
                $event->setLastModified($product->getUpdated());
            }
        }

        return true;
    }

    public function onCategoryDetails(ContentEvent $event)
    {
        $request = $this->getCurrentRequest();
        $category_slug = $request->attributes->get('slug');
        if(!$category_slug) {
            $category_slug = $event->getRouteMatch('slug');
            $event->setRoute($event->getRouteMatch('_route'));
        }
        //die(var_dump($this->getNamespace($event->getRoute())));
        $category = $this->getRepository($event->getRoute())->findOneBySlug($category_slug);
        $query = $this->get('doctrine.orm.entity_manager')->getRepository('eDemyProductBundle:Product')->findAllByCategory($category->getId(), $this->getNamespace(), true);
        //die(var_dump($query));
        $num_categories = count($this->get('doctrine.orm.entity_manager')->getRepository('eDemyProductBundle:Category')->findAll());
        
        $this->get('edemy.meta')->setTitlePrefix($category->getName());

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1)/*page number*/,
            24/*limit per page*/
        );

        $this->addEventModule($event, "product_frontpage.html.twig", array(
            'pagination' => $pagination,
            'title' => 'Estás en la categoría ' . $category->getName(),
            'num_categories' => $num_categories,
        ));

        return true;
    }
}
