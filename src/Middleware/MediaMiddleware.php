<?php

namespace Media\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\MimeType;
use Laminas\Validator\File\Size;
use Laminas\Validator\File\UploadFile;
use Media\Service\MediaService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Handler\ErrorHandler;

class MediaMiddleware implements MiddlewareInterface
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

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ErrorHandler $errorHandler,
        MediaService $mediaService
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->errorHandler    = $errorHandler;
        $this->mediaService    = $mediaService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uploadFiles = $request->getUploadedFiles();
        $parsedBody  = $request->getParsedBody();

        // Check valid
        $this->attacheIsValid($uploadFiles);

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

        return $handler->handle($request);
    }

    protected function setErrorHandler($inputFilter): array
    {
        $message = [];
        foreach ($inputFilter->getInvalidInput() as $error) {
            $message[$error->getName()] = implode(', ', $error->getMessages());
        }

        return $this->validationResult = [
            'status'  => false,
            'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
            'message' => $message,
        ];
    }

    protected function attacheIsValid($uploadFiles)
    {
        $validatorUpload    = new UploadFile();
        $validatorExtension = new Extension(
            [
                'csv',
                'xlsx',
                'xls',
                'png',
                'jpg',
                'jpeg',
            ]
        );
        $validatorMimeType  = new MimeType([
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'application/vnd.oasis.opendocument.spreadsheet',
        ]);
        $validatorSize      = new Size(
            [
                'min' => '1kB',
                'max' => '10MB',
            ]
        );

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
        }
    }
}