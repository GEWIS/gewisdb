<?php

namespace Database\Service;

use Application\Service\AbstractService;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\QueryException;

use Database\Model\SavedQuery;

class Query extends AbstractService
{

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
        $form = $this->getSavedQueryForm();

        $form->bind(new SavedQuery());

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $data = $form->getData();

        $mapper = $this->getSavedQueryMapper();

        $mapper->persist($data);
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
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        try {
            $query = $em->createQuery($data['query']);
            return $query->getResult(AbstractQuery::HYDRATE_SCALAR);
        } catch (QueryException $e) {
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
     * @return \Database\Mapper\SavedQuery
     */
    public function getSavedQueryMapper()
    {
        return $this->getServiceManager()->get('database_mapper_savedquery');
    }

    /**
     * Get the query form.
     * @return \Database\Form\Query
     */
    public function getQueryForm()
    {
        return $this->getServiceManager()->get('database_form_query');
    }

    /**
     * Get the query form.
     * @return \Database\Form\SavedQuery
     */
    public function getSavedQueryForm()
    {
        return $this->getServiceManager()->get('database_form_querysave');
    }

    /**
     * Get the query form.
     * @return \Database\Form\QueryExport
     */
    public function getQueryExportForm()
    {
        return $this->getServiceManager()->get('database_form_queryexport');
    }
}
