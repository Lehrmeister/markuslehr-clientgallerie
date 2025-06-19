<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject;

/**
 * Gallery Status Value Object
 * 
 * Represents the lifecycle state of a gallery with type safety and validation
 * 
 * @package MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject
 * @author Markus Lehr
 * @since 1.0.0
 */
class GalleryStatus
{
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';
    public const ARCHIVED = 'archived';

    private const VALID_STATUSES = [
        self::DRAFT,
        self::PUBLISHED,
        self::ARCHIVED,
    ];

    private string $value;

    public function __construct(string $status)
    {
        $status = strtolower(trim($status));
        
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid gallery status "%s". Valid statuses are: %s',
                    $status,
                    implode(', ', self::VALID_STATUSES)
                )
            );
        }

        $this->value = $status;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(string $status): bool
    {
        return $this->value === $status;
    }

    public function isDraft(): bool
    {
        return $this->value === self::DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->value === self::PUBLISHED;
    }

    public function isArchived(): bool
    {
        return $this->value === self::ARCHIVED;
    }

    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match ($this->value) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
        };
    }

    /**
     * Get status color for UI
     */
    public function getColor(): string
    {
        return match ($this->value) {
            self::DRAFT => 'orange',
            self::PUBLISHED => 'green',
            self::ARCHIVED => 'gray',
        };
    }

    /**
     * Check if status allows editing
     */
    public function allowsEditing(): bool
    {
        return $this->value !== self::ARCHIVED;
    }

    /**
     * Get all valid statuses
     */
    public static function getValidStatuses(): array
    {
        return self::VALID_STATUSES;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
