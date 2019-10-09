<?php

namespace Api\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * API Key model
 *
 * @ORM\Entity
 */
class ApiKey
{

    /**
     * Id
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Name
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Secret
     * @ORM\Column(type="string")
     */
    protected $secret;

    /**
     * Webhook URL
     * @ORM\Column(type="string")
     */
    protected $webhook;


    /**
     * Get the ID.
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the name.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name.
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the secret.
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Set the secret.
     * @param string $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * Get the webhook.
     * @return string
     */
    public function getWebhook()
    {
        return $this->webhook;
    }

    /**
     * Set the webhook.
     * @param string $webhook
     */
    public function setWebhook($webhook)
    {
        $this->webhook = $webhook;
    }
}
