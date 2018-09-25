<?php
declare(strict_types=1);

namespace App\Database\Entities\MailChimp;

use Doctrine\ORM\Mapping as ORM;
use EoneoPay\Utils\Str;

/**
 * @ORM\Entity()
 */
class MailChimpMember extends MailChimpEntity
{
    /**
     * @ORM\Column(name="email_address", type="string")
     *
     * @var string
     */
    private $emailAddress;

    /**
     * @ORM\Column(name="email_type", type="string", nullable=true)
     *
     * @var string
     */
    private $emailType;

    /**
     * @ORM\Column(name="interests", type="array", nullable=true)
     *
     * @var array
     */
    private $interests;

    /**
     * @ORM\Column(name="ip_opt", type="string", nullable=true)
     *
     * @var string
     */
    private $ipOpt;

    /**
     * @ORM\Column(name="ip_signup", type="string", nullable=true)
     *
     * @var string
     */
    private $ipSignup;

    /**
     * @ORM\Column(name="language", type="string", nullable=true)
     *
     * @var string
     */
    private $language;

    /**
     * @ORM\Column(name="location", type="array", nullable=true)
     *
     * @var array
     */
    private $location;

    /**
     * @ORM\Column(name="mail_chimp_id", type="string", nullable=true)
     *
     * @var string
     */
    private $mailChimpId;

    /**
     * @ORM\Column(name="marketing_permissions", type="array", nullable=true)
     *
     * @var array
     */
    private $marketingPermissions;

    /**
     * @ORM\Id()
     * @ORM\Column(name="id", type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     *
     * @var string
     */
    private $memberId;

    /**
     * @ORM\Column(name="merge_fields", type="array", nullable=true)
     *
     * @var array
     */
    private $mergeFields;

    /**
     * @ORM\Column(name="status", type="string")
     *
     * @var string
     */
    private $status;

    /**
     * @ORM\Column(name="subscriber_hash", type="string")
     *
     * @var string
     */
    private $subscriberHash;

    /**
     * @ORM\Column(name="timestamp_opt", type="string", nullable=true)
     *
     * @var string
     */
    private $timestampOpt;

    /**
     * @ORM\Column(name="timestamp_signup", type="string", nullable=true)
     *
     * @var string
     */
    private $timestampSignup;

    /**
     * @ORM\Column(name="vip", type="boolean", nullable=true)
     *
     * @var boolean
     */
    private $vip;


    /**
     * Get id.
     *
     * @return null|string
     */
    public function getId(): ?string
    {
        return $this->memberId;
    }

    /**
     * Get mailchimp id of the list.
     *
     * @return null|string
     */
    public function getMailChimpId(): ?string
    {
        return $this->mailChimpId;
    }

    /**
     * Get subscriber hash.
     *
     * @return null|string
     */
    public function getSubscriberHash(): ?string
    {
        return $this->subscriberHash;
    }

    /**
     * Get validation rules for mailchimp entity.
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'email_address' => 'required|string',
            'email_type' => 'nullable|string',
            'interests' => 'nullable|array',
            'ip_opt' => 'nullable|string',
            'ip_signup' => 'nullable|string',
            'language' => 'nullable|string',
            'location' => 'nullable|array',
            'location.latitude' => 'nullable|numeric',
            'location.longitude' => 'nullable|numeric',
            'mail_chimp_id' => 'nullable|string',
            'marketing_permissions' => 'nullable|array',
            'marketing_permissions.marketing_permission_id' => 'nullable|string',
            'marketing_permissions.enabled' => 'nullable|boolean',
            'merge_fields' => 'nullable|array',
            'status' => 'required|string|in:subscribed,unsubscribed,cleaned,pending',
            'subscriber_hash' => 'required|string',
            'timestamp_opt' => 'nullable|string',
            'timestamp_signup' => 'nullable|string',
            'vip' => 'nullable|string'
        ];
    }

    /**
     * Set email address.
     *
     * @param string $emailAddress
     *
     * @return MailChimpMember
     */
    public function setEmailAddress(string $emailAddress): MailChimpMember
    {
        $this->emailAddress   = $emailAddress;
        $this->subscriberHash = md5(strtolower($emailAddress));

        return $this;
    }

    /**
     * Set email type.
     *
     * @param string $emailType
     *
     * @return MailChimpMember
     */
    public function setEmailType(string $emailType): MailChimpMember
    {
        $this->emailType = $emailType;

        return $this;
    }

    /**
     * Set interests.
     *
     * @param array $interests
     *
     * @return MailChimpMember
     */
    public function setInterests(array $interests): MailChimpMember
    {
        $this->interests = $interests;

        return $this;
    }

    /**
     * Set opt-in ip.
     *
     * @param string $ipOpt
     *
     * @return MailChimpMember
     */
    public function setIpOpt(string $ipOpt): MailChimpMember
    {
        $this->ipOpt = $ipOpt;

        return $this;
    }

    /**
     * Set signup ip.
     *
     * @param string $ipSignup
     *
     * @return MailChimpMember
     */
    public function setIpSignup(string $ipSignup): MailChimpMember
    {
        $this->ipSignup = $ipSignup;

        return $this;
    }

    /**
     * Set language.
     *
     * @param string $notifyOnUnsubscribe
     *
     * @return MailChimpMember
     */
    public function setLanguage(string $language): MailChimpMember
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Set location.
     *
     * @param array $location
     *
     * @return MailChimpMember
     */
    public function setLocation(array $location): MailChimpMember
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Set mailchimp id.
     *
     * @param string $mailChimpId
     *
     * @return MailChimpMember
     */
    public function setMailChimpId(string $mailChimpId): MailChimpMember
    {
        $this->mailChimpId = $mailChimpId;

        return $this;
    }

    /**
     * Set marketing permissions.
     *
     * @param bool $marketingPermissions
     *
     * @return MailChimpMember
     */
    public function setMarketingPermissions(bool $marketingPermissions): MailChimpMember
    {
        $this->marketingPermissions = $marketingPermissions;

        return $this;
    }

    /**
     * Set merge fields.
     *
     * @param array $mergeFields
     *
     * @return MailChimpMember
     */
    public function setMergeFields(array $mergeFields): MailChimpMember
    {
        $this->mergeFields = $mergeFields;

        return $this;
    }

    /**
     * Set status.
     *
     * @param array $status
     *
     * @return MailChimpMember
     */
    public function setStatus(string $status): MailChimpMember
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set opt-in timestamp.
     *
     * @param string $timestampOpt
     *
     * @return MailChimpMember
     */
    public function setTimestampOpt(string $timestampOpt): MailChimpMember
    {
        $this->timestampOpt = $timestampOpt;

        return $this;
    }

    /**
     * Set merge fields.
     *
     * @param string $mergeFields
     *
     * @return MailChimpMember
     */
    public function setTimestampSignup(string $timestampSignup): MailChimpMember
    {
        $this->timestampSignup = $timestampSignup;

        return $this;
    }

    /**
     * Set merge fields.
     *
     * @param boolean $vip
     *
     * @return MailChimpMember
     */
    public function setVip(array $vip): MailChimpMember
    {
        $this->vip = $vip;

        return $this;
    }

    /**
     * Get array representation of entity.
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        $str = new Str();

        foreach (\get_object_vars($this) as $property => $value) {
            $array[$str->snake($property)] = $value;
        }

        return $array;
    }
}
