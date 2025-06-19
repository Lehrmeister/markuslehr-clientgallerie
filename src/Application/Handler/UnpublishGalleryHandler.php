<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Handler;

use MarkusLehr\ClientGallerie\Application\Command\UnpublishGalleryCommand;
use MarkusLehr\ClientGallerie\Domain\Gallery\ValueObject\GalleryStatus;
use MarkusLehr\ClientGallerie\Infrastructure\Database\Repository\GalleryRepository;

/**
 * Handler for UnpublishGalleryCommand
 */
class UnpublishGalleryHandler
{
    private GalleryRepository $galleryRepository;

    public function __construct(GalleryRepository $galleryRepository)
    {
        $this->galleryRepository = $galleryRepository;
    }

    public function handle(UnpublishGalleryCommand $command): void
    {
        $gallery = $this->galleryRepository->findById($command->getGalleryId());
        
        if (!$gallery) {
            throw new \InvalidArgumentException("Gallery with ID {$command->getGalleryId()} not found");
        }
        
        $gallery->changeStatus(GalleryStatus::draft());
        
        $this->galleryRepository->save($gallery);
    }
}
