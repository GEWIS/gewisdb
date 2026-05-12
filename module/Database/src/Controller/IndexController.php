<?php

declare(strict_types=1);

namespace Database\Controller;

use Database\Service\FrontPage as FrontPageService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Override;

class IndexController extends AbstractActionController
{
    public function __construct(private readonly FrontPageService $frontPageService)
    {
    }

    #[Override]
    public function indexAction(): ViewModel
    {
        return new ViewModel($this->frontPageService->getFrontpageData());
    }
}
