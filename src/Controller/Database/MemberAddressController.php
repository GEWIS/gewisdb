<?php

declare(strict_types=1);

namespace App\Controller\Database;

use App\Entity\Database\Enums\AddressTypes;
use App\Form\Database\AddressEditType;
use App\Form\Database\DeleteAddressType;
use App\Form\SubmitButtons;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function Symfony\Component\Translation\t;

/**
 * The three addresses a member can have on file. Which one a page is about follows from the route, never from what
 * is submitted.
 */
#[Route(
    path: '/member/{lidnr}',
    requirements: [
        'lidnr' => '\d+',
        'type' => 'home|student|mail',
    ],
)]
final class MemberAddressController extends AbstractMemberController
{
    #[Route(
        path: '/add/address/{type}',
        name: 'member_address_add',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function add(
        Request $request,
        int $lidnr,
        AddressTypes $type,
    ): Response {
        $member = $this->resolveMember($lidnr);

        if ($member instanceof Response) {
            return $member;
        }

        $address = $this->memberService->getAddress(
            $member,
            $type,
            true,
        );
        $form = $this->createForm(
            AddressEditType::class,
            $address,
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->memberService->addAddress($form);

            $this->addFlash(
                'success',
                t(
                    '%entity% has been added to %target%',
                    [
                        '%entity%' => t('Address'),
                        '%target%' => t('member'),
                    ],
                ),
            );

            return $this->redirectToRoute(
                'member_show',
                ['lidnr' => $lidnr],
            );
        }

        return $this->render(
            'member/edit-address.html.twig',
            [
                'add' => true,
                'address' => $address,
                'form' => $form,
            ],
        );
    }

    #[Route(
        path: '/edit/address/{type}',
        name: 'member_address_edit',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        int $lidnr,
        AddressTypes $type,
    ): Response {
        $member = $this->resolveMember($lidnr);

        if ($member instanceof Response) {
            return $member;
        }

        $address = $this->memberService->getAddress(
            $member,
            $type,
        );

        if (null === $address) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(
            AddressEditType::class,
            $address,
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->memberService->editAddress($form);

            $this->addFlash(
                'success',
                t(
                    'Change(s) of %entity% have been saved!',
                    ['%entity%' => t('member address')],
                ),
            );

            return $this->redirectToRoute(
                'member_show',
                ['lidnr' => $lidnr],
            );
        }

        return $this->render(
            'member/edit-address.html.twig',
            [
                'add' => false,
                'address' => $address,
                'form' => $form,
            ],
        );
    }

    #[Route(
        path: '/remove/address/{type}',
        name: 'member_address_remove',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function remove(
        Request $request,
        int $lidnr,
        AddressTypes $type,
    ): Response {
        $member = $this->resolveMember($lidnr);

        if ($member instanceof Response) {
            return $member;
        }

        if (
            null === $this->memberService->getAddress(
                $member,
                $type,
            )
        ) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(DeleteAddressType::class);
        $form->handleRequest($request);

        // `isValid()` before the button: a clicked button says nothing about the token, which is only weighed when
        // the form as a whole is validated.
        if (
            $form->isSubmitted()
            && $form->isValid()
            && SubmitButtons::clicked(
                $form,
                'submit_yes',
            )
        ) {
            $this->memberService->removeAddress(
                $member,
                $type,
                $form,
            );

            $this->addFlash(
                'success',
                t(
                    'Succesfully deleted %entity%!',
                    ['%entity%' => t('member address')],
                ),
            );

            return $this->redirectToRoute(
                'member_show',
                ['lidnr' => $lidnr],
            );
        }

        return $this->render(
            'member/remove-address.html.twig',
            [
                'address_type' => $type,
                'form' => $form,
                'member' => $member,
            ],
        );
    }
}
