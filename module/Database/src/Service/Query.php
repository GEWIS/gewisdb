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
     */
    public function getSavedQueries(): array
    {
        return $this->getSavedQueryMapper()->findAll();
    }

    /**
     * Save a query.
     */
    public function save(array $data): ?SavedQueryModel
    {
        $form = $this->getQuerySaveForm();

        $form->bind(new SavedQueryModel());
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
     */
    public function getSavedQueryMapper(): SavedQueryMapper
    {
        return $this->savedQueryMapper;
    }

    /**
     * Get all entities that are present in the database
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
