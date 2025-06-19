<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Handler;

use MarkusLehr\ClientGallerie\Application\Command\CreateGalleryCommand;
use MarkusLehr\ClientGallerie\Domain\Gallery\Entity\Gallery;
use MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface;
use MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GalleryStatus;
use MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GallerySlug;

/**
 * Create Gallery Handler
 * 
 * Handles gallery creation following CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Handler
 * @author Markus Lehr
 * @since 1.0.0
 */
class CreateGalleryHandler
{
    private GalleryRepositoryInterface $galleryRepository;

    public function __construct(GalleryRepositoryInterface $galleryRepository)
    {
        $this->galleryRepository = $galleryRepository;
    }

    /**
     * Handle gallery creation
     * 
     * @param CreateGalleryCommand $command
     * @return Gallery
     * @throws \Exception
     */
    public function handle(CreateGalleryCommand $command): Gallery
    {
        // Check if slug already exists
        $existingGallery = $this->galleryRepository->findBySlug(
            GallerySlug::fromString($command->slug)
        );
        
        if ($existingGallery !== null) {
            throw new \InvalidArgumentException(
                sprintf('Gallery with slug "%s" already exists', $command->slug)
            );
        }

        // Create new gallery
        $gallery = Gallery::create(
            name: $command->name,
            slug: $command->slug, // Use string directly
            clientId: $command->clientId,
            description: $command->description,
            settings: $command->settings
        );

        // Persist gallery
        $savedGallery = $this->galleryRepository->save($gallery);
        
        if ($savedGallery === null) {
            throw new \RuntimeException('Failed to create gallery');
        }

        return $savedGallery;
    }
}
