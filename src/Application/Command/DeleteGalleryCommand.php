<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Command;

/**
 * Delete Gallery Command
 * 
 * Command to delete an existing gallery following CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Command
 * @author Markus Lehr
 * @since 1.0.0
 */
class DeleteGalleryCommand
{
    public int $id;

    public function __construct(int $id) {
        $this->id = $id;
        
        if ($this->id <= 0) {
            throw new \InvalidArgumentException('Gallery ID must be positive');
        }
    }
}
