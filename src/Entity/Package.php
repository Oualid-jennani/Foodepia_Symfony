<?php

namespace App\Entity;

use App\Repository\PackageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=PackageRepository::class)
 */
class Package
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=0, nullable=true)
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank(message="This value should not be blank")
     * @Assert\Positive(message="Quantity has an invalid value")
     * @Assert\Regex(pattern="/^(\d+)$/", message="The quantity should be a valid numeric")
     */
    private $memberShipLimit;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $packUsage;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\NotBlank(message="This value should not be blank")
     * @Assert\Positive(message="Quantity has an invalid value")
     * @Assert\Regex(pattern="/^(\d+)$/", message="The quantity should be a valid numeric")
     */
    private $limitMerchantBySell;

    /**
     * @ORM\OneToMany(targetEntity=User::class, mappedBy="package")
     */
    private $user;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    public function __construct()
    {
        $this->user = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMemberShipLimit(): ?int
    {
        return $this->memberShipLimit;
    }

    public function setMemberShipLimit(?int $memberShipLimit): self
    {
        $this->memberShipLimit = $memberShipLimit;

        return $this;
    }

    public function getPackUsage(): ?string
    {
        return $this->packUsage;
    }

    public function setPackUsage(?string $packUsage): self
    {
        $this->packUsage = $packUsage;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getLimitMerchantBySell(): ?int
    {
        return $this->limitMerchantBySell;
    }

    public function setLimitMerchantBySell(?int $limitMerchantBySell): self
    {
        $this->limitMerchantBySell = $limitMerchantBySell;

        return $this;
    }

    /**
     * @return Collection|user[]
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    public function addUser(user $user): self
    {
        if (!$this->user->contains($user)) {
            $this->user[] = $user;
            $user->setPackage($this);
        }

        return $this;
    }

    public function removeUser(user $user): self
    {
        if ($this->user->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getPackage() === $this) {
                $user->setPackage(null);
            }
        }

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

}
