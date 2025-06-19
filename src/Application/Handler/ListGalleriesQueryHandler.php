<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Handler;

use MarkusLehr\ClientGallerie\Application\Query\ListGalleriesQuery;
use MarkusLehr\ClientGallerie\Domain\Gallery\Repository\GalleryRepositoryInterface;

/**
 * List Galleries Query Handler
 * 
 * Handles the retrieval of galleries list following CQRS pattern
 * 
 * @package MarkusLehr\ClientGallerie\Application\Handler
 * @author Markus Lehr
 * @since 1.0.0
 */
class ListGalleriesQueryHandler
{
    private GalleryRepositoryInterface $galleryRepository;

    public function __construct(GalleryRepositoryInterface $galleryRepository) {
        $this->galleryRepository = $galleryRepository;
    }

    /**
     * Handle the query to list galleries
     * 
     * @param ListGalleriesQuery $query
     * @return array
     */
    public function handle(ListGalleriesQuery $query): array
    {
        return $this->galleryRepository->findAll();
    }
}
