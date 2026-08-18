<?php

declare(strict_types=1);

namespace App\Service\Application;

use Exception;

use function base64_decode;
use function file_exists;
use function file_put_contents;
use function mkdir;
use function sha1_file;
use function str_replace;
use function substr;
use function unlink;

class FileStorage
{
    public function __construct(
        private readonly string $storageDir,
        private readonly int $dirMode,
    ) {
    }

    /**
     * Generates CFS paths.
     *
     * @param string $data The data of the image to generate the path for
     *
     * @return string The path at which the image should be saved
     */
    public function generateStoragePath(string $data): string
    {
        $hash = sha1_file($data);
        $directory = substr($hash, 0, 2);

        if (!file_exists($this->storageDir . '/' . $directory)) {
            mkdir($this->storageDir . '/' . $directory, $this->dirMode);
        }

        return $directory . '/' . substr($hash, 2);
    }

    /**
     * Stores uploaded data URL in the content based file system.
     *
     * @param string $data      The data of the image to be stored
     * @param string $extension The extension of the image to be stored
     *
     * @return string The CFS path at which the file was stored
     *
     * @throws Exception
     */
    public function storeUploadedData(
        string $data,
        string $extension,
    ): string {
        $storagePath = $this->generateStoragePath($data) . '.' . $extension;
        $destination = $this->storageDir . '/' . $storagePath;

        if (file_exists($destination)) {
            throw new Exception('There already exists a file at this location.');
        }

        $data = str_replace('data:image/' . $extension . ';base64,', '', $data);
        $data = str_replace(' ', '+', $data);
        $data = base64_decode($data);
        file_put_contents($destination, $data);

        return $storagePath;
    }

    /**
     * Removes a file from the content based file system.
     *
     * @param string $path The CFS path of the file to remove
     *
     * @return bool indicating if removing the file was successful.
     */
    public function removeFile(string $path): bool
    {
        $fullPath = $this->storageDir . '/' . $path;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }
}
