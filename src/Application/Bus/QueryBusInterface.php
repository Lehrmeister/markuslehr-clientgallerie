<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Bus;

/**
 * Query Bus Interface
 * 
 * Interface for query handling in CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Bus
 * @author Markus Lehr
 * @since 1.0.0
 */
interface QueryBusInterface
{
    /**
     * Execute a query
     * 
     * @param object $query
     * @return mixed
     */
    public function execute(object $query);
}
