<?php

namespace Pi\Media\Handler\Admin;

use Fig\Http\Message\StatusCodeInterface;
use Pi\Core\Response\EscapingJsonResponse;
use Pi\Media\Service\MediaService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GetHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var MediaService */
    protected MediaService $mediaService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        MediaService             $mediaService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->mediaService    = $mediaService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $media = $request->getAttribute('media_item');

        // Set result
        $result = [
            'result' => true,
            'data'   => $media,
            'error'  => [],
        ];

        return new EscapingJsonResponse($result, $result['status'] ?? StatusCodeInterface::STATUS_OK);
    }
}