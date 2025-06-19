<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Bus;

/**
 * Command Bus Interface
 * 
 * Interface for command handling in CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Bus
 * @author Markus Lehr
 * @since 1.0.0
 */
interface CommandBusInterface
{
    /**
     * Execute a command
     * 
     * @param object $command
     * @return mixed
     */
    public function execute(object $command);
}
