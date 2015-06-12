<?php

namespace eDemy\ProductBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use eDemy\ParamBundle\Entity\Param;

class LoadInitData implements FixtureInterface
{
    private $manager;
    
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->addParam('eDemyProductBundle', 'translate', 'product_product.list', 'Listado de productos', 'Descripción de listado de productos', 'all');
        $this->addParam('eDemyProductBundle', 'translate', 'product_product.new', 'Nuevo Producto', 'Descripción de nuevo producto', 'all');
        $this->addParam('eDemyProductBundle', 'translate', 'product_product.edit', 'Modificar Producto', 'Descripción de modificar productos', 'all');
        $this->addParam('eDemyProductBundle', 'translate', 'product_product.show', 'Detalles de Producto', 'Descripción de detalles de productos', 'all');
    }
    
    public function addParam($bundle, $type, $name, $value, $description, $namespace)
    {
        $entity = $this->manager->getRepository('eDemyParamBundle:Param')->findOneBy(array(
            'bundle' => $bundle,
            'type' => $type,
            'name' => $name,
            'namespace' => $namespace,
        ));
        if($entity) {
            //die("a");
        } else {
            $param = new Param(null, $bundle, $type, $name, $value, $description, $namespace);

            $this->manager->persist($param);
            $this->manager->flush();
        }
    }
} 
