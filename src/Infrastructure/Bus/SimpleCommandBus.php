<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Bus;

use MarkusLehr\ClientGallerie\Application\Bus\CommandBusInterface;
use MarkusLehr\ClientGallerie\Application\Command\CreateGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\DeleteGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\UpdateGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Command\PublishGalleryCommand;
use MarkusLehr\ClientGallerie\Application\Handler\CreateGalleryHandler;
use MarkusLehr\ClientGallerie\Application\Handler\DeleteGalleryHandler;
use MarkusLehr\ClientGallerie\Application\Handler\UpdateGalleryHandler;
use MarkusLehr\ClientGallerie\Application\Handler\PublishGalleryHandler;

/**
 * Simple Command Bus
 * 
 * Simple implementation of command bus pattern for CQRS
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Bus
 * @author Markus Lehr
 * @since 1.0.0
 */
class SimpleCommandBus implements CommandBusInterface
{
    private array $handlers = [];

    public function __construct(
        CreateGalleryHandler $createHandler,
        DeleteGalleryHandler $deleteHandler,
        UpdateGalleryHandler $updateHandler,
        PublishGalleryHandler $publishHandler
    ) {
        $this->handlers[CreateGalleryCommand::class] = $createHandler;
        $this->handlers[DeleteGalleryCommand::class] = $deleteHandler;
        $this->handlers[UpdateGalleryCommand::class] = $updateHandler;
        $this->handlers[PublishGalleryCommand::class] = $publishHandler;
    }

    /**
     * Execute a command
     * 
     * @param object $command
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function execute(object $command)
    {
        $commandClass = get_class($command);
        
        if (!isset($this->handlers[$commandClass])) {
            throw new \InvalidArgumentException("No handler found for command: {$commandClass}");
        }
        
        return $this->handlers[$commandClass]->handle($command);
    }
}
