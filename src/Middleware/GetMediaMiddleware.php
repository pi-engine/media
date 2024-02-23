<?php

namespace Media\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Media\Service\MediaService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Handler\ErrorHandler;

class GetMediaMiddleware implements MiddlewareInterface
{
    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    /** @var MediaService */
    protected MediaService $mediaService;

    /* @var array */
    protected array $config;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ErrorHandler $errorHandler,
        MediaService $mediaService,
        $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->errorHandler    = $errorHandler;
        $this->mediaService    = $mediaService;
        $this->config          = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $account = $request->getAttribute('account');
        $roles = $request->getAttribute('roles');
        $authorization = $request->getAttribute('media_authorization');
        $requestBody = $request->getParsedBody();

        // Check ID is set
        if (empty($requestBody['id']) || !is_numeric($requestBody['id'])) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_BAD_REQUEST);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => 'You should set media ID',
                    'code'    => StatusCodeInterface::STATUS_BAD_REQUEST,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Get media
        $media = $this->mediaService->getMedia($requestBody);

        // Check media
        if (empty($media) || (int)$media['status'] !== 1 || $media['access'] != $authorization['access']) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => 'You should select media !',
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Check media
        $hasAccess = true;
        switch ($media['access']) {
            case 'company':
                // Check company access
                if ((int)$media['company_id'] !== $authorization['company_id']) {
                    $hasAccess = false;
                }

                // Check just admin allow doenload other users media
                if ((int)$account['id'] !== (int)$media['user_id']) {
                    if (!$authorization['is_admin']) {
                        $hasAccess = false;
                    }
                }
                break;

            case 'private':
                /* if ((int)$account['id'] !== (int)$media['user_id']) {
                    $hasAccess = false;
                } */
                break;

            case 'group':
                if ((int)$account['id'] !== (int)$media['user_id']) {
                    if (!isset($media['information']['access']) || empty($media['information']['access'])) {
                        $hasAccess = false;
                    } elseif (!in_array((int)$account['id'], $media['information']['access'])) {
                        $hasAccess = false;
                    }
                }
                break;
        }

        if (!$hasAccess) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => 'You dont have a access to this media !',
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        $request = $request->withAttribute('media_item', $media);
        return $handler->handle($request);
    }
}