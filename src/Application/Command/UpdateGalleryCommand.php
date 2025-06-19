<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Command;

/**
 * Update Gallery Command
 * 
 * Command to update an existing gallery following CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Command
 * @author Markus Lehr
 * @since 1.0.0
 */
class UpdateGalleryCommand
{
    public int $id;
    public ?string $name;
    public ?string $slug;
    public ?string $description;
    public ?array $settings;

    public function __construct(
        int $id,
        ?string $name = null,
        ?string $slug = null,
        ?string $description = null,
        ?array $settings = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->settings = $settings;
        if ($this->id <= 0) {
            throw new \InvalidArgumentException('Gallery ID must be positive');
        }
        
        if ($this->name !== null && empty(trim($this->name))) {
            throw new \InvalidArgumentException('Gallery name cannot be empty');
        }
        
        if ($this->slug !== null && empty(trim($this->slug))) {
            throw new \InvalidArgumentException('Gallery slug cannot be empty');
        }
    }
}
