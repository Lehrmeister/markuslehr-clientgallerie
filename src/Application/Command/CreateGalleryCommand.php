<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Command;

/**
 * Create Gallery Command
 * 
 * Command to create a new gallery following CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Command
 * @author Markus Lehr
 * @since 1.0.0
 */
class CreateGalleryCommand
{
    public string $name;
    public string $slug;
    public int $clientId;
    public ?string $description;
    public array $settings;

    public function __construct(
        string $name,
        string $slug,
        int $clientId,
        ?string $description = null,
        array $settings = []
    ) {
        $this->name = $name;
        $this->slug = $slug;
        $this->clientId = $clientId;
        $this->description = $description;
        $this->settings = $settings;
        // Validation
        if (empty(trim($this->name))) {
            throw new \InvalidArgumentException('Gallery name cannot be empty');
        }
        
        if (empty(trim($this->slug))) {
            throw new \InvalidArgumentException('Gallery slug cannot be empty');
        }
        
        if ($this->clientId <= 0) {
            throw new \InvalidArgumentException('Client ID must be positive');
        }
    }
}
