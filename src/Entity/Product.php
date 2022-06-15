<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;use Symfony\Component\Validator\Constraints as Assert;
//*$2y$13$FCQCSeAu3za2uPZQqVJK4OqvY/UIiV5Lcv5MrK1DPy83VQt7SbAwe
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\OneToMany(mappedBy: "product", targetEntity: StockMovement::class)]
    private $stockMovement;

    #[ORM\OneToMany(mappedBy: "product", targetEntity: StockStatus::class)]
    private $stockStatus;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: "products")]
    private $category=null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private $productCode=null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private $name=null;

    #[ORM\Column(type: 'string', length: 5000, nullable: true)]
    private $content = null;

    #[ORM\Column(type: 'float', nullable: false)]
    private $price = 0;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $profit = 0;

    /**
     * @return mixed
     */
    public function getStockStatus()
    {
        return $this->stockStatus;
    }

    /**
     * @param mixed $stockStatus
     */
    public function setStockStatus($stockStatus): void
    {
        $this->stockStatus = $stockStatus;
    }

    /**
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * @param Category|null $category
     * @return $this
     */
    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return string
     */
    public function getProductCode(): string
    {
        return $this->productCode;
    }

    /**
     * @param string $productCode
     */
    public function setProductCode(string $productCode): void
    {
        $this->productCode = $productCode;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return int
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * @param int $price
     */
    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    /**
     * @return int
     */
    public function getProfit(): int
    {
        return $this->profit;
    }

    /**
     * @param int $profit
     */
    public function setProfit(int $profit): void
    {
        $this->profit = $profit;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

}
