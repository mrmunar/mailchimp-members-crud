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
        'email_address' => 'test@test.com',
        'email_type' => 'html',
        'status' => 'pending',
        'merge_fields' => [
            'FNAME' => 'John',
            'LNAME' => 'Doe'
        ],
        'interests' => [
            '9143cf3bd1' => true,
            '3a2a927344'=> false
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
        'ip_opt' => '198.2.191.34',
        'timestamp_opt' => '2018-09-22 10:00:00'
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

        $list = $this->createList($this->getListData);
        $this->listId = $list->getId();
    }

    /**
     * Call MailChimp to delete members created during test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        /** @var Mailchimp $mailChimp */
        $mailChimp = $this->app->make(Mailchimp::class);

        foreach ($this->createdMembers as $member) {
            // Delete list on MailChimp after test
            $mailChimp->delete(\sprintf('lists/%s/members/%s', $member['listId'], $member['subscriberHash']));
        }

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

        $this->assertResponseStatus(404);
        self::assertArrayHasKey('message', $content);
        self::assertEquals(\sprintf('MailChimpMember[%s] not found', $subscriberHash), $content['message']);
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
     * @param array $data
     *
     * @return \App\Database\Entities\MailChimp\MailChimpMember
     */
    protected function createMember(array $data): MailChimpMember
    {
        $member = new MailChimpMember($data);

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
        $list = new MailChimpList($data);

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
        return MailChimpList::$listData;
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
