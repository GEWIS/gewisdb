<?php

declare(strict_types=1);

namespace App\Controller\Database;

use App\Entity\Database\Member;
use App\Service\Database\Member as MemberService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared by the pages that are about one member.
 *
 * A deleted member keeps nothing but their name, so that the decisions naming them remain readable. Every page about
 * such a member is therefore replaced by the notice that their data is gone, rather than by a form that has nothing
 * left to show.
 */
abstract class AbstractMemberController extends AbstractController
{
    public function __construct(protected readonly MemberService $memberService)
    {
    }

    /**
     * The member the page is about, or the notice that stands in for them.
     */
    protected function resolveMember(int $lidnr): Member|Response
    {
        $member = $this->memberService->getMember($lidnr);

        if (null === $member) {
            throw $this->createNotFoundException();
        }

        return $this->guardDeleted($member);
    }

    protected function guardDeleted(Member $member): Member|Response
    {
        if ($member->getDeleted()) {
            return $this->render(
                'member/deleted.html.twig',
                ['member' => $member],
            );
        }

        return $member;
    }
}
