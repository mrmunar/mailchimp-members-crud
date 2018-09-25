<?php
declare(strict_types=1);

namespace Tests\App\Functional\Http\Controllers\MailChimp;

use Tests\App\TestCases\MailChimp\MemberTestCase;

class MembersControllerTest extends MemberTestCase
{
    /**
     * Test application creates successfully member and returns it back with id from MailChimp.
     *
     * @return void
     */
    public function testCreateMemberSuccessfully(): void
    {
        $this->post(\sprintf('/mailchimp/lists/%s/members', $this->listId), static::$memberData);

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseOk();
        $this->seeJson(static::$memberData);
        self::assertArrayHasKey('mail_chimp_id', $content);
        self::assertNotNull($content['mail_chimp_id']);

        // Store MailChimp member id for cleaning purposes
        $this->createdMemberIds[] = $content['mail_chimp_id'];
    }

    /**
     * Test application returns error response with errors when member validation fails.
     *
     * @return void
     */
    public function testCreateMemberValidationFailed(): void
    {
        $this->post(\sprintf('/mailchimp/lists/%s/members', $this->listId));

        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(400);
        self::assertArrayHasKey('message', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertEquals('Invalid data given', $content['message']);

        foreach (\array_keys(static::$memberData) as $key) {
            if (!\in_array($key, static::$required, true)) {
                continue;
            }

            self::assertArrayHasKey($key, $content['errors']);
        }
    }

    /**
     * Test application returns error response when member not found.
     *
     * @return void
     */
    public function testRemoveMemberNotFoundException(): void
    {
        $this->delete(sprintf('/mailchimp/lists/%s/members/invalid-member-id', $this->listId));

        $this->assertMemberNotFoundResponse('invalid-member-id');
    }

    /**
     * Test application returns empty successful response when removing existing member.
     *
     * @return void
     */
    public function testRemoveMemberSuccessfully(): void
    {
        $this->post(sprintf('/mailchimp/lists/%s/members', $this->listId), static::$memberData);
        $member = \json_decode($this->response->content(), true);

        $this->delete(\sprintf('/mailchimp/lists/%s', $member['list_id']));

        $this->assertResponseOk();
        self::assertEmpty(\json_decode($this->response->content(), true));
    }

    /**
     * Test application returns error response when member not found.
     *
     * @return void
     */
    public function testShowMemberNotFoundException(): void
    {
        $this->get(sprintf('/mailchimp/lists/%s/members/invalid-member-id', $this->listId));

        $this->assertMemberNotFoundResponse('invalid-member-id');
    }

    /**
     * Test application returns successful response with member data when requesting existing member.
     *
     * @return void
     */
    public function testShowMemberSuccessfully(): void
    {
        $member = $this->createMember(static::$memberData);

        $this->get(sprintf('/mailchimp/lists/%s/members/%s', $this->listId, $member->getSubscriberHash()));
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseOk();

        foreach (static::$memberData as $key => $value) {
            self::assertArrayHasKey($key, $content);
            self::assertEquals($value, $content[$key]);
        }
    }

    /**
     * Test application returns error response when member not found.
     *
     * @return void
     */
    public function testUpdateMemberNotFoundException(): void
    {
        $this->put(sprintf('/mailchimp/lists/%s/members/invalid-member-id', $this->listId));

        $this->assertMemberNotFoundResponse('invalid-member-id');
    }

    /**
     * Test application returns successfully response when updating existing member with updated values.
     *
     * @return void
     */
    public function testUpdateMemberSuccessfully(): void
    {
        $this->post(sprintf('/mailchimp/lists/%s/members', $this->listId), static::$listData);
        $member = \json_decode($this->response->content(), true);

        if (isset($member['mail_chimp_id'])) {
            $this->createdMemberIds[] = $list['mail_chimp_id']; // Store MailChimp list id for cleaning purposes
        }

        $this->put(\sprintf('/mailchimp/lists/%s/members/%s', $this->listId, $member->getSubscriberHash()), ['ip_opt' => '127.0.0.1']);
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseOk();

        foreach (\array_keys(static::$memberData) as $key) {
            self::assertArrayHasKey($key, $content);
            self::assertEquals('updated', $content['ip_opt']);
        }
    }

    /**
     * Test application returns error response with errors when member validation fails.
     *
     * @return void
     */
    public function testUpdateMemberValidationFailed(): void
    {
        $member = $this->createMember(static::$memberData);

        $this->put(sprintf('/mailchimp/lists/%s/members/%s', $this->listId, $member->getSubscriberHash()), ['vip' => true]);
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(400);
        self::assertArrayHasKey('message', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertArrayHasKey('vip', $content['errors']);
        self::assertEquals('Invalid data given', $content['message']);
    }
}
