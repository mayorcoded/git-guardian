<?php

namespace Gioffreda\Component\GitGuardian\Adapter;

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $anonymousUri;

    /**
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @var RemoteInterface
     */
    private $remote;

    /**
     * Repository constructor.
     * @param string $name
     * @param string $description
     * @param string $anonymousUri
     */
    public function __construct($name, $description, $anonymousUri)
    {
        $this->name = $name;
        $this->description = $description;
        $this->anonymousUri = $anonymousUri;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getAnonymousUri()
    {
        return $this->anonymousUri;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @param RemoteInterface $remote
     */
    public function setRemote(RemoteInterface $remote)
    {
        $this->remote = $remote;
    }

    /**
     * @return RemoteInterface
     */
    public function getRemote()
    {
        return $this->remote;
    }
}
