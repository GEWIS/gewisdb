<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class FileUrl extends AbstractHelper
{
    /** @var array $config */
    protected $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get the file URL.
     *
     * @param string $path
     *
     * @return string
     */
    public function __invoke(string $path): string
    {
        return $this->getView()->basePath() . '/' . $this->config['storage']['public_dir'] . '/' . $path;
    }
}
