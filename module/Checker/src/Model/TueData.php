<?php

namespace Checker\Model;

use Checker\Model\Exception\LookupException;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Client\Adapter\Exception\RuntimeException as LaminasRuntimeException;
use Laminas\Http\Request;
use Laminas\Json\Json;
use RuntimeException;
use LogicException;

/**
 * Object representing data from a TU/e user
 */
class TueData
{
    /** @var Client $client */
    private Client $client;

    /** @var string $username */
    private string $username;

    /**
     * @var int $status
     */
    private int $status = -1;

    /** @var array $data */
    private ?array $data;

    /**
     * @param array $config an array with membership_api config
     */
    public function __construct(private array $config)
    {
        $this->client = new Client();
        $this->client->setAdapter(Curl::class)
            ->setEncType('application/json');
    }

    /**
     * Load response from TU/e
     *
     * @param string $username TU/e username
     * @return null
     * @throws LookupException in case of an error
     */
    public function setUser($username)
    {
        //Assume error, but keep username we last looked up
        $this->username = $username;
        $this->data = null;
        //Unless we change it later, we assume error
        $this->status = 1;

        $request = new Request();
        $request->setMethod(Request::METHOD_GET)
            ->getHeaders()->addHeaders([
                'Authorization' => 'Bearer ' . $this->config['key'],
            ]);

        $request->setUri($this->config['endpoint'] . $username);
        try {
            $response = $this->client->send($request);
        } catch (LaminasRuntimeException $e) {
            throw new LookupException(
                message: "Could not connect to TU/e servers",
                previousThrowable: $e
            );
        }

        if (200 === $response->getStatusCode()) {
            try {
                $responseContent = Json::decode($response->getBody(), Json::TYPE_ARRAY);
            } catch (RuntimeException $e) {
                throw new LookupException(
                    message: "TU/e lookup tool could not decode JSON",
                    previousThrowable: $e
                );
            }

            if ($responseContent['sAMAccountName'] !== $username) {
                $this->status = 1;
                throw new LookupException(
                    message: "TU/e check API returned user " .
                        $responseContent['sAMAccountName'] . ", but $username was requested"
                );
            }
            $this->status = 0;
            $this->data = $responseContent;
        } elseif (404 === $response->getStatusCode()) {
            // If we did not find a user, mark it differently
            $this->status = 404;
        } else {
            throw new LookupException(
                message: "Request for TUe lookup failed with status code " . $response->getStatusCode()
            );
        }
    }

    /**
     * Check if current TU/e student is studying at TU/e
     * @return bool
     * @throws LookupException when received unexpected TU/e response
     * @throws LogicException when object is not ready to be checked, for example when no data was requested previously
     */
    public function studiesAtTue()
    {
        if ($this->status !== 0 || !is_array($this->data)) {
            throw new LogicException(
                message: "Trying to query study status from object with status $this->status"
            );
        }

        if (!array_key_exists('registrations', $this->data)) {
            throw new LookupException(
                message: "Did not receive `registrations` object. so unable to check study status"
            );
        }

        return !empty($this->data['registrations']);
    }

    /**
     * Check if current TU/e student is enrolled at department
     * @param string $department Department to check for
     * @return bool
     * @throws LookupException when received unexpected TU/e response
     * @throws LogicException when object is not ready to be checked, for example when no data was requested previously
     */
    public function studiesAtDepartment(string $department = "WIN")
    {
        if (!$this->studiesAtTue()) {
            return false;
        }

        return (
            is_array($this->data) &&
            is_array($this->data['registrations']) &&
            in_array($department, array_column($this->data['registrations'], 'dept'))
        );
    }

    /**
     * Get status of last lookup
     * Status of object:
     * -1 = no data loaded,
     * 0 = success,
     * 1 = error,
     * 404 = user not found
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function isValid(): bool
    {
        return $this->data !== null;
    }

    public function computedLastName(): string
    {
        if (!isset($this->data['name']['last'])) {
            return "";
        }
        return trim(explode(",", $this->data['name']['last'])[0]);
    }

    public function computedPrefixName(): string
    {
        if (!isset($this->data['name']['last'])) {
            return "";
        }
        return trim(explode(",", $this->data['name']['last'])[1]);
    }

    /**
     * Function to check how similar data is to a given user
     * @return int the number of changes needed to edit the data using Levenshtein distance
     * @throws LogicException when querying if data is unknown
     */
    public function compareData($firstName = "", $prefixName = "", $lastName = "", $initials = ""): int
    {
        $sum = 0;

        if ($this->status !== 0 || !is_array($this->data)) {
            throw new LogicException(
                message: "Trying to query similarity from object with status $this->status"
            );
        }

        $sum += levenshtein($firstName, $this->data['name']['given']);
        $sum += levenshtein($prefixName, $this->computedPrefixName());
        $sum += levenshtein($lastName, $this->computedLastName());
        $sum += levenshtein(
            strtolower(preg_replace('/\PL/u', '', $initials)),
            strtolower(preg_replace('/\PL/u', '', $this->data['name']['initials']))
        );

        return $sum;
    }
}