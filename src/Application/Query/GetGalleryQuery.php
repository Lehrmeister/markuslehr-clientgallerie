<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Query;

/**
 * Get Gallery Query
 * 
 * Query to retrieve a single gallery following CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Query
 * @author Markus Lehr
 * @since 1.0.0
 */
class GetGalleryQuery
{
    public int $id;

    public function __construct(int $id) {
        $this->id = $id;
        
        if ($this->id <= 0) {
            throw new \InvalidArgumentException('Gallery ID must be positive');
        }
    }
}
