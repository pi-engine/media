<?php

namespace Media\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\MimeType;
use Laminas\Validator\File\Size;
use Laminas\Validator\File\UploadFile;
use Media\Service\MediaService;
use Media\Validator\SlugValidator;
use Pi\Core\Handler\ErrorHandler;
use Pi\Core\Service\UtilityService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UploadMediaMiddleware implements MiddlewareInterface
{
    public array $validationResult
        = [
            'status'  => true,
            'code'    => StatusCodeInterface::STATUS_OK,
            'message' => '',
        ];

    /** @var ResponseFactoryInterface */
    protected ResponseFactoryInterface $responseFactory;

    /** @var StreamFactoryInterface */
    protected StreamFactoryInterface $streamFactory;

    /** @var ErrorHandler */
    protected ErrorHandler $errorHandler;

    /** @var MediaService */
    protected MediaService $mediaService;

    /** @var UtilityService */
    protected UtilityService $utilityService;

    /* @var array */
    protected array $config;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        ErrorHandler             $errorHandler,
        MediaService             $mediaService,
        UtilityService           $utilityService,
                                 $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->errorHandler    = $errorHandler;
        $this->mediaService    = $mediaService;
        $this->utilityService  = $utilityService;
        $this->config          = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uploadFiles   = $request->getUploadedFiles();
        $requestBody   = $request->getParsedBody();
        $authorization = $request->getAttribute('media_authorization');

        // Check valid
        $this->attacheIsValid($uploadFiles, $authorization, $requestBody);

        // Check if validation result is not true
        if (!$this->validationResult['status']) {
            $request = $request->withAttribute('status', $this->validationResult['code']);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => $this->validationResult['message'],
                    'code'    => $this->validationResult['code'],
                ]
            );
            return $this->errorHandler->handle($request);
        }

        $uploadFile = array_shift($uploadFiles);
        $store      = $this->mediaService->storeMedia($uploadFile, $authorization, $requestBody);
        if (!$store['status']) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request = $request->withAttribute(
                'error',
                [
                    'message' => $store['error']['message'],
                    'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                ]
            );
            return $this->errorHandler->handle($request);
        }

        $request = $request->withAttribute('store_information', $store['data']);
        return $handler->handle($request);
    }

    protected function attacheIsValid($uploadFiles, $authorization, $requestBody): array
    {
        // Check file is set
        if (empty($uploadFiles)) {
            return $this->validationResult = [
                'status'  => false,
                'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                'message' => 'No file uploaded',
            ];
        }

        // Set check class
        $validatorUpload    = new UploadFile();
        $validatorExtension = new Extension($this->config['allowed_extension']);
        $validatorMimeType  = new MimeType($this->config['mime_type']);
        $validatorSize      = new Size($this->config['allowed_size']);

        // Check attached files
        foreach ($uploadFiles as $uploadFile) {
            if (!$validatorUpload->isValid($uploadFile)) {
                return $this->setErrorHandler($validatorUpload);
            }
            if (!$validatorExtension->isValid($uploadFile)) {
                return $this->setErrorHandler($validatorExtension);
            }
            /* if (!$validatorMimeType->isValid($uploadFile)) {
                return $this->setErrorHandler($validatorMimeType);
            } */
            if (!$validatorSize->isValid($uploadFile)) {
                return $this->setErrorHandler($validatorUpload);
            }

            // Check duplicate
            if (isset($this->config['check_duplicate']) && (int)$this->config['check_duplicate'] === 1) {
                // Set slug params
                $slug = sprintf(
                    '%s-%s-%s-%s',
                    $requestBody['access'],
                    $authorization['user_id'],
                    $authorization['company_id'],
                    $uploadFile->getClientFilename()
                );

                // Set validator params and create slug
                $params = [
                    'slug' => $this->utilityService->slug($slug),
                ];

                // Call validator
                $slug = new Input('slug');
                $slug->getValidatorChain()->attach(new SlugValidator($this->mediaService, []));

                // Set input filter
                $inputFilter = new InputFilter();
                $inputFilter->add($slug);
                $inputFilter->setData($params);
                if (!$inputFilter->isValid()) {
                    return $this->setErrorHandler($inputFilter);
                }
            }
        }

        return $this->validationResult;
    }

    protected function setErrorHandler($inputFilter): array
    {
        $message = [];
        foreach ($inputFilter->getInvalidInput() as $error) {
            $message[$error->getName()] = $error->getName() . ': ' . implode(', ', $error->getMessages());
        }

        return $this->validationResult = [
            'status'  => false,
            'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
            'message' => implode(', ', $message),
        ];
    }
}