<?php

namespace Database\Service\Factory;

use Database\Form\DeleteList as DeleteListForm;
use Database\Form\MailingList as MailingListForm;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Service\MailingList as MailingListService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MailingListFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return MailingListService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): MailingListService {
        /** @var DeleteListForm $deleteListForm */
        $deleteListForm = $container->get(DeleteListForm::class);
        /** @var MailingListForm $mailingListForm */
        $mailingListForm = $container->get(MailingListForm::class);
        /** @var MailingListMapper $mailingListMapper */
        $mailingListMapper = $container->get(MailingListMapper::class);

        return new MailingListService(
            $deleteListForm,
            $mailingListForm,
            $mailingListMapper
        );
    }
}
