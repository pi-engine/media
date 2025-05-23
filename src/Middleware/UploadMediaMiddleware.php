<?php

namespace Pi\Media\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\File\Extension;
use Laminas\Validator\File\MimeType;
use Laminas\Validator\File\Size;
use Laminas\Validator\File\UploadFile;
use Pi\Core\Handler\ErrorHandler;
use Pi\Core\Service\ConfigService;
use Pi\Core\Service\UtilityService;
use Pi\Media\Service\MediaService;
use Pi\Media\Validator\SlugValidator;
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
            'result'  => true,
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

    /** @var ConfigService */
    protected ConfigService $configService;

    /* @var array */
    protected array $config;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface   $streamFactory,
        ErrorHandler             $errorHandler,
        MediaService             $mediaService,
        UtilityService           $utilityService,
        ConfigService            $configService,
                                 $config
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory   = $streamFactory;
        $this->errorHandler    = $errorHandler;
        $this->mediaService    = $mediaService;
        $this->utilityService  = $utilityService;
        $this->configService   = $configService;
        $this->config          = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uploadFiles   = $request->getUploadedFiles();
        $requestBody   = $request->getParsedBody();
        $authorization = $request->getAttribute('media_authorization');

        // Validate uploaded files
        $this->validateAttachments($uploadFiles, $authorization, $requestBody);

        // Check validation result
        if (!$this->validationResult['result']) {
            $request = $request->withAttribute('status', $this->validationResult['code']);
            $request = $request->withAttribute('error', [
                'message' => $this->validationResult['message'],
                'code'    => $this->validationResult['code'],
            ]);
            return $this->errorHandler->handle($request);
        }

        // Upload media to storage
        $uploadFile = array_shift($uploadFiles);
        $store      = $this->mediaService->storeMedia($uploadFile, $authorization, $requestBody);

        // Check upload result
        if (!$store['result']) {
            $request = $request->withAttribute('status', StatusCodeInterface::STATUS_FORBIDDEN);
            $request = $request->withAttribute('error', [
                'message' => $store['error']['message'],
                'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
            ]);
            return $this->errorHandler->handle($request);
        }

        $request = $request->withAttribute('store_information', $store['data']);
        return $handler->handle($request);
    }

    protected function validateAttachments($uploadFiles, $authorization, $requestBody): void
    {
        if (empty($uploadFiles)) {
            $this->validationResult = [
                'result'  => false,
                'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                'message' => 'No file uploaded',
            ];
            return;
        }

        // Get custom configs
        $configList = $this->configService->gtyConfigList();
        if (isset($configList['file_upload']['configs']) && !empty($configList['file_upload']['configs'])) {
            foreach ($configList['file_upload']['configs'] as $config) {
                switch ($config['key']) {
                    case 'allowed_size':
                        $this->config['allowed_size']['max'] = $config['value'];
                        break;

                    case 'allowed_extension':
                        $this->config['allowed_extension'] = $config['value'];
                        break;
                }

            }
        }

        // Set validator
        $validatorUpload    = new UploadFile();
        $validatorExtension = new Extension($this->config['allowed_extension']);
        $validatorMimeType  = new MimeType($this->config['mime_type']);
        $validatorSize      = new Size($this->config['allowed_size']);

        $errors = [];
        foreach ($uploadFiles as $uploadFile) {
            // Get temp file path
            $filePath = $uploadFile->getStream()->getMetadata('uri');

            // Check if the file is uploaded correctly
            if (!$validatorUpload->isValid($uploadFile)) {
                $errors = array_merge($errors, $validatorUpload->getMessages());
            }

            // Check if the file extension is valid
            if (!$validatorExtension->isValid($uploadFile)) {
                $errors = array_merge($errors, $validatorExtension->getMessages());
            }

            // Check if the MIME type of the file is valid
            if (!$validatorMimeType->isValid($uploadFile)) {
                $errors = array_merge($errors, $validatorMimeType->getMessages());
            }

            // Check if the file size is valid
            if (!$validatorSize->isValid($uploadFile)) {
                $errors = array_merge($errors, $validatorSize->getMessages());
            }

            // Check actual file type and ensure MIME types match
            if (!empty($this->config['check_real_mime']) && file_exists($filePath)) {
                $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                $realMime = finfo_file($finfo, $filePath);
                finfo_close($finfo);

                // Check if the real MIME type matches the allowed MIME types
                if (!in_array($realMime, $this->config['mime_type'], true)) {
                    $errors[] = "Invalid file type: expected " . implode(', ', $this->config['mime_type']) . " but detected $realMime.";
                }

                // Block dangerous types
                if (in_array($realMime, $this->config['forbidden_type'], true)) {
                    $errors[] = "Uploading $realMime files is not allowed.";
                }
            }

            // Check duplicate files
            if (!empty($this->config['check_duplicate'])) {
                $slug = sprintf(
                    '%s-%s-%s-%s',
                    $requestBody['access'],
                    $authorization['user_id'],
                    $authorization['company_id'],
                    $uploadFile->getClientFilename()
                );

                // Set slug input
                $params    = ['slug' => $this->utilityService->slug($slug)];
                $slugInput = new Input('slug');
                $slugInput->getValidatorChain()->attach(new SlugValidator($this->mediaService, []));

                // Check input validation
                $inputFilter = new InputFilter();
                $inputFilter->add($slugInput);
                $inputFilter->setData($params);

                // Check and set errors if it not valid
                if (!$inputFilter->isValid()) {
                    foreach ($inputFilter->getMessages() as $key => $message) {
                        $errors[$key] = implode(', ', $message);
                    }
                }
            }
        }

        if (!empty($errors)) {
            $this->validationResult = [
                'result'  => false,
                'code'    => StatusCodeInterface::STATUS_FORBIDDEN,
                'message' => implode(', ', $errors),
            ];
        }
    }
}