<?php

namespace Media\Handler\Api;

use Laminas\Diactoros\Response\JsonResponse;
use Media\Service\MediaService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AddPublicHandler implements RequestHandlerInterface
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
        $authentication = $request->getAttribute('account');
        $requestBody    = $request->getParsedBody();
        $uploadFiles    = $request->getUploadedFiles();

        // Set access type
        $requestBody['access'] = 'public';

        $fileList = [];
        foreach ($uploadFiles as $uploadFile) {
            $fileList[] = $this->mediaService->addMedia($uploadFile, $authentication, $requestBody);
        }

        $result = [
            'result' => true,
            'data'   => $fileList,
            'error'  => [],
        ];

        return new JsonResponse($result);
    }
}