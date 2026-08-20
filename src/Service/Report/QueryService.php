<?php

declare(strict_types=1);

namespace App\Service\Report;

use App\Entity\Database\SavedQuery;
use App\Repository\Database\SavedQueryRepository;
use DateTimeInterface;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function array_map;
use function explode;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_replace;

class QueryService
{
    /**
     * Prefixes that a stored query may use to address a ReportDB entity.
     *
     * `db:` was an ORM entity namespace alias registered on the report entity manager, `Report\Model\` the namespace
     * it pointed at. Saved queries are stored verbatim and are years old, so both keep working: they are expanded to
     * the entities' current namespace before the query is handed to the ORM.
     */
    private const array ENTITY_PREFIXES = [
        'db:',
        'Report\\Model\\',
    ];

    private const string ENTITY_NAMESPACE = 'App\\Entity\\Report\\';

    public function __construct(
        private readonly SavedQueryRepository $savedQueryRepository,
        #[Autowire(service: 'doctrine.orm.report_entity_manager')]
        private readonly EntityManagerInterface $emReport,
    ) {
    }

    /**
     * Get all saved queries.
     *
     * @return SavedQuery[]
     */
    public function getSavedQueries(): array
    {
        return $this->savedQueryRepository->findAll();
    }

    /**
     * Save a query.
     */
    public function save(
        string $name,
        string $category,
        string $query,
    ): SavedQuery {
        // This is an intentional choice to find the query by name
        // (we require unique names even across categories)
        $savedQuery = $this->savedQueryRepository->findByName($name) ?? new SavedQuery();

        $savedQuery->setName($name);
        $savedQuery->setCategory($category);
        $savedQuery->setQuery($query);

        $this->savedQueryRepository->persist($savedQuery);

        return $savedQuery;
    }

    /**
     * Get a saved query
     */
    public function getSavedQuery(int $id): ?SavedQuery
    {
        return $this->savedQueryRepository->find($id);
    }

    /**
     * Execute a query.
     *
     * @return array<array-key, mixed>
     *
     * @throws ORMException if the query cannot be executed; the caller reports the message on the query field.
     */
    public function execute(string $query): array
    {
        /**
         * Yay. Making more excuses. I should create an InputFilter for this.
         * However, I'm too lazy again.
         *
         * TODO: make an InputFilter for this
         */
        $q = '';
        $arr = explode(
            "\n",
            $query,
        );
        foreach ($arr as $line) {
            if (
                preg_match(
                    '/^-- /i',
                    $line,
                )
            ) {
                continue;
            }

            $q .= $line . "\n";
        }

        $dql = str_replace(
            self::ENTITY_PREFIXES,
            self::ENTITY_NAMESPACE,
            $q,
        );

        /** @var array<array-key, array<string, mixed>> $rows */
        $rows = $this->emReport->createQuery($dql)->getResult(AbstractQuery::HYDRATE_SCALAR);

        // Scalar hydration still returns the column's PHP type, so a date column arrives as an object. Both the
        // result table and the CSV export print values as they are, which is why they are flattened here rather
        // than in each of them.
        return array_map(
            static fn (array $row): array => array_map(
                static fn (mixed $value): mixed => $value instanceof DateTimeInterface
                    ? $value->format('Y-m-d')
                    : $value,
                $row,
            ),
            $rows,
        );
    }

    /**
     * Get all entities that are present in the database
     *
     * @return string[]
     */
    public function getEntities(): array
    {
        $classes = [];
        $metas = $this->emReport->getMetadataFactory()->getAllMetadata();

        foreach ($metas as $meta) {
            $class = preg_replace(
                '/^App\\\\Entity\\\\Report\\\\/',
                'db:',
                $meta->getName(),
            );

            if (null === $class) {
                // preg_replace can only return null on error, so this should never happen.
                throw new RuntimeException(
                    sprintf(
                        'An error occurred while processing entity class "%s"',
                        $meta->getName(),
                    ),
                );
            }

            $classes[] = $class;
        }

        return $classes;
    }
}
