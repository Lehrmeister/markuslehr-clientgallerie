<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Handler;

use MarkusLehr\ClientGallerie\Application\Command\PublishGalleryCommand;
use MarkusLehr\ClientGallerie\Domain\Gallery\Entity\Gallery;
use MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface;

/**
 * Publish Gallery Handler
 * 
 * Handles gallery publishing/unpublishing following CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Handler
 * @author Markus Lehr
 * @since 1.0.0
 */
class PublishGalleryHandler
{
    private GalleryRepositoryInterface $galleryRepository;

    public function __construct(GalleryRepositoryInterface $galleryRepository)
    {
        $this->galleryRepository = $galleryRepository;
    }

    /**
     * Handle gallery status change
     * 
     * @param PublishGalleryCommand $command
     * @return Gallery
     * @throws \Exception
     */
    public function handle(PublishGalleryCommand $command): Gallery
    {
        // Find existing gallery
        $gallery = $this->galleryRepository->findGalleryById($command->id);
        
        if ($gallery === null) {
            throw new \InvalidArgumentException(
                sprintf('Gallery with ID %d not found', $command->id)
            );
        }

        // Update status
        $gallery->updateStatus($command->status);

        // Persist changes
        $updatedGallery = $this->galleryRepository->save($gallery);
        
        if ($updatedGallery === null) {
            throw new \RuntimeException('Failed to update gallery status');
        }

        return $updatedGallery;
    }
}
