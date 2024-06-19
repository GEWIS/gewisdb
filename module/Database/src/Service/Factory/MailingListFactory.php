<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Database\Form\DeleteList as DeleteListForm;
use Database\Form\MailingList as MailingListForm;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Service\MailingList as MailingListService;
use Database\Service\Mailman as MailmanService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MailingListFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MailingListService {
        /** @var DeleteListForm $deleteListForm */
        $deleteListForm = $container->get(DeleteListForm::class);
        /** @var MailingListForm $mailingListForm */
        $mailingListForm = $container->get(MailingListForm::class);
        /** @var MailingListMapper $mailingListMapper */
        $mailingListMapper = $container->get(MailingListMapper::class);
        /** @var MailmanService $mailmanService */
        $mailmanService = $container->get(MailmanService::class);

        return new MailingListService(
            $deleteListForm,
            $mailingListForm,
            $mailingListMapper,
            $mailmanService,
        );
    }
}
