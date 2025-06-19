<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Handler;

use MarkusLehr\ClientGallerie\Application\Query\GetGalleryQuery;
use MarkusLehr\ClientGallerie\Domain\Gallery\Entity\Gallery;
use MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface;
use MarkusLehr\ClientGallerie\Domain\Gallery\Exception\GalleryNotFoundException;

/**
 * Get Gallery Query Handler
 * 
 * Handles the retrieval of a single gallery following CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Handler
 * @author Markus Lehr
 * @since 1.0.0
 */
class GetGalleryQueryHandler
{
    private GalleryRepositoryInterface $galleryRepository;

    public function __construct(GalleryRepositoryInterface $galleryRepository) {
        $this->galleryRepository = $galleryRepository;
    }

    /**
     * Handle the query to get a gallery
     * 
     * @param GetGalleryQuery $query
     * @return Gallery
     * @throws GalleryNotFoundException
     */
    public function handle(GetGalleryQuery $query): Gallery
    {
        $gallery = $this->galleryRepository->findGalleryById($query->id);
        
        if ($gallery === null) {
            throw GalleryNotFoundException::forId($query->id);
        }
        
        return $gallery;
    }
}
