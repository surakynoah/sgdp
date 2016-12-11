<?php

namespace DoctrineProxies\__CG__\Entity;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class Request extends \Entity\Request implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    /** @private */
    public function __load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;

            if (method_exists($this, "__wakeup")) {
                // call this after __isInitialized__to avoid infinite recursion
                // but before loading to emulate what ClassMetadata::newInstance()
                // provides.
                $this->__wakeup();
            }

            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }

    /** @private */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    
    public function getId()
    {
        if ($this->__isInitialized__ === false) {
            return (int) $this->_identifier["id"];
        }
        $this->__load();
        return parent::getId();
    }

    public function setCreationDate($creationDate)
    {
        $this->__load();
        return parent::setCreationDate($creationDate);
    }

    public function getCreationDate()
    {
        $this->__load();
        return parent::getCreationDate();
    }

    public function setValidationDate($validationDate)
    {
        $this->__load();
        return parent::setValidationDate($validationDate);
    }

    public function getValidationDate()
    {
        $this->__load();
        return parent::getValidationDate();
    }

    public function setComment($comment)
    {
        $this->__load();
        return parent::setComment($comment);
    }

    public function getComment()
    {
        $this->__load();
        return parent::getComment();
    }

    public function setReunion($reunion)
    {
        $this->__load();
        return parent::setReunion($reunion);
    }

    public function getReunion()
    {
        $this->__load();
        return parent::getReunion();
    }

    public function setRequestedAmount($requestedAmount)
    {
        $this->__load();
        return parent::setRequestedAmount($requestedAmount);
    }

    public function getRequestedAmount()
    {
        $this->__load();
        return parent::getRequestedAmount();
    }

    public function setApprovedAmount($approvedAmount)
    {
        $this->__load();
        return parent::setApprovedAmount($approvedAmount);
    }

    public function getApprovedAmount()
    {
        $this->__load();
        return parent::getApprovedAmount();
    }

    public function setStatus($status)
    {
        $this->__load();
        return parent::setStatus($status);
    }

    public function getStatus()
    {
        $this->__load();
        return parent::getStatus();
    }

    public function getStatusByText()
    {
        $this->__load();
        return parent::getStatusByText();
    }

    public function setStatusByText($status)
    {
        $this->__load();
        return parent::setStatusByText($status);
    }

    public function setPaymentDue($paymentDue)
    {
        $this->__load();
        return parent::setPaymentDue($paymentDue);
    }

    public function getPaymentDue()
    {
        $this->__load();
        return parent::getPaymentDue();
    }

    public function setLoanType($loanType)
    {
        $this->__load();
        return parent::setLoanType($loanType);
    }

    public function getLoanType()
    {
        $this->__load();
        return parent::getLoanType();
    }

    public function setContactNumber($contactNumber)
    {
        $this->__load();
        return parent::setContactNumber($contactNumber);
    }

    public function getContactNumber()
    {
        $this->__load();
        return parent::getContactNumber();
    }

    public function setContactEmail($contactEmail)
    {
        $this->__load();
        return parent::setContactEmail($contactEmail);
    }

    public function getContactEmail()
    {
        $this->__load();
        return parent::getContactEmail();
    }

    public function addDocument(\Entity\Document $documents)
    {
        $this->__load();
        return parent::addDocument($documents);
    }

    public function removeDocument(\Entity\Document $documents)
    {
        $this->__load();
        return parent::removeDocument($documents);
    }

    public function getDocuments()
    {
        $this->__load();
        return parent::getDocuments();
    }

    public function addHistory(\Entity\History $history)
    {
        $this->__load();
        return parent::addHistory($history);
    }

    public function removeHistory(\Entity\History $history)
    {
        $this->__load();
        return parent::removeHistory($history);
    }

    public function getHistory()
    {
        $this->__load();
        return parent::getHistory();
    }

    public function setUserOwner(\Entity\User $userOwner)
    {
        $this->__load();
        return parent::setUserOwner($userOwner);
    }

    public function getUserOwner()
    {
        $this->__load();
        return parent::getUserOwner();
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'id', 'creationDate', 'comment', 'reunion', 'requestedAmount', 'approvedAmount', 'status', 'paymentDue', 'loanType', 'contactNumber', 'contactEmail', 'validationDate', 'documents', 'history', 'userOwner');
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            $class = $this->_entityPersister->getClassMetadata();
            $original = $this->_entityPersister->load($this->_identifier);
            if ($original === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            foreach ($class->reflFields as $field => $reflProperty) {
                $reflProperty->setValue($this, $reflProperty->getValue($original));
            }
            unset($this->_entityPersister, $this->_identifier);
        }
        
    }
}