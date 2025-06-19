<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Domain\Gallery\Exception;

/**
 * Gallery Not Found Exception
 * 
 * Thrown when a requested gallery cannot be found
 * 
 * @package MarkusLehr\ClientGallerie\Domain\Gallery\Exception
 * @author Markus Lehr
 * @since 1.0.0
 */
class GalleryNotFoundException extends \Exception
{
    public function __construct(int $galleryId, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Gallery with ID %d not found', $galleryId),
            404,
            $previous
        );
    }

    public static function forId(int $id): self
    {
        return new self($id);
    }

    public static function forSlug(string $slug): self
    {
        $exception = new \Exception(sprintf('Gallery with slug "%s" not found', $slug));
        $exception->code = 404;
        return $exception;
    }
}
