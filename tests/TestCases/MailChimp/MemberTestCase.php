<?php
declare(strict_types=1);

namespace Tests\App\TestCases\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpMember;
use Illuminate\Http\JsonResponse;
use Mailchimp\Mailchimp;
use Mockery;
use Mockery\MockInterface;
use Tests\App\TestCases\WithDatabaseTestCase;

abstract class MemberTestCase extends WithDatabaseTestCase
{
    protected const MAILCHIMP_EXCEPTION_MESSAGE = 'MailChimp exception';

    /**
     * @var array
     */
    protected $createdMemberIds = [];


    /**
     * @var string
     */
    protected $listId = '';

    /**
     * @var array
     */
    protected static $memberData = [
        'email_address' => 'migs.morales@gmail.com',
        'email_type' => 'html',
        'status' => 'subscribed',
        'merge_fields' => [
            'FNAME' => 'Johdsdn',
            'LNAME' => 'Dosdse'
        ],
        'language' => '',
        'vip' => false,
        'location' => [
            'latitude' => 0,
            'longitude' => 0
        ],
        'marketing_permissions' => [],
        'ip_signup' => '',
        'timestamp_signup' => '',
        'ip_opt' => '',
        'timestamp_opt' => ''
    ];

    /**
     * @var array
     */
    protected static $listData = [
        'name' => 'New list',
        'permission_reminder' => 'You signed up for updates on Greeks economy.',
        'email_type_option' => false,
        'contact' => [
            'company' => 'Doe Ltd.',
            'address1' => 'DoeStreet 1',
            'address2' => '',
            'city' => 'Doesy',
            'state' => 'Doedoe',
            'zip' => '1672-12',
            'country' => 'US',
            'phone' => '55533344412'
        ],
        'campaign_defaults' => [
            'from_name' => 'John Doe',
            'from_email' => 'john@doe.com',
            'subject' => 'My new campaign!',
            'language' => 'US'
        ],
        'visibility' => 'prv',
        'use_archive_bar' => false,
        'notify_on_subscribe' => 'notify@loyaltycorp.com.au',
        'notify_on_unsubscribe' => 'notify@loyaltycorp.com.au'
    ];

    /**
     * @var array
     */
    protected static $required = [
        'email_address',
        'status'
    ];

    /**
     * Initialize sample list for members
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $list = $this->createList($this->getListData());
        $this->listId = $list->getMailChimpId();
    }

    /**
     * Call MailChimp to delete members created during test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        /** @var Mailchimp $mailChimp */
        // $mailChimp = $this->app->make(Mailchimp::class);

        // foreach ($this->createdMemberIds as $memberId) {
        //     // Delete list on MailChimp after test
        //     $mailChimp->delete(\sprintf('lists/%s/members/%s', $this->listId, $memberId));
        // }

        // $mailChimp->delete(\sprintf('lists/%s', $this->listId));

        parent::tearDown();
    }

    /**
     * Asserts error response when list not found.
     *
     * @param string $listId
     *
     * @return void
     */
    protected function assertMemberNotFoundResponse(string $subscriberHash): void
    {
        $content = \json_decode($this->response->content(), true);

        self::assertArrayHasKey('message', $content);
        self::assertRegexp('^(?=.*\bMailChimpMember\b)(?=.*\bnot\b)(?=.*\bfound\b).*$^', $content['message']);
    }

    /**
     * Asserts error response when MailChimp exception is thrown.
     *
     * @param \Illuminate\Http\JsonResponse $response
     *
     * @return void
     */
    protected function assertMailChimpExceptionResponse(JsonResponse $response): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals(400, $response->getStatusCode());
        self::assertArrayHasKey('message', $content);
        self::assertEquals(self::MAILCHIMP_EXCEPTION_MESSAGE, $content['message']);
    }

    /**
     * Create MailChimp member into database.
     *
     * @return \App\Database\Entities\MailChimp\MailChimpMember
     */
    protected function createMember(): MailChimpMember
    {
        $member = $this->getMemberData();

        $this->post(\sprintf('/mailchimp/lists/%s/members', $this->listId), $member);
        $member = \json_decode($this->response->content(), true);

        if (isset($member['mail_chimp_id'])) {
            $this->createdMemberIds[] = $member['mail_chimp_id']; // Store MailChimp list id for cleaning purposes
        }

        $member = array_merge($member, ['list_id' => $this->listId]);
        $member = new MailChimpMember($member);
        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * Create MailChimp list into database.
     *
     * @param array $data
     *
     * @return \App\Database\Entities\MailChimp\MailChimpList
     */
    protected function createList(array $data): MailChimpList
    {
        $this->post('/mailchimp/lists', $data);
        $list = \json_decode($this->response->content(), true);
        
        $list = new MailChimpList($list);

        $this->entityManager->persist($list);
        $this->entityManager->flush();

        return $list;
    }

    /**
     * Get sample list data
     *
     * @return array
     */
    protected function getListData(): Array
    {
        return static::$listData;
    }

    /**
     * Get sample member data with dynamic email as to not be 
     * banned by mailchimp for spamming with multiple unit tests :)
     *
     * @return array
     */
    protected function getMemberData(): Array
    {
        $updatedMemberData = static::$memberData;
        // "Dynamic Email"
        $updatedMemberData['email_address'] = substr(md5(strval(time())), 0, 8) . '@gmail.com';

        return $updatedMemberData;
    }

    /**
     * Returns mock of MailChimp to trow exception when requesting their API.
     *
     * @param string $method
     *
     * @return \Mockery\MockInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess) Mockery requires static access to mock()
     */
    protected function mockMailChimpForException(string $method): MockInterface
    {
        $mailChimp = Mockery::mock(Mailchimp::class);

        $mailChimp
            ->shouldReceive($method)
            ->once()
            ->withArgs(function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andThrow(new \Exception(self::MAILCHIMP_EXCEPTION_MESSAGE));

        return $mailChimp;
    }
}
