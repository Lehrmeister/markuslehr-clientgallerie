<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Handler;

use MarkusLehr\ClientGallerie\Application\Command\UpdateGalleryCommand;
use MarkusLehr\ClientGallerie\Domain\Gallery\Entity\Gallery;
use MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface;
use MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GallerySlug;

/**
 * Update Gallery Handler
 * 
 * Handles gallery updates following CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Handler
 * @author Markus Lehr
 * @since 1.0.0
 */
class UpdateGalleryHandler
{
    private GalleryRepositoryInterface $galleryRepository;

    public function __construct(GalleryRepositoryInterface $galleryRepository)
    {
        $this->galleryRepository = $galleryRepository;
    }

    /**
     * Handle gallery update
     * 
     * @param UpdateGalleryCommand $command
     * @return Gallery
     * @throws \Exception
     */
    public function handle(UpdateGalleryCommand $command): Gallery
    {
        // Find existing gallery
        $gallery = $this->galleryRepository->findGalleryById($command->id);
        
        if ($gallery === null) {
            throw new \InvalidArgumentException(
                sprintf('Gallery with ID %d not found', $command->id)
            );
        }

        // Check for slug conflicts if slug is being updated
        if ($command->slug !== null) {
            $newSlug = GallerySlug::fromString($command->slug);
            $existingGallery = $this->galleryRepository->findBySlug($newSlug);
            
            if ($existingGallery !== null && $existingGallery->getId() !== $command->id) {
                throw new \InvalidArgumentException(
                    sprintf('Gallery with slug "%s" already exists', $command->slug)
                );
            }
            
            $gallery->updateSlug($newSlug);
        }

        // Update fields if provided
        if ($command->name !== null) {
            $gallery->updateName($command->name);
        }

        if ($command->description !== null) {
            $gallery->updateDescription($command->description);
        }

        if ($command->settings !== null) {
            $gallery->updateSettings($command->settings);
        }

        // Persist changes
        $updatedGallery = $this->galleryRepository->save($gallery);
        
        if ($updatedGallery === null) {
            throw new \RuntimeException('Failed to update gallery');
        }

        return $updatedGallery;
    }
}
