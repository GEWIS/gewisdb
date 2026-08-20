<?php

declare(strict_types=1);

namespace App\Controller\Report;

use App\Entity\Database\SavedQuery;
use App\Form\Report\QueryExportType;
use App\Form\Report\QuerySaveType;
use App\Form\SubmitButtons;
use App\Service\Report\QueryService;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function str_replace;

#[Route(path: '/query')]
final class QueryController extends AbstractController
{
    public function __construct(private readonly QueryService $queryService)
    {
    }

    #[Route(
        path: '',
        name: 'query_index',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function index(Request $request): Response
    {
        return $this->page(
            $request,
            null,
        );
    }

    /**
     * Show a stored query, and its result.
     */
    #[Route(
        path: '/show/{query}',
        name: 'query_show',
        requirements: ['query' => '[0-9]+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function show(
        Request $request,
        int $query,
    ): Response {
        $savedQuery = $this->queryService->getSavedQuery($query);

        if (null === $savedQuery) {
            throw $this->createNotFoundException();
        }

        return $this->page(
            $request,
            $savedQuery,
        );
    }

    /**
     * The query page, which is the same whether it was reached with a stored query or without one.
     *
     * Both the editor and the export sit on this page and post back to it; they are told apart by the form their
     * fields arrive under.
     */
    private function page(
        Request $request,
        ?SavedQuery $savedQuery,
    ): Response {
        $exportForm = $this->createForm(QueryExportType::class);
        $exportForm->handleRequest($request);

        if (
            $exportForm->isSubmitted()
            && $exportForm->isValid()
        ) {
            return $this->export($exportForm->getData());
        }

        $form = $this->createForm(
            QuerySaveType::class,
            null === $savedQuery ? null : [
                'query' => $savedQuery->getQuery(),
                'category' => $savedQuery->getCategory(),
                'name' => $savedQuery->getName(),
            ],
        );
        $form->handleRequest($request);

        // A stored query is run as it was opened, until the editor is submitted, from which point on what was
        // typed is what runs.
        $query = $savedQuery?->getQuery();

        if ($form->isSubmitted()) {
            $query = null;

            if ($form->isValid()) {
                /** @var array{query: string, category: string, name: string} $data */
                $data = $form->getData();

                if (
                    SubmitButtons::clicked(
                        $form,
                        'submit_save',
                    )
                ) {
                    $stored = $this->queryService->save(
                        $data['name'],
                        $data['category'],
                        $data['query'],
                    );

                    return $this->redirectToRoute(
                        'query_show',
                        ['query' => $stored->getId()],
                    );
                }

                $query = $data['query'];
            }
        }

        $result = null;

        if (null !== $query) {
            try {
                $result = $this->queryService->execute($query);
            } catch (ORMException $e) {
                // Whatever the parser objected to is the only thing that tells the author what is wrong with the
                // query, so it is reported on the field it was typed in.
                $form->get('query')->addError(new FormError($e->getMessage()));
                $query = null;
            }
        }

        return $this->render(
            'query/index.html.twig',
            [
                'form' => $form,
                'export_form' => $this->createForm(
                    QueryExportType::class,
                    [
                        'query' => $query,
                        'name' => null === $savedQuery
                            ? null
                            : $savedQuery->getCategory() . ' - ' . $savedQuery->getName(),
                    ],
                ),
                'entities' => $this->queryService->getEntities(),
                'saved_queries' => $this->queryService->getSavedQueries(),
                'current_query_id' => $savedQuery?->getId(),
                'result' => $result,
            ],
        );
    }

    /**
     * The result of a query as a file to download.
     *
     * @param array{query: string, name: ?string, type: string} $data
     */
    private function export(array $data): Response
    {
        try {
            $result = $this->queryService->execute($data['query']);
        } catch (ORMException $e) {
            $this->addFlash(
                'danger',
                $e->getMessage(),
            );

            return $this->redirectToRoute('query_index');
        }

        // A stored query's name is free text and ends up in a filename, where a path separator is not allowed.
        $name = str_replace(
            [
                '/',
                '\\',
            ],
            '-',
            $data['name'] ?: 'query',
        );

        $response = $this->render(
            'query/export.csv.twig',
            ['result' => $result],
        );
        $response->headers->set(
            'Content-Type',
            'text/csv; charset=UTF-8',
        );
        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $name . '.csv',
                'query.csv',
            ),
        );

        return $response;
    }
}
