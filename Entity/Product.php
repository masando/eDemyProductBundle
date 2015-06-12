<?php

namespace eDemy\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Doctrine\Common\Collections\ArrayCollection;
use eDemy\ProductBundle\Entity\ProductCategory;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use eDemy\MainBundle\Entity\BaseEntity;

/**
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="eDemy\ProductBundle\Entity\ProductRepository")
 */
class Product extends BaseEntity implements Translatable
{
    public function __construct($em = null)
    {
        parent::__construct($em);
        $this->imagenes = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="model", type="string", length=255, nullable=true)
     */
    protected $model;

    public function setModel($model)
    {
        $this->model = $model;
    
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function showModelInForm()
    {
        return false;
    }

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="description", type="text")
     */
    protected $description;

    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @ORM\Column(name="price", type="float")
     */
    protected $price;

    public function setPrice($price)
    {
        $this->price = $price;
    
        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function showPriceInForm()
    {
        return true;
    }

    /**
     * @ORM\Column(name="price_unit", type="string", length=255)
     */
    protected $priceUnit;

    public function setPriceUnit($priceUnit)
    {
        $this->priceUnit = $priceUnit;
    
        return $this;
    }

    public function getPriceUnit()
    {
        return $this->priceUnit;
    }

    public function showPriceUnitInForm()
    {
        return true;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="products")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    protected $category;    

    public function setCategory(\eDemy\ProductBundle\Entity\Category $category = null)
    {
        $this->category = $category;
    
        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function showCategoryInForm()
    {
        return true;
    }

    public function showCategoryInPanel()
    {
        return true;
    }

    /**
     * @ORM\OneToMany(targetEntity="eDemy\ProductBundle\Entity\Imagen", mappedBy="product", cascade={"persist","remove"})
     */
    protected $imagenes;


    public function getImagenes()
    {
        return $this->imagenes;
    }

    public function addImagen(Imagen $imagen)
    {
        $imagen->setProduct($this);
        $this->imagenes->add($imagen);
    }

    public function removeImagen(Imagen $imagen)
    {
        $this->imagenes->removeElement($imagen);
        $this->getEntityManager()->remove($imagen);
    }

    public function showImagenesInPanel()
    {
        return true;
    }
    
    ////
    public function showMeta_DescriptionInForm()
    {
        return true;
    }

    ////
    public function showMeta_KeywordsInForm()
    {
        return true;
    }
}
