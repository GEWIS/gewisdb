<?php

declare(strict_types=1);

namespace Application\Service;

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
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(private readonly array $config)
    {
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
        $config = $this->getConfig();
        $hash = sha1_file($data);
        $directory = substr($hash, 0, 2);

        if (!file_exists($config['storage_dir'] . '/' . $directory)) {
            mkdir($config['storage_dir'] . '/' . $directory, $config['dir_mode']);
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
        $config = $this->getConfig();
        $storagePath = $this->generateStoragePath($data) . '.' . $extension;
        $destination = $config['storage_dir'] . '/' . $storagePath;

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
        $config = $this->getConfig();
        $fullPath = $config['storage_dir'] . '/' . $path;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Get the storage config, as used by this service.
     *
     * @return array containing the config for the module
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getConfig(): array
    {
        return $this->config['storage'];
    }
}
