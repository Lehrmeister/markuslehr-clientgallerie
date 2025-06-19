<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Handler;

use MarkusLehr\ClientGallerie\Application\Command\DeleteGalleryCommand;
use MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface;

/**
 * Delete Gallery Handler
 * 
 * Handles gallery deletion following CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Handler
 * @author Markus Lehr
 * @since 1.0.0
 */
class DeleteGalleryHandler
{
    private GalleryRepositoryInterface $galleryRepository;

    public function __construct(GalleryRepositoryInterface $galleryRepository)
    {
        $this->galleryRepository = $galleryRepository;
    }

    /**
     * Handle gallery deletion
     * 
     * @param DeleteGalleryCommand $command
     * @return bool
     * @throws \Exception
     */
    public function handle(DeleteGalleryCommand $command): bool
    {
        // Find existing gallery
        $gallery = $this->galleryRepository->findGalleryById($command->id);
        
        if ($gallery === null) {
            throw new \InvalidArgumentException(
                sprintf('Gallery with ID %d not found', $command->id)
            );
        }

        // Delete the gallery
        $deleted = $this->galleryRepository->deleteById($command->id);
        
        if (!$deleted) {
            throw new \RuntimeException('Failed to delete gallery');
        }

        return true;
    }
}
