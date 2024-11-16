<?php

namespace Pi\Media\Model;

class Relation
{
    private mixed  $id;
    private int    $storage_id;
    private int    $user_id;
    private int    $company_id;
    private string $access;
    private string $relation_module;
    private string $relation_section;
    private int    $relation_item;
    private int    $status;
    private int    $time_create;
    private int    $time_update;
    private string $information;

    public function __construct(
        $storage_id,
        $user_id,
        $company_id,
        $access,
        $relation_module,
        $relation_section,
        $relation_item,
        $status,
        $time_create,
        $time_update,
        $information,
        $id = null
    ) {
        $this->id               = $id;
        $this->storage_id       = $storage_id;
        $this->user_id          = $user_id;
        $this->company_id       = $company_id;
        $this->access           = $access;
        $this->relation_module  = $relation_module;
        $this->relation_section = $relation_section;
        $this->relation_item    = $relation_item;
        $this->status           = $status;
        $this->time_create      = $time_create;
        $this->time_update      = $time_update;
        $this->information      = $information;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStorageId(): int
    {
        return $this->storage_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    public function getAccess(): string
    {
        return $this->access;
    }

    public function getRelationModule(): string
    {
        return $this->relation_module;
    }

    public function getRelationSection(): string
    {
        return $this->relation_section;
    }

    public function getRelationItem(): int
    {
        return $this->relation_item;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getTimeCreate(): int
    {
        return $this->time_create;
    }

    public function getTimeUpdate(): int
    {
        return $this->time_update;
    }

    public function getInformation(): ?string
    {
        return $this->information;
    }
}