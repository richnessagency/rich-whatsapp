<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Document = 'document';
    case Audio = 'audio';
    case Video = 'video';

    public static function fromMime(string $mime): self
    {
        $mime = strtolower($mime);

        return match (true) {
            str_starts_with($mime, 'image/') => self::Image,
            str_starts_with($mime, 'video/') => self::Video,
            str_starts_with($mime, 'audio/') => self::Audio,
            default => self::Document,
        };
    }
}