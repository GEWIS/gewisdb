<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class FileUrl extends AbstractHelper
{
    public function __construct(protected readonly array $config)
    {
    }

    /**
     * Get the file URL.
     */
    public function __invoke(string $path): string
    {
        return $this->getView()->basePath() . '/' . $this->config['storage']['public_dir'] . '/' . $path;
    }
}
