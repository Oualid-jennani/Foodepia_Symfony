<?php

namespace App\Entity;

use App\Repository\VariantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=VariantRepository::class)
 *
 * @UniqueEntity(
 *     fields={"menu", "size"},
 *     errorPath="size",
 *     message="This size is already in use on this menu."
 * )
 * @UniqueEntity(
 *     fields={"subMenu", "size"},
 *     ignoreNull=false,
 *     message="This size is already in use on this sub Menu ."
 * )
 */
class Variant
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Choice(
     *     choices = {"standard", "sm", "l", "m", "xl", "u", "kg"},
     *     message = "Choose a size genre."
     * )
     */
    private $size;

    /**
     * @ORM\Column(type="float", precision=10, scale=0)
     */
    private $price;

    /**
     * @ORM\ManyToOne(targetEntity=Menu::class, inversedBy="variants",fetch="EAGER")
     */
    private $menu;

    /**
     * @ORM\ManyToOne(targetEntity=SubMenu::class, inversedBy="variants",fetch="EAGER")
     */
    private $subMenu;

    /**
     * @ORM\Column(type="integer")
     */
    private $sort;

    /**
     * @ORM\OneToMany(targetEntity=CartLine::class, mappedBy="variant")
     */
    private $cartLines;

    public function __construct()
    {
        $this->cartLines = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(string $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getMenu(): ?Menu
    {
        return $this->menu;
    }

    public function setMenu(?Menu $menu = null): self
    {
        $this->menu = $menu;

        return $this;
    }

    public function getSubMenu(): ?SubMenu
    {
        return $this->subMenu;
    }

    public function setSubMenu(?SubMenu $subMenu): self
    {
        $this->subMenu = $subMenu;

        return $this;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(int $sort): self
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * @return Collection|CartLine[]
     */
    public function getCartLines(): Collection
    {
        return $this->cartLines;
    }

    public function addCartLine(CartLine $cartLine): self
    {
        if (!$this->cartLines->contains($cartLine)) {
            $this->cartLines[] = $cartLine;
            $cartLine->setVariant($this);
        }

        return $this;
    }

    public function removeCartLine(CartLine $cartLine): self
    {
        if ($this->cartLines->removeElement($cartLine)) {
            // set the owning side to null (unless already changed)
            if ($cartLine->getVariant() === $this) {
                $cartLine->setVariant(null);
            }
        }

        return $this;
    }

}
