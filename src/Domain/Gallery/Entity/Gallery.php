<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Domain\Gallery\Entity;

use MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GalleryStatus;
use MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GallerySlug;

/**
 * Gallery Entity - Core business object representing a client gallery
 * 
 * Domain Entity following DDD principles:
 * - Contains business logic and invariants
 * - Immutable after creation (use methods for changes)
 * - Rich domain model with behavior, not anemic data structure
 * 
 * @package MarkusLehr\ClientGallerie\Domain\Gallery\Entity
 * @author Markus Lehr
 * @since 1.0.0
 */
class Gallery
{
    private int $id;
    private string $name;
    private GallerySlug $slug;
    private ?string $description;
    private GalleryStatus $status;
    private int $clientId;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
    private array $settings;
    private int $imageCount = 0;

    /**
     * Create new Gallery instance
     * 
     * @param int $id Gallery ID (0 for new galleries)
     * @param string $name Gallery name
     * @param string $slug Gallery slug
     * @param string|null $description Gallery description
     * @param string $status Gallery status (draft|published|archived)
     * @param int $clientId Associated client ID
     * @param \DateTimeImmutable|null $createdAt Creation timestamp
     * @param \DateTimeImmutable|null $updatedAt Last update timestamp
     * @param array $settings Gallery settings
     * @param int $imageCount Number of images in gallery
     * 
     * @throws \InvalidArgumentException If invalid data provided
     */
    public function __construct(
        ?int $id,
        string $name,
        string $slug,
        ?string $description,
        string $status,
        int $clientId = 0,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null,
        array $settings = [],
        int $imageCount = 0
    ) {
        $this->setId($id);
        $this->setName($name);
        $this->slug = new GallerySlug($slug);
        $this->description = $description;
        $this->status = new GalleryStatus($status);
        $this->setClientId($clientId);
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
        $this->settings = $settings;
        $this->imageCount = max(0, $imageCount);
    }

    /**
     * Create a new gallery (factory method)
     */
    public static function create(
        string $name,
        string $slug,
        int $clientId,
        ?string $description = null,
        array $settings = []
    ): self {
        return new self(
            id: 0, // Will be set by repository
            name: $name,
            slug: $slug,
            description: $description,
            status: GalleryStatus::DRAFT,
            clientId: $clientId,
            settings: $settings
        );
    }

    // === Business Logic Methods ===

    /**
     * Change gallery name and update slug if needed
     */
    public function rename(string $newName, ?string $newSlug = null): self
    {
        $this->setName($newName);
        
        if ($newSlug !== null) {
            $this->slug = new GallerySlug($newSlug);
        }
        
        $this->touch();
        return $this;
    }

    /**
     * Update gallery description
     */
    public function updateDescription(?string $description): self
    {
        $this->description = $description;
        $this->touch();
        return $this;
    }

    /**
     * Publish the gallery (change from draft to published)
     */
    public function publish(): self
    {
        if (!$this->canBePublished()) {
            throw new \DomainException('Gallery cannot be published: ' . $this->getPublishRequirements());
        }

        $this->status = new GalleryStatus(GalleryStatus::PUBLISHED);
        $this->touch();
        return $this;
    }

    /**
     * Set gallery back to draft
     */
    public function setToDraft(): self
    {
        $this->status = new GalleryStatus(GalleryStatus::DRAFT);
        $this->touch();
        return $this;
    }

    /**
     * Archive the gallery
     */
    public function archive(): self
    {
        $this->status = new GalleryStatus(GalleryStatus::ARCHIVED);
        $this->touch();
        return $this;
    }
    
    /**
     * Change gallery status
     */
    public function changeStatus(GalleryStatus $status): self
    {
        $this->status = $status;
        $this->touch();
        return $this;
    }

    /**
     * Update gallery settings
     */
    public function updateSettings(array $settings): self
    {
        $this->settings = array_merge($this->settings, $settings);
        $this->touch();
        return $this;
    }

    /**
     * Increment image count when image is added
     */
    public function addImage(): self
    {
        $this->imageCount++;
        $this->touch();
        return $this;
    }

    /**
     * Decrement image count when image is removed
     */
    public function removeImage(): self
    {
        $this->imageCount = max(0, $this->imageCount - 1);
        $this->touch();
        return $this;
    }

    /**
     * Update gallery name
     */
    public function updateName(string $name): self
    {
        $this->setName($name);
        $this->touch();
        return $this;
    }

    /**
     * Update gallery slug
     */
    public function updateSlug(GallerySlug $slug): self
    {
        $this->slug = $slug;
        $this->touch();
        return $this;
    }

    /**
     * Update gallery status
     */
    public function updateStatus(GalleryStatus $status): self
    {
        $this->status = $status;
        $this->touch();
        return $this;
    }

    // === Business Rules ===

    /**
     * Check if gallery can be published
     */
    public function canBePublished(): bool
    {
        return !empty($this->name) && $this->clientId > 0;
    }

    /**
     * Get requirements for publishing
     */
    public function getPublishRequirements(): string
    {
        $issues = [];
        
        if (empty($this->name)) {
            $issues[] = 'Gallery must have a name';
        }
        
        if ($this->clientId <= 0) {
            $issues[] = 'Gallery must be assigned to a client';
        }
        
        return empty($issues) ? 'Gallery is ready to publish' : implode(', ', $issues);
    }

    /**
     * Check if gallery is empty
     */
    public function isEmpty(): bool
    {
        return $this->imageCount === 0;
    }

    /**
     * Check if gallery is published
     */
    public function isPublished(): bool
    {
        return $this->status->equals(GalleryStatus::PUBLISHED);
    }

    /**
     * Check if gallery is draft
     */
    public function isDraft(): bool
    {
        return $this->status->equals(GalleryStatus::DRAFT);
    }

    /**
     * Check if gallery is archived
     */
    public function isArchived(): bool
    {
        return $this->status->equals(GalleryStatus::ARCHIVED);
    }

    // === Getters ===

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): GallerySlug
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): GalleryStatus
    {
        return $this->status;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getImageCount(): int
    {
        return $this->imageCount;
    }

    // === Private Helper Methods ===

    private function setId(int $id): void
    {
        if ($id < 0) {
            throw new \InvalidArgumentException('Gallery ID cannot be negative');
        }
        $this->id = $id;
    }

    private function setName(string $name): void
    {
        $trimmed = trim($name);
        if (empty($trimmed)) {
            throw new \InvalidArgumentException('Gallery name cannot be empty');
        }
        if (strlen($trimmed) > 255) {
            throw new \InvalidArgumentException('Gallery name cannot be longer than 255 characters');
        }
        $this->name = $trimmed;
    }

    private function setClientId(int $clientId): void
    {
        if ($clientId <= 0) {
            throw new \InvalidArgumentException('Client ID must be positive');
        }
        $this->clientId = $clientId;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug->getValue(),
            'description' => $this->description,
            'status' => $this->status->getValue(),
            'client_id' => $this->clientId,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'settings' => $this->settings,
            'image_count' => $this->imageCount,
        ];
    }
}
