<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Command;

use MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GalleryStatus;

/**
 * Publish Gallery Command
 * 
 * Command to publish/unpublish a gallery following CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Command
 * @author Markus Lehr
 * @since 1.0.0
 */
class PublishGalleryCommand
{
    public int $id;
    public GalleryStatus $status;

    public function __construct(int $id, GalleryStatus $status) {
        $this->id = $id;
        $this->status = $status;
        
        if ($this->id <= 0) {
            throw new \InvalidArgumentException('Gallery ID must be positive');
        }
    }
}
