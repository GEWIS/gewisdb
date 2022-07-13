<?php

namespace Database\Service;

use Database\Form\Query as QueryForm;
use Database\Form\QueryExport as QueryExportForm;
use Database\Form\QuerySave as QuerySaveForm;
use Database\Mapper\SavedQuery as SavedQueryMapper;
use Database\Model\SavedQuery as SavedQueryModel;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\QueryException;
use Doctrine\Common\Persistence\Mapping\MappingException;

class Query
{
    /** @var QueryForm $queryForm */
    private $queryForm;

    /** @var QueryExportForm $queryExportForm */
    private $queryExportForm;

    /** @var QuerySaveForm $querySaveForm */
    private $querySaveForm;

    /** @var SavedQueryMapper $savedQueryMapper */
    private $savedQueryMapper;

    /** @var EntityManager */
    private $emReport;

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
        EntityManager $emReport
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
    public function getSavedQueries()
    {
        return $this->getSavedQueryMapper()->findAll();
    }

    /**
     * Save a query.
     * @param array $data
     * @return mixed result
     */
    public function save($data)
    {
        $form = $this->getQuerySaveForm();

        $form->bind(new SavedQueryModel());

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $data = $form->getData();

        $mapper = $this->getSavedQueryMapper();

        $mapper->persist($data);

        return $data;
    }

    /**
     * Execute a saved query.
     * @param string $id Query number to execute
     * @return mixed result
     */
    public function executeSaved($id)
    {
        $query = $this->getSavedQueryMapper()->find($id);

        return $this->execute(array(
            'query' => $query->getQuery()
        ));
    }

    /**
     * Execute a query.
     * @param array $data
     * @param boolean $export False by default
     * @return mixed result
     */
    public function execute($data, $export = false)
    {
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
        $em = $this->emReport;
        try {
            $query = $em->createQuery($data['query']);
            return $query->getResult(AbstractQuery::HYDRATE_SCALAR);
        } catch (QueryException $e) {
            $form->get('query')
                ->setMessages(array(
                    $e->getMessage()
                ));
            return null;
        } catch (MappingException $e) {
            $form->get('query')
                ->setMessages(array(
                    $e->getMessage()
                ));
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
    public function getEntities()
    {
        $entityManager = $this->emReport;
        $classes = array();
        $metas = $entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($metas as $meta) {
            $classes[] = preg_replace('/^Report\\\\Model\\\\/', 'db:', $meta->getName());
        }
        return $classes;
    }

    /**
     * Get the query form.
     * @return QueryForm
     */
    public function getQueryForm(): QueryForm
    {
        return $this->queryForm;
    }

    /**
     * Get the query form.
     * @return QuerySaveForm
     */
    public function getQuerySaveForm(): QuerySaveForm
    {
        return $this->querySaveForm;
    }

    /**
     * Get the query form.
     * @return QueryExportForm
     */
    public function getQueryExportForm(): QueryExportForm
    {
        return $this->queryExportForm;
    }
}
