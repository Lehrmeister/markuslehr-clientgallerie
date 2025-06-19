<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject;

/**
 * Gallery Slug Value Object
 * 
 * Represents a URL-safe gallery identifier with validation and normalization
 * 
 * @package MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject
 * @author Markus Lehr
 * @since 1.0.0
 */
class GallerySlug
{
    private string $value;

    public function __construct(string $slug)
    {
        $normalized = $this->normalize($slug);
        $this->validate($normalized);
        $this->value = $normalized;
    }

    /**
     * Create slug from gallery name
     */
    public static function fromName(string $name): self
    {
        return new self($name);
    }

    /**
     * Create slug from string
     */
    public static function fromString(string $slug): self
    {
        return new self($slug);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(GallerySlug $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Normalize slug to URL-safe format
     */
    private function normalize(string $slug): string
    {
        // Remove extra whitespace
        $slug = trim($slug);
        
        // Convert to lowercase
        $slug = strtolower($slug);
        
        // Replace German umlauts
        $slug = str_replace(
            ['ä', 'ö', 'ü', 'ß', 'à', 'á', 'è', 'é', 'ì', 'í', 'ò', 'ó', 'ù', 'ú'],
            ['ae', 'oe', 'ue', 'ss', 'a', 'a', 'e', 'e', 'i', 'i', 'o', 'o', 'u', 'u'],
            $slug
        );
        
        // Remove special characters except hyphens and underscores
        $slug = preg_replace('/[^a-z0-9\-_\s]/', '', $slug);
        
        // Replace spaces and multiple hyphens with single hyphen
        $slug = preg_replace('/[\s\-_]+/', '-', $slug);
        
        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * Validate slug format
     */
    private function validate(string $slug): void
    {
        if (empty($slug)) {
            throw new \InvalidArgumentException('Gallery slug cannot be empty');
        }

        if (strlen($slug) < 2) {
            throw new \InvalidArgumentException('Gallery slug must be at least 2 characters long');
        }

        if (strlen($slug) > 100) {
            throw new \InvalidArgumentException('Gallery slug cannot be longer than 100 characters');
        }

        if (!preg_match('/^[a-z0-9\-_]+$/', $slug)) {
            throw new \InvalidArgumentException(
                'Gallery slug can only contain lowercase letters, numbers, hyphens and underscores'
            );
        }

        // Check for reserved WordPress slugs
        $reserved = ['admin', 'wp-admin', 'wp-content', 'wp-includes', 'feed', 'rss', 'atom'];
        if (in_array($slug, $reserved, true)) {
            throw new \InvalidArgumentException(
                sprintf('Gallery slug "%s" is reserved and cannot be used', $slug)
            );
        }
    }

    /**
     * Check if slug is valid format
     */
    public static function isValid(string $slug): bool
    {
        try {
            new self($slug);
            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Generate unique slug by appending number
     */
    public function makeUnique(callable $existsChecker): self
    {
        $baseSlug = $this->value;
        $counter = 1;
        $newSlug = $baseSlug;

        while ($existsChecker($newSlug)) {
            $newSlug = $baseSlug . '-' . $counter;
            $counter++;
            
            // Prevent infinite loops
            if ($counter > 1000) {
                throw new \RuntimeException('Cannot generate unique slug after 1000 attempts');
            }
        }

        return new self($newSlug);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
