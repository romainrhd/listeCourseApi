<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ShoppingListRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ShoppingListRepository::class)]
class ShoppingList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['get_all_lists', 'get_one_list'])]
    private int $id;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['get_all_lists', 'get_one_list'])]
    private string $name;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'boolean', options: ["default" => false])]
    #[Groups(['get_all_lists', 'get_one_list'])]
    private bool $archived = false;

    #[ORM\OneToMany(mappedBy: 'shoppingList', targetEntity: Item::class)]
    #[Groups(['get_one_list'])]
    private Collection $items;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'shoppingLists')]
    private $users;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getArchived(): ?bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * @return Collection<int, Item>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(Item $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setShoppingList($this);
        }

        return $this;
    }

    public function removeItem(Item $item): self
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getShoppingList() === $this) {
                $item->setShoppingList(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addShoppingList($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeShoppingList($this);
        }

        return $this;
    }
}
