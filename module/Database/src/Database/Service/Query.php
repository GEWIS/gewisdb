<?php

namespace Database\Service;

use Application\Service\AbstractService;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\QueryException;

class Query extends AbstractService
{

    /**
     * Execute a query.
     * @param array $data
     * @return mixed result
     */
    public function execute($data)
    {
        $form = $this->getQueryForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $data = $form->getData();

        /**
         * Yes, I know, this is ugly. I should actually make a mapper for this
         * etc. etc. etc. But yes, I'm lazy. So I'm typing a bunch of text
         * instead, to make up it. And yes, probably it would have been better
         * to have made the mapper anyway. Still, I'm lazy.
         *
         * TODO: properly put this in a mapper.....
         */
        $em = $this->getServiceManager()->get('database_doctrine_em');
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
     * Export a query.
     * @param array $data
     * @return mixed result
     */
    public function export($data)
    {
        $form = $this->getQueryExportForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $data = $form->getData();

        /**
         * Yes, I know, this is ugly. I should actually make a mapper for this
         * etc. etc. etc. But yes, I'm lazy. So I'm typing a bunch of text
         * instead, to make up it. And yes, probably it would have been better
         * to have made the mapper anyway. Still, I'm lazy.
         *
         * TODO: properly put this in a mapper.....
         */
        $em = $this->getServiceManager()->get('database_doctrine_em');
        try {
            $query = $em->createQuery($data['query']);
            $result = $query->getResult(AbstractQuery::HYDRATE_SCALAR);
        } catch (QueryException $e) {
            $form->get('query')
                ->setMessages(array(
                    $e->getMessage()
                ));
            return null;
        }

        switch ($data['type']) {
        case 'csvex':
            $str = "";
            foreach ($result as $row) {
                $row = array_map(function($val) {
                    if ($val instanceof \DateTime) {
                        return '"' . $val->format('Y-m-d H:i:s') . '"';
                    } else {
                        return '"' . $val . '"';
                    }
                }, $row);
                $str .= implode(',', $row) . "\n";
            }
            return $str;
            break;
        }
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
     * @return \Database\Form\QueryExport
     */
    public function getQueryExportForm()
    {
        return $this->getServiceManager()->get('database_form_queryexport');
    }
}
