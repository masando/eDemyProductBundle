<?php

namespace eDemy\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Doctrine\Common\Collections\ArrayCollection;
use eDemy\MainBundle\Entity\BaseEntity;

/**
 * ProductCategory
 *
 * @ORM\Table("ProductCategory")
 * @ORM\Entity(repositoryClass="eDemy\ProductBundle\Entity\CategoryRepository")
 */
class Category extends BaseEntity implements Translatable
{
    public function __construct($em = null)
    {
        parent::__construct($em);
        $this->products = new ArrayCollection();
        $this->imagenes = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @ORM\OneToOne(targetEntity="Category")
     */
    protected $category;

    public function setCategory(\eDemy\ProductBundle\Entity\Category $category)
    {
        $this->category = $category;
    
        return $this;
    }

    public function removeCategory(\eDemy\ProductBundle\Entity\Category $category)
    {
        $this->category->removeElement($category);
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function showCategoryInForm()
    {
        return true;
    }
    /**
     * @ORM\OneToMany(targetEntity="Product", mappedBy="category")
     */
    protected $products;

    public function addProduct(\eDemy\ProductBundle\Entity\Product $products)
    {
        $this->products[] = $products;
    
        return $this;
    }

    public function removeProduct(\eDemy\ProductBundle\Entity\Product $products)
    {
        $this->products->removeElement($products);
    }

    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @ORM\OneToMany(targetEntity="eDemy\ProductBundle\Entity\CategoryImagen", mappedBy="category", cascade={"persist","remove"})
     */
    protected $imagenes;


    public function getImagenes()
    {
        return $this->imagenes;
    }

    public function addImagen(CategoryImagen $imagen)
    {
        $imagen->setCategory($this);
        $this->imagenes->add($imagen);
    }

    public function removeImagen(CategoryImagen $imagen)
    {
        $this->imagenes->removeElement($imagen);
        $this->getEntityManager()->remove($imagen);
    }
    
    public function showImagenesInPanel()
    {
        return true;
    }

    public function showImagenesInForm()
    {
        return true;
    }
}
