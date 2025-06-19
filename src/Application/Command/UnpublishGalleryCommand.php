<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Application\Command;

/**
 * Command to unpublish a gallery (set status to draft)
 */
class UnpublishGalleryCommand
{
    private int $galleryId;

    public function __construct(int $galleryId)
    {
        $this->galleryId = $galleryId;
    }

    public function getGalleryId(): int
    {
        return $this->galleryId;
    }
}
