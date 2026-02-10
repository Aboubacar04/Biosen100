<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Upload une image
     *
     * @param UploadedFile $image
     * @param string $directory (logos, produits, employes, users)
     * @return string Le chemin de l'image
     */
    public function upload(UploadedFile $image, string $directory): string
    {
        // Générer un nom unique
        $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

        // Stocker dans public/storage/{directory}
        $path = $image->storeAs($directory, $filename, 'public');

        return $path;
    }

    /**
     * Supprimer une image
     *
     * @param string|null $path
     * @return bool
     */
    public function delete(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Mettre à jour une image (supprime l'ancienne, upload la nouvelle)
     *
     * @param UploadedFile $newImage
     * @param string|null $oldPath
     * @param string $directory
     * @return string
     */
    public function update(UploadedFile $newImage, ?string $oldPath, string $directory): string
    {
        // Supprimer l'ancienne image
        $this->delete($oldPath);

        // Upload la nouvelle
        return $this->upload($newImage, $directory);
    }

    /**
     * Obtenir l'URL publique d'une image
     *
     * @param string|null $path
     * @return string|null
     */
    public function getUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return url('storage/' . $path);
    }
}
