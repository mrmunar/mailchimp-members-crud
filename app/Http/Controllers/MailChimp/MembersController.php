<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Database\Entities\MailChimp\MailChimpMember;
use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mailchimp\Mailchimp;

class MembersController extends Controller
{
    /**
     * @var \Mailchimp\Mailchimp
     */
    private $mailChimp;

    /**
     * MembersController constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Mailchimp\Mailchimp $mailchimp
     */
    public function __construct(EntityManagerInterface $entityManager, Mailchimp $mailchimp)
    {
        parent::__construct($entityManager);

        $this->mailChimp = $mailchimp;
    }

    /**
     * Add a new member member.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request, string $listId): JsonResponse
    {
        // Check if list id exists in local database
        /** @var \App\Database\Entities\MailChimp\MailChimpList|null $list */
        $list = $this->entityManager->getRepository(MailChimpList::class)->findOneBy(array('mailChimpId' => $listId));

        if ($list === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpList[%s] not found', $listId)],
                405
            );
        }

        // Instantiate entity
        $member = new MailChimpMember($request->all());

        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
            // Save member into db
            $this->saveEntity($member);
            // Save member into MailChimp
            $response = $this->mailChimp->post(\sprintf('/lists/%s/members', $listId), $member->toMailChimpArray());
            // Set MailChimp id on the member and save member into db
            $this->saveEntity($member->setMailChimpId($response->get('id')));
        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * Remove MailChimp member.
     *
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(string $listId, string $subscriberHash): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpMember|null $member */
        $member = $this->entityManager->getRepository(MailChimpMember::class)
            ->findOneBy(array('listId' => $listId, 'subscriberHash' => $subscriberHash));

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpMember[%s] not found', $subscriberHash)],
                404
            );
        }

        try {
            // Remove member from database
            $this->removeEntity($member);
            // Remove member from MailChimp
            $this->mailChimp->delete(\sprintf('/lists/%s/members/%s', $member->getListId(), $member->getSubscriberHash()));
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse([]);
    }

    /**
     * Retrieve and return list member.
     *
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $listId, string $subscriberHash): JsonResponse
    {
        // Set filters based on route parameters
        $filters = array('listId' => $listId);

        // Add in subscriber hash if available
        if ($subscriberHash) {
            $filters = array_merge($filters, array('subscriberHash' => $subscriberHash));
        }

        /** @var \App\Database\Entities\MailChimp\MailChimpMember|null $member */
        $member = $this->entityManager->getRepository(MailChimpMember::class)->findOneBy($filters);

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpMember[%s] not found', $subscriberHash)],
                404
            );
        }

        return $this->successfulResponse($member->toArray());
    }

    /**
     * Update MailChimp member.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $memberId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $listId, string $subscriberHash): JsonResponse
    {
        /** @var \App\Database\Entities\MailChimp\MailChimpmember|null $member */
        $member = $this->entityManager->getRepository(MailChimpMember::class)
            ->findOneBy(array('listId' => $listId, 'subscriberHash' => $subscriberHash));

        if ($member === null) {
            return $this->errorResponse(
                ['message' => \sprintf('MailChimpMember[%s] not found', $subscriberHash)],
                404
            );
        }

        // Update member properties
        $member->fill($request->all());

        // Validate entity
        $validator = $this->getValidationFactory()->make($member->toMailChimpArray(), $member->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
            // Update member into database
            $this->saveEntity($member);
            // Update member into MailChimp
            $this->mailChimp->patch(\sprintf('/lists/%s/members/%s', $member->getListId(), $member->getSubscriberHash()), $member->toMailChimpArray());
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($member->toArray());
    }
}
