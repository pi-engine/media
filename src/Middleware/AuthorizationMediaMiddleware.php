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

class AuthorizationMediaMiddleware implements MiddlewareInterface
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

    /* @var array */
    protected array $authorization
        = [
            'company'    => [],
            'user'       => [],
            'access'     => 'public',
            'user_id'    => 0,
            'company_id' => 0,
            'is_admin'   => 0,
        ];

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
        // Get data
        $routeMatch  = $request->getAttribute('Laminas\Router\RouteMatch');
        $routeParams = $routeMatch->getParams();
        $requestBody = $request->getParsedBody();
        $package = $routeParams['media_access'] ?? $routeParams['package'];

        // Check package and section
        if (!in_array($package, $this->config['authorization']['access'])) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => 'You dont have access to this area !',
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        // Set access type
        $requestBody['access'] = $package;

        // Set authorization
        switch ($package) {
            case 'public':
                $this->authorization['access'] = 'public';
                break;

            case 'company':
                $authorization       = $request->getAttribute('company_authorization');
                $this->authorization = [
                    'company'    => $authorization['company'],
                    'user'       => $authorization['user'],
                    'access'     => 'company',
                    'user_id'    => $authorization['user_id'],
                    'company_id' => $authorization['company_id'],
                    'is_admin'   => $authorization['is_admin'],
                ];
                break;

            case 'private':
                // Get account and set hash
                $account             = $request->getAttribute('account');
                $account['hash']     = hash('sha256', sprintf('%s-%s', $account['id'], $account['time_created']));
                $this->authorization = [
                    'company'    => [],
                    'user'       => $account,
                    'access'     => 'private',
                    'user_id'    => $account['id'],
                    'company_id' => 0,
                    'is_admin'   => 0,
                ];
                break;

            case 'group':
                // Get account and set hash
                $account             = $request->getAttribute('account');
                $account['hash']     = hash('sha256', sprintf('%s-%s', $account['id'], $account['time_created']));
                $this->authorization = [
                    'company'    => [],
                    'user'       => $account,
                    'access'     => 'group',
                    'user_id'    => $account['id'],
                    'company_id' => 0,
                    'is_admin'   => 0,
                ];
                break;

            case 'admin':
                // Get account and set hash
                $account             = $request->getAttribute('account');
                $account['hash']     = hash('sha256', sprintf('%s-%s', $account['id'], $account['time_created']));
                $this->authorization = [
                    'company'    => [],
                    'user'       => $account,
                    'access'     => 'admin',
                    'user_id'    => $account['id'],
                    'company_id' => 0,
                    'is_admin'   => 1,
                ];
                break;
        }

        $request = $request->withParsedBody($requestBody);
        $request = $request->withAttribute('media_authorization', $this->authorization);
        return $handler->handle($request);
    }
}