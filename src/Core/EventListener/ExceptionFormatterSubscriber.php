<?php

declare(strict_types=1);

namespace App\Core\EventListener;

use App\Core\Exception\ValidationException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class ExceptionFormatterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private KernelInterface $kernel,
        private LoggerInterface $logger,
        private SerializerInterface $serializer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $extraDetails = $responseHeaders = [];
        $exception = $event->getThrowable();

        if ($exception instanceof NotFoundHttpException) {
            $statusCode = $exception->getStatusCode();

            $extraDetails = $exception->getMessage();
            $responseHeaders = $exception->getHeaders();
        } elseif ($exception instanceof HttpExceptionInterface) {
            $previous = $exception->getPrevious();

            if ($previous instanceof ValidationFailedException) {
                $statusCode = Response::HTTP_BAD_REQUEST;
                $extraDetails = $this->getMessages($previous->getViolations());
            } else {
                $statusCode = $exception->getStatusCode();
                $extraDetails = $exception->getMessage();
            }

            $responseHeaders = $exception->getHeaders();
        } elseif ($exception instanceof ValidationException) {
            $statusCode = Response::HTTP_BAD_REQUEST;

            $extraDetails = $exception->getMessages();
        } elseif ($exception instanceof NotNormalizableValueException || $exception instanceof InvalidArgumentException) {
            $statusCode = Response::HTTP_BAD_REQUEST;
            $extraDetails = $exception->getMessage();
        } elseif ($exception instanceof MissingConstructorArgumentsException) {
            $list = array_map(static fn(string $name) => $name . ' is required', $exception->getMissingConstructorArguments());

            $statusCode = Response::HTTP_BAD_REQUEST;
            $extraDetails = implode(PHP_EOL, $list);
        } else {
            $this->logger->error('Exception caught', ['exception' => $exception]);

            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

            if ('prod' === $this->kernel->getEnvironment()) {
                $extraDetails = ['message' => 'Internal Server Error'];
            } elseif ($exception->getMessage() !== '') {
                $extraDetails = [
                    'message' => $exception->getMessage(),
                ];
            }
        }

        $responseData = [];

        if ($extraDetails !== []) {
            $responseData = array_merge($responseData, ['details' => $extraDetails]);
        }

        $json = $this->serializer->serialize(
            $responseData,
            'json',
            array_merge([
                'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
            ], [])
        );

        $response = new JsonResponse($json, $statusCode, $responseHeaders, true);

        $event->setResponse($response);
        $event->stopPropagation();
        $event->allowCustomResponseCode();
    }


    /**
     * @return array<int|string, array<int, mixed>>
     */
    private function getMessages(ConstraintViolationListInterface $violations): array
    {
        $messages = [];

        foreach ($violations as $violation) {
            $item = [
                'message' => $violation->getMessage(),
            ];

            if (is_string($violation->getCode()) && $violation->getCode() !== '') {
                $constraint = $violation->getConstraint();

                if (is_null($constraint)) {
                    throw new \RuntimeException('Constraint is null');
                }

                $item['code'] = $constraint::getErrorName($violation->getCode());
            }

            $path = $violation->getPropertyPath();

            if ($path !== '' && $violation->getConstraint() instanceof File) {
                $path = $violation->getConstraint()->payload;
            }

            $messages[$path][] = $item;
        }

        return $messages;
    }
}
