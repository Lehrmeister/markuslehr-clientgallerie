<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Bus;

use MarkusLehr\ClientGallerie\Application\Bus\QueryBusInterface;
use MarkusLehr\ClientGallerie\Application\Query\GetGalleryQuery;
use MarkusLehr\ClientGallerie\Application\Query\ListGalleriesQuery;
use MarkusLehr\ClientGallerie\Application\Handler\GetGalleryQueryHandler;
use MarkusLehr\ClientGallerie\Application\Handler\ListGalleriesQueryHandler;

/**
 * Simple Query Bus
 * 
 * Simple implementation of query bus pattern for CQRS
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Bus
 * @author Markus Lehr
 * @since 1.0.0
 */
class SimpleQueryBus implements QueryBusInterface
{
    private array $handlers = [];

    public function __construct(
        GetGalleryQueryHandler $getHandler,
        ListGalleriesQueryHandler $listHandler
    ) {
        $this->handlers[GetGalleryQuery::class] = $getHandler;
        $this->handlers[ListGalleriesQuery::class] = $listHandler;
    }

    /**
     * Execute a query
     * 
     * @param object $query
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function execute(object $query)
    {
        $queryClass = get_class($query);
        
        if (!isset($this->handlers[$queryClass])) {
            throw new \InvalidArgumentException("No handler found for query: {$queryClass}");
        }
        
        return $this->handlers[$queryClass]->handle($query);
    }
}
