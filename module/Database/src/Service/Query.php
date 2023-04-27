<?php

declare(strict_types=1);

namespace Database\Service;

use Database\Form\Query as QueryForm;
use Database\Form\QueryExport as QueryExportForm;
use Database\Form\QuerySave as QuerySaveForm;
use Database\Mapper\SavedQuery as SavedQueryMapper;
use Database\Model\SavedQuery as SavedQueryModel;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;

use function explode;
use function preg_match;
use function preg_replace;

class Query
{
    public function __construct(
        private readonly QueryForm $queryForm,
        private readonly QueryExportForm $queryExportForm,
        private readonly QuerySaveForm $querySaveForm,
        private readonly SavedQueryMapper $savedQueryMapper,
        private readonly EntityManager $emReport,
    ) {
    }

    /**
     * Get all saved queries.
     *
     * @return SavedQueryModel[]
     */
    public function getSavedQueries(): array
    {
        return $this->getSavedQueryMapper()->findAll();
    }

    /**
     * Save a query.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function save(array $data): ?SavedQueryModel
    {
        $form = $this->getQuerySaveForm();

        $query = $this->getSavedQueryMapper()->findByName($data['name']);
        if (null !== $query) {
            $form->bind($query);
        } else {
            $form->bind(new SavedQueryModel());
        }

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        /** @var SavedQueryModel $queryModel */
        $queryModel = $form->getData();
        $this->getSavedQueryMapper()->persist($queryModel);

        return $queryModel;
    }

    /**
     * Execute a saved query.
     *
     * @return array<array-key, mixed>|null
     */
    public function executeSaved(int $id): ?array
    {
        $query = $this->getSavedQueryMapper()->find($id);

        if (null === $query) {
            return null;
        }

        return $this->execute([
            'query' => $query->getQuery(),
        ]);
    }

    /**
     * Execute a query.
     *
     * @return array<array-key, mixed>|null
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function execute(
        array $data,
        bool $export = false,
    ): ?array {
        if ($export) {
            $form = $this->getQueryExportForm();
        } else {
            $form = $this->getQueryForm();
        }

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        /** @var array $data */
        $data = $form->getData();

        /**
         * Yay. Making more excuses. I should create an InputFilter for this.
         * However, I'm too lazy again.
         *
         * TODO: make an InputFilter for this
         */
        $q = '';
        $arr = explode("\n", $data['query']);
        foreach ($arr as $line) {
            if (preg_match('/^-- /i', $line)) {
                continue;
            }

            $q .= $line . "\n";
        }

        $data['query'] = $q;

        /**
         * Yes, I know, this is ugly. I should actually make a mapper for this
         * etc. etc. etc. But yes, I'm lazy. So I'm typing a bunch of text
         * instead, to make up it. And yes, probably it would have been better
         * to have made the mapper anyway. Still, I'm lazy.
         *
         * TODO: properly put this in a mapper.....
         */
        try {
            $query = $this->emReport->createQuery($data['query']);

            return $query->getResult(AbstractQuery::HYDRATE_SCALAR);
        } catch (ORMException $e) {
            $form->get('query')
                ->setMessages([
                    $e->getMessage(),
                ]);

            return null;
        }
    }

    /**
     * Get the saved query mapper.
     */
    public function getSavedQueryMapper(): SavedQueryMapper
    {
        return $this->savedQueryMapper;
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
            $classes[] = preg_replace('/^Report\\\\Model\\\\/', 'db:', $meta->getName());
        }

        return $classes;
    }

    /**
     * Get the query form.
     */
    public function getQueryForm(): QueryForm
    {
        return $this->queryForm;
    }

    /**
     * Get the query form.
     */
    public function getQuerySaveForm(): QuerySaveForm
    {
        return $this->querySaveForm;
    }

    /**
     * Get the query form.
     */
    public function getQueryExportForm(): QueryExportForm
    {
        return $this->queryExportForm;
    }
}
