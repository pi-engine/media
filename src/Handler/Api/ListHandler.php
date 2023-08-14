<?php

namespace Media\Handler\Api;

use Laminas\Diactoros\Response\JsonResponse;
use Media\Service\MediaService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ListHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var MediaService */
    protected MediaService $mediaService;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        MediaService $mediaService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->mediaService    = $mediaService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $authorization = $request->getAttribute('company_authorization');
        $requestBody   = $request->getParsedBody();

        $result = $this->mediaService->getMediaList($authorization, $requestBody);
        return new JsonResponse($result);
    }
}