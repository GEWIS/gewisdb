<?php

namespace Database\Service;

use Database\Form\{
    Query as QueryForm,
    QueryExport as QueryExportForm,
    QuerySave as QuerySaveForm,
};
use Database\Mapper\SavedQuery as SavedQueryMapper;
use Database\Model\SavedQuery as SavedQueryModel;
use Doctrine\ORM\{
    AbstractQuery,
    EntityManager,
    Exception\ORMException,
};

class Query
{
    /** @var QueryForm $queryForm */
    private QueryForm $queryForm;

    /** @var QueryExportForm $queryExportForm */
    private QueryExportForm $queryExportForm;

    /** @var QuerySaveForm $querySaveForm */
    private QuerySaveForm $querySaveForm;

    /** @var SavedQueryMapper $savedQueryMapper */
    private SavedQueryMapper $savedQueryMapper;

    /** @var EntityManager */
    private EntityManager $emReport;

    /**
     * @param QueryForm $queryForm
     * @param QueryExportForm $queryExportForm
     * @param QuerySaveForm $querySaveForm
     * @param SavedQueryMapper $savedQueryMapper
     * @param EntityManager $emReport
     */
    public function __construct(
        QueryForm $queryForm,
        QueryExportForm $queryExportForm,
        QuerySaveForm $querySaveForm,
        SavedQueryMapper $savedQueryMapper,
        EntityManager $emReport,
    ) {
        $this->queryForm = $queryForm;
        $this->queryExportForm = $queryExportForm;
        $this->querySaveForm = $querySaveForm;
        $this->savedQueryMapper = $savedQueryMapper;
        $this->emReport = $emReport;
    }

    /**
     * Get all saved queries.
     *
     * @return array of SavedQuery's
     */
    public function getSavedQueries(): array
    {
        return $this->getSavedQueryMapper()->findAll();
    }

    /**
     * Save a query.
     *
     * @param array $data
     *
     * @return SavedQueryModel|null
     */
    public function save(array $data): ?SavedQueryModel
    {
        $form = $this->getQuerySaveForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $data = $form->getData();

        $queryModel = new SavedQueryModel();
        $queryModel->setName($data['name']);
        $queryModel->setQuery($data['query']);

        $this->getSavedQueryMapper()->persist($queryModel);

        return $queryModel;
    }

    /**
     * Execute a saved query.
     *
     * @param int $id
     *
     * @return array|null
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
     * @param array $data
     * @param bool $export
     *
     * @return array|null
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
            if (!preg_match('/^-- /i', $line)) {
                $q .= $line . "\n";
            }
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
     *
     * @return SavedQueryMapper
     */
    public function getSavedQueryMapper(): SavedQueryMapper
    {
        return $this->savedQueryMapper;
    }

    /**
     * Get all entities that are present in the database
     *
     * @return array Array of all entities
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
     *
     * @return QueryForm
     */
    public function getQueryForm(): QueryForm
    {
        return $this->queryForm;
    }

    /**
     * Get the query form.
     *
     * @return QuerySaveForm
     */
    public function getQuerySaveForm(): QuerySaveForm
    {
        return $this->querySaveForm;
    }

    /**
     * Get the query form.
     *
     * @return QueryExportForm
     */
    public function getQueryExportForm(): QueryExportForm
    {
        return $this->queryExportForm;
    }
}
