<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PurchaseLineRepository")
 */
class PurchaseLine
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;





    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Size", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $size;


    /**
     * @ORM\Column(type="integer")
     */
    private $quantity;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Purchase", inversedBy="purchaseLines")
     * @ORM\JoinColumn(nullable=true)
     */
    private $purchase;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Tint", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $tint;

    

     /**
     * Undocumented variable
     *
     * @var int
     * 
     */
    private $productPrice;

    /**
     * Undocumented variable
     *
     * @var int
     * 
     */
    private $price;

    /**
     * Undocumented variable
     *
     * @var Image
     */
    private $image;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Stock")
     */
    private $stock;


    public function __toString()
    {
        return ' Product :' . $this->product .
                 ' | Size : ' . $this->size . 
                 ' | Color : ' . $this->tint .
                 'Product Price : ' . $this->getProductPrice() . 
                 ' | Quantity' . $this->quantity.
                 ' | Total Item : ' . $this->getPrice()
                    ;
    }

    public function getId(): ?int
    {
        return $this->id;
    }



    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getSize(): ?Size
    {
        return $this->size;
    }

    public function setSize(?Size $size): self
    {
    if(in_array($size, $this->product->getSizes()))
    {
        $this->size = $size;
    }
        

        return $this;
    }



    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        if($this->quantity > $this->product->getStocK($this->size, $this->tint)){
            $this->quantity = $this->product->getStocK($this->size, $this->tint);
        }

        return $this;
    }

    public function getPurchase(): ?Purchase
    {
        return $this->purchase;
    }

    public function setPurchase(?Purchase $purchase): self
    {
        $this->purchase = $purchase;

        return $this;
    }

    public function getTint(): ?Tint
    {
        
        return $this->tint;
    }

    /**
     * @param Tint|null $tint
     * @return $this
     */
    public function setTint(?Tint $tint): self
    {
        if(in_array($tint, $this->product->getTints()))
    {
        $this->tint = $tint;
    }
        $this->tint = $tint;


        return $this;

    }



    /**
     * Get undocumented variable
     *
     * @return  int
     */ 
    public function getPrice()
    {
        $product = $this->getProduct();
            if (!empty($product->getDiscountPrice())) {
                $this->price = $product->getDiscountPrice() * $this->getQuantity();
            } else {
                $this->price = $product->getPrice() * $this->getQuantity();
            }
        return $this->price;
    }

    /**
     * Get undocumented variable
     *
     * @return  int
     */ 
    public function getProductPrice()
    {
        $product = $this->getProduct();
            if (!empty($product->getDiscountPrice())) {
                $this->productPrice = $product->getDiscountPrice();
            } else {
                $this->productPrice = $product->getPrice();
            }
        return $this->productPrice;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

   

    /**
     * Get undocumented variable
     *
     * @return  Image|null
     */ 
    public function getImage(): ?Image
    {
        return $this->image;
    }

    /**
     * Set undocumented variable
     *
     * @param  Image  $image  Undocumented variable
     *
     * @return  null|Image
     */ 
    public function setImage(?Image $image)
    {
        $this->image = $image;

        return $this;
    }
}
