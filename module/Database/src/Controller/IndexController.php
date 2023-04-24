<?php

declare(strict_types=1);

namespace Database\Controller;

use Database\Service\Member as MemberService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function __construct(private readonly MemberService $memberService)
    {
    }

    public function indexAction(): ViewModel
    {
        return new ViewModel($this->memberService->getFrontpageData());
    }
}
