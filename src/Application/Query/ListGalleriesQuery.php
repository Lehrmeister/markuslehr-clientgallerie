<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Query;

/**
 * List Galleries Query
 * 
 * Query to retrieve a list of galleries following CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Query
 * @author Markus Lehr
 * @since 1.0.0
 */
class ListGalleriesQuery
{
    public ?int $clientId;
    public ?string $status;
    public int $limit;
    public int $offset;

    public function __construct(
        ?int $clientId = null,
        ?string $status = null,
        int $limit = 10,
        int $offset = 0
    ) {
        $this->clientId = $clientId;
        $this->status = $status;
        $this->limit = $limit;
        $this->offset = $offset;
        
        if ($this->limit <= 0) {
            throw new \InvalidArgumentException('Limit must be positive');
        }
        
        if ($this->offset < 0) {
            throw new \InvalidArgumentException('Offset cannot be negative');
        }
    }
}
