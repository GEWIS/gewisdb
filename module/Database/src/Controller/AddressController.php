<?php

namespace Database\Controller;

use Database\Service\Member as MemberService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class AddressController extends AbstractActionController
{
    private MemberService $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'form' => $this->memberService->getAddressExportForm(),
        ]);
    }
}
