<?php

namespace eDemy\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use eDemy\MainBundle\Entity\BaseImagen;

/**
 * @ORM\Table("ProductImagen")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity
 */
class Imagen extends BaseImagen
{
    public function __construct($em = null)
    {
        parent::__construct($em);
    }

    /**
     * @ORM\ManyToOne(targetEntity="eDemy\ProductBundle\Entity\Product", inversedBy="imagenes")
     */
    protected $product;

    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct()
    {
        return $this->product;
    }

    ////
    public function showNameInForm()
    {
        return false;
    }

    public function showPublishedInForm()
    {
        return false;
    }

    public function showProductInForm()
    {
        return false;
    }

    //// WebPath
    protected $webpath;
    
    public function showWebpathInForm()
    {
        return true;
    }

}
