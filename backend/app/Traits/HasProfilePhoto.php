<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait HasProfilePhoto
{
    public function photoUrl(): ?string
    {
        if (! is_string($this->photo_path) || trim($this->photo_path) === '') {
            return null;
        }

        return asset('storage/'.ltrim($this->photo_path, '/'));
    }

    public function photoDataUri(): ?string
    {
        if (! is_string($this->photo_path) || trim($this->photo_path) === '') {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($this->photo_path)) {
            return null;
        }

        $bin = $disk->get($this->photo_path);
        if ($bin === '' || $bin === false) {
            return null;
        }

        $mime = $disk->mimeType($this->photo_path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($bin);
    }
}
