<?php

declare(strict_types=1);

namespace App\Controller\Application;

use App\Entity\Database\Enums\InstallationFunctions;
use App\Service\Application\FrontPageService;
use App\ViewModel\Application\Notification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function explode;
use function in_array;
use function ltrim;

/**
 * The pages that belong to the application itself rather than to any of its domains: the dashboard, the language
 * switch and the settings overview that links into the other domains' own settings pages.
 */
final class ApplicationController extends AbstractController
{
    /**
     * @param string[] $enabledLocales
     */
    public function __construct(
        private readonly FrontPageService $frontPageService,
        #[Autowire(param: 'kernel.enabled_locales')]
        private readonly array $enabledLocales,
        #[Autowire(param: 'kernel.default_locale')]
        private readonly string $defaultLocale,
        #[Autowire(env: 'string:default::GIT_COMMIT')]
        private readonly string $gitCommit,
    ) {
    }

    #[Route(
        path: '/',
        name: 'application_home',
        methods: ['GET'],
    )]
    public function index(): Response
    {
        $data = $this->frontPageService->getFrontpageViewData();
        // The same set the notification bell reads, so what the dashboard opens with and what the bell counts cannot
        // drift apart.
        $data['notifications'] = Notification::fromFrontPage($data);
        $data['membership_breakdown'] = $this->frontPageService->getMembershipBreakdown();
        // The build a deployment runs is not part of the dashboard's data, it comes from the image it was built as.
        $data['git_commit'] = $this->gitCommit;

        return $this->render(
            'application/index.html.twig',
            $data,
        );
    }

    /**
     * Switch the interface language.
     */
    #[Route(
        path: '/lang/{lang}',
        name: 'application_lang',
        requirements: ['lang' => '[a-zA-Z_]{2,5}'],
        methods: ['GET'],
    )]
    public function lang(
        Request $request,
        string $lang,
    ): Response {
        $request->getSession()->set(
            '_locale',
            in_array(
                $lang,
                $this->enabledLocales,
                true,
            ) ? $lang : $this->defaultLocale,
        );

        // Return to the page the switch was made on, reduced to its path so that the referer cannot send the
        // visitor to another site. A leading slash of its own would make the result protocol-relative, which is
        // such an address again, so it is dropped.
        $referer = explode(
            '/',
            (string) $request->headers->get('referer'),
            4,
        );
        if (isset($referer[3])) {
            return $this->redirect('/' . ltrim($referer[3], '/'));
        }

        // Without a referer there is no page to return to. Anyone who is not logged in reached this from the
        // enrolment form, which is the only page they can see.
        if (null === $this->getUser()) {
            return $this->redirectToRoute('join_subscribe_index');
        }

        return $this->redirectToRoute('application_home');
    }

    #[Route(
        path: '/settings',
        name: 'application_settings_index',
        methods: ['GET'],
    )]
    public function settings(): Response
    {
        return $this->render('application/settings/index.html.twig');
    }

    #[Route(
        path: '/settings/function',
        name: 'application_settings_function_index',
        methods: ['GET'],
    )]
    public function functions(): Response
    {
        return $this->render(
            'application/settings/functions.html.twig',
            [
                'current_functions' => InstallationFunctions::currentCases(),
                'legacy_functions' => InstallationFunctions::legacyCases(),
                'administrative_functions' => InstallationFunctions::administrativeCases(),
            ],
        );
    }
}
