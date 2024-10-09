<?php

namespace Media\Validator;

use Laminas\Validator\AbstractValidator;
use Media\Service\MediaService;

class SlugValidator extends AbstractValidator
{

    /** @var string */
    const TAKEN = 'mediaTaken';

    /** @var array */
    protected array $messageTemplates = [];

    /** @var array */
    protected $options = [];

    /** @var MediaService */
    protected MediaService $mediaService;

    /**
     * {@inheritDoc}
     */
    public function __construct(
        MediaService $mediaService,
                     $options = []
    ) {
        $this->mediaService = $mediaService;
        $this->options      = array_merge($this->options, $options);

        $this->messageTemplates = [
            self::TAKEN => 'Media is duplicated and already exist!',
        ];

        parent::__construct($options);
    }

    /**
     * mobile validate
     *
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        $this->setValue($value);

        $isDuplicated = $this->mediaService->isDuplicated($value);
        if ($isDuplicated) {
            $this->error(static::TAKEN);
            return false;
        }

        return true;
    }
}
