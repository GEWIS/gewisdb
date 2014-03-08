<?php

namespace Database\Service;

use Application\Service\AbstractService;

class Meeting extends AbstractService
{

    /**
     * Create a meeting.
     *
     * @param array $data Meeting creation data.
     *
     * @return boolean If creation was succesfull
     */
    public function createMeeting($data)
    {
        $form = $this->getCreateMeetingForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        // TODO: create meeting

        return true;
    }

    /**
     * Get the create meeting form.
     *
     * @return \Database\Form\CreateMeeting
     */
    public function getCreateMeetingForm()
    {
        return $this->getServiceManager()->get('database_form_createmeeting');
    }
}
