<?php

declare(strict_types=1);

namespace App\DataFixtures\Mailing;

use App\Entity\Database\ListmonkMailingList;
use App\Entity\Database\MailingList;
use App\Entity\Database\MailmanMailingList;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Override;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function sprintf;
use function ucfirst;

/**
 * The lists a registration offers to subscribe to, each bound to the list of the same name on one of the two mailing
 * list servers.
 *
 * `make seed` creates every one of them on both servers, which is what makes the binding here true: a list is
 * identified on Mailman by `<name>.<domain>` and on Listmonk by a number the seed pins, so both are known before
 * either server is asked. `database:mailinglist:fetch` then recognises what it finds rather than adding it again,
 * and the development stack has a synchronisation that can actually run.
 *
 * One server per list, and between them both are covered. Binding a list to both does not work: whether a membership
 * still has to be carried across is one flag on the membership rather than one per server, so whichever server syncs
 * first clears it and the other never sees the work.
 */
class MailingListFixture extends Fixture
{
    public const string REF_LIST_ANNOUNCEMENTS = 'list-announcements';
    public const string REF_LIST_ACTIVITIES = 'list-activities';

    /**
     * Name, descriptions, whether the registration form offers it, whether it is ticked, and which server carries
     * it -- the Listmonk id the seed gives it, or null where Mailman carries it instead.
     */
    private const array LISTS = [
        [
            'name' => 'announcements',
            'nl' => 'Mededelingen van de vereniging, waaronder uitnodigingen voor de ALV.',
            'en' => 'Announcements from the association, including invitations to the GMM.',
            // Not offered as a choice: every member is on it, which is what the registration form says.
            'on_form' => false,
            'default' => true,
            'listmonk' => null,
            'reference' => self::REF_LIST_ANNOUNCEMENTS,
        ],
        [
            'name' => 'activities',
            'nl' => 'Aankondigingen van activiteiten.',
            'en' => 'Announcements of activities.',
            'on_form' => true,
            'default' => true,
            'listmonk' => 2,
            'reference' => self::REF_LIST_ACTIVITIES,
        ],
        [
            'name' => 'vacancies',
            'nl' => 'Vacatures en carrièremogelijkheden van onze partners.',
            'en' => 'Vacancies and career opportunities from our partners.',
            'on_form' => true,
            'default' => false,
            'listmonk' => 3,
            'reference' => null,
        ],
    ];

    public function __construct(
        #[Autowire(env: 'SERVER_HOSTNAME')]
        private readonly string $hostname,
    ) {
    }

    #[Override]
    public function load(ObjectManager $manager): void
    {
        foreach (self::LISTS as $definition) {
            $name = $definition['name'];
            // Both servers show a list under a capitalised name, which is what they report back when asked.
            $displayName = ucfirst($name);

            $list = new MailingList();
            $list->setName($name);
            $list->setNlDescription($definition['nl']);
            $list->setEnDescription($definition['en']);
            $list->setOnForm($definition['on_form']);
            $list->setDefaultSub($definition['default']);

            if (null === $definition['listmonk']) {
                $mailman = new MailmanMailingList();
                $mailman->setMailmanId(sprintf('%s.%s', $name, $this->hostname));
                $mailman->setName($displayName);
                $mailman->setLastSeen();
                $manager->persist($mailman);

                $list->setMailmanList($mailman);
            } else {
                $listmonk = new ListmonkMailingList();
                $listmonk->setListmonkId($definition['listmonk']);
                $listmonk->setName($displayName);
                $listmonk->setLastSeen();
                $manager->persist($listmonk);

                $list->setListmonkList($listmonk);
            }

            $manager->persist($list);

            if (null === $definition['reference']) {
                continue;
            }

            $this->addReference(
                $definition['reference'],
                $list,
            );
        }

        $manager->flush();
    }
}
