<?php

declare(strict_types=1);

namespace App\Core\MessageMiddleware;

use App\Core\Dto\DtoInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class ValidationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $dto = $envelope->getMessage();

        if ($dto instanceof DtoInterface) {
            $violations = $this->validator->validate($dto);
            if ($violations->count() > 0) {
                $e = new ValidationFailedException($dto, $violations);

                $this->logger->debug('Validation error in ' . get_class($dto), [$e->getMessage()]);

                throw $e;
            }
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
