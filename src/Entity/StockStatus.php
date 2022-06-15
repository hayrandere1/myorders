<?php

namespace App\Entity;

use App\Repository\StockStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockStatusRepository::class)]
class StockStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: "stock_status")]
    private $product=null;

    #[ORM\Column(type: 'integer')]
    private $total = 0;

    /**
     * @return mixed
     */
    public function getProduct():?Product
    {
        return $this->product;
    }

    /**
     * @param $product
     */
    public function setProduct($product): void
    {
        $this->product = $product;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @param int $total
     */
    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
