<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\DateTime;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "orders")]
    private $user;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: "orders")]
    private $product;

    #[ORM\Column(type: "string", columnDefinition: "ENUM('waiting','preparing','shipped','delivered','canceled')")]
    private $orderStatus = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $quantity = 0;

    #[ORM\Column(type: 'string', nullable: false)]
    private $address = '';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $shippingDate;

    #[ORM\Column(type: 'string', nullable: false)]
    private $orderCode = '';

    /**
     * @return mixed
     */
    public function getUser():?User
    {
        return $this->user;

    }

    /**
     * @param User|null $user
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderCode(): string
    {
        return $this->orderCode;
    }

    /**
     * @param string $orderCode
     */
    public function setOrderCode(string $orderCode): void
    {
        $this->orderCode = $orderCode;
    }

    /**
     * @return mixed
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * @param Product|null $product
     * @return $this
     */
    public function setProduct(?Product $product): self
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderStatus(): string
    {
        return $this->orderStatus;
    }

    /**
     * @param string $orderStatus
     */
    public function setOrderStatus(string $orderStatus): void
    {
        $this->orderStatus = $orderStatus;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getShippingDate()
    {
        return $this->shippingDate;
    }

    /**
     * @param $shippingDate
     */
    public function setShippingDate($shippingDate): void
    {
        $this->shippingDate = $shippingDate;
    }

    public function getId(): ?int
    {
        return $this->id;
    }


}
