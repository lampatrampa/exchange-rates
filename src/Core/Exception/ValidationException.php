<?php

declare(strict_types=1);

namespace App\Core\Exception;

use Exception;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Constraints\File;

class ValidationException extends Exception
{
    public function __construct(
        private readonly ConstraintViolationListInterface $violations,
    ) {
        parent::__construct('Entity does not pass validation rules');
    }

    /**
     * @return array<int|string, array<int, mixed>>
     */
    public function getMessages(): array
    {
        $messages = [];
        /** @var ConstraintViolation $violation */
        foreach ($this->violations as $violation) {
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
