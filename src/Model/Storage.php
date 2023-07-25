<?php

namespace Media\Model;

class Storage
{
    private mixed  $id;
    private mixed  $slug;
    private string $title;
    private int    $user_id;
    private int    $company_id;
    private string $access;
    private string $storage;
    private string $type;
    private string $extension;
    private int    $status;
    private int    $time_create;
    private int    $time_update;
    private string $information;

    public function __construct(
        $slug,
        $title,
        $user_id,
        $company_id,
        $access,
        $storage,
        $type,
        $extension,
        $status,
        $time_create,
        $time_update,
        $information,
        $id = null
    ) {
        $this->slug        = $slug;
        $this->title       = $title;
        $this->user_id     = $user_id;
        $this->company_id  = $company_id;
        $this->access      = $access;
        $this->storage     = $storage;
        $this->type        = $type;
        $this->extension   = $extension;
        $this->status      = $status;
        $this->time_create = $time_create;
        $this->time_update = $time_update;
        $this->information = $information;
        $this->id          = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getTitle(): string
    {
        return $this->title;
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

    public function getStorage(): string
    {
        return $this->storage;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getExtension(): string
    {
        return $this->extension;
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