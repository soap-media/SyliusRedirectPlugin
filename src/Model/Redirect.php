<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Model;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Channel\Model\ChannelInterface;

#[ORM\MappedSuperclass]
#[ORM\Table(name: 'setono_sylius_redirect__redirect')]
#[ORM\Index(columns: ['last_accessed'])]
#[ORM\Index(columns: ['enabled'])]
#[ORM\Index(columns: ['only_404'])]
#[ORM\Index(name: 'findOneEnabledBySource_idx', columns: ['source', 'enabled'])]
#[ORM\Index(name: 'findOne404EnabledBySource_idx', columns: ['source', 'enabled', 'only_404'])]
class Redirect implements RedirectInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string')]
    protected ?string $source = null;

    #[ORM\Column(type: 'string')]
    protected ?string $destination = null;

    #[ORM\Column(type: 'boolean')]
    protected bool $permanent = true;

    #[ORM\Column(type: 'integer')]
    protected int $count = 0;

    #[ORM\Column(name: 'last_accessed', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $lastAccessed = null;

    #[ORM\Column(name: 'enabled', type: 'boolean')]
    protected bool $enabled = true;

    #[ORM\Column(name: 'only_404', type: 'boolean')]
    protected bool $only404 = true;

    #[ORM\Column(name: 'keep_query_string', type: 'boolean', options: ['default' => 0])]
    protected bool $keepQueryString = false;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    protected ?DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $updatedAt = null;

    /** @var Collection<array-key, ChannelInterface> */
    #[ORM\ManyToMany(targetEntity: ChannelInterface::class)]
    #[ORM\JoinTable(name: 'setono_sylius_redirect__redirect_channels')]
    #[ORM\JoinColumn(name: 'redirect_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'channel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Collection $channels;

    public function __construct()
    {
        $this->channels = new ArrayCollection();
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): void
    {
        $this->destination = $destination;
    }

    public function isPermanent(): bool
    {
        return $this->permanent;
    }

    public function setPermanent(bool $permanent): void
    {
        $this->permanent = $permanent;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    public function getLastAccessed(): ?DateTimeInterface
    {
        return $this->lastAccessed;
    }

    public function setLastAccessed(DateTimeInterface $lastAccessed): void
    {
        $this->lastAccessed = $lastAccessed;
    }

    public function onAccess(): void
    {
        ++$this->count;
        $this->setLastAccessed(new DateTime());
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): void
    {
        $this->enabled = (bool) $enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isOnly404(): bool
    {
        return $this->only404;
    }

    public function setOnly404(bool $only404): void
    {
        $this->only404 = $only404;
    }

    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function addChannel(ChannelInterface $channel): void
    {
        if (!$this->hasChannel($channel)) {
            $this->channels->add($channel);
        }
    }

    public function removeChannel(ChannelInterface $channel): void
    {
        if ($this->hasChannel($channel)) {
            $this->channels->removeElement($channel);
        }
    }

    public function hasChannel(ChannelInterface $channel): bool
    {
        return $this->channels->contains($channel);
    }

    public function keepQueryString(): bool
    {
        return $this->keepQueryString;
    }

    public function setKeepQueryString(bool $keepQueryString): void
    {
        $this->keepQueryString = $keepQueryString;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
