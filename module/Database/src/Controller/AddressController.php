<?php

namespace Database\Controller;

use Database\Service\Member as MemberService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class AddressController extends AbstractActionController
{
    public function __construct(private readonly MemberService $memberService)
    {
    }

    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'form' => $this->memberService->getAddressExportForm(),
        ]);
    }
}
