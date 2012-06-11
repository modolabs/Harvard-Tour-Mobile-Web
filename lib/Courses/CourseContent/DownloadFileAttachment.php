<?php

class DownloadFileAttachment {
    protected $id;
    protected $contentID;
    protected $fileSize;
    protected $linkName;
    protected $storageType;
    protected $strName;

    public function getId()
    {
        return $this->id;
    }

    public function setId($newId)
    {
        $this->id = $newId;
        return $this;
    }

    public function getContentID()
    {
        return $this->contentID;
    }

    public function setContentID($newContentID)
    {
        $this->contentID = $newContentID;
        return $this;
    }

    public function getFileSize()
    {
        return $this->fileSize;
    }

    public function setFileSize($newFileSize)
    {
        $this->fileSize = intval($newFileSize);
        return $this;
    }

    public function getFileName()
    {
        return $this->linkName;
    }

    public function setFileName($newLinkName)
    {
        $this->linkName = $newLinkName;
        return $this;
    }

    public function getStorageType()
    {
        return $this->storageType;
    }

    public function setStorageType($newStorageType)
    {
        $this->storageType = $newStorageType;
        return $this;
    }

    public function getStrName()
    {
        return $this->strName;
    }

    public function setStrName($newStrName)
    {
        $this->strName = $newStrName;
        return $this;
    }
}