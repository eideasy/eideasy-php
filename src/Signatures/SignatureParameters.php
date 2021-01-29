<?php

namespace EidEasy\Signatures;

class SignatureParameters
{
    /** @var string|null */
    private $signerName;
    /** @var string|null */
    private $contactInfo;
    /** @var string|null */
    private $location;
    /** @var string|null */
    private $reason;

    /**
     * PadesParameters constructor.
     * @param string|null $signerName
     * @param string|null $contactInfo
     * @param string|null $location
     * @param string|null $reason
     */
    public function __construct($reason = null, $signerName = null, $contactInfo = null, $location = null)
    {
        $this->signerName  = $signerName;
        $this->contactInfo = $contactInfo;
        $this->location    = $location;
        $this->reason      = $reason;
    }

    /**
     * @return string|null
     */
    public function getSignerName()
    {
        return $this->signerName;
    }

    /**
     * @param string|null $signerName
     */
    public function setSignerName(string $signerName)
    {
        $this->signerName = $signerName;
    }

    /**
     * @return string|null
     */
    public function getContactInfo()
    {
        return $this->contactInfo;
    }

    /**
     * @param string|null $contactInfo
     */
    public function setContactInfo(string $contactInfo)
    {
        $this->contactInfo = $contactInfo;
    }

    /**
     * @return string|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string|null $location
     */
    public function setLocation(string $location)
    {
        $this->location = $location;
    }

    /**
     * @return string|null
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param string|null $reason
     */
    public function setReason(string $reason)
    {
        $this->reason = $reason;
    }
}
