<?php

namespace Database\Controller;

use Database\Service\Member as MemberService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AddressController extends AbstractActionController
{
    /** @var MemberService $memberService */
    private $memberService;

    /**
     * @param MemberService $memberService
     */
    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    public function indexAction()
    {
        return new ViewModel([
            'form' => $this->memberService->getAddressExportForm()
        ]);
    }
}
