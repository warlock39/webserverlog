<?php

namespace AppBundle\WebServerLog\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\DateTimeValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Class DateTimeBetweenValidator.
 */
class DateTimeBetweenValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof DateTimeBetween) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\DateTimeBetween');
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = (string) $value;
        $dates = explode(',', $value);

        if (count($dates) !== 2) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(DateTimeBetween::INVALID_RANGE_ERROR)
                ->addViolation();

            return;
        }

        list($from, $to) = $dates;

        if (!preg_match(DateTimeValidator::PATTERN, $from, $matches) ||
            !preg_match(DateTimeValidator::PATTERN, $to, $matches)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(DateTimeBetween::INVALID_FORMAT_ERROR)
                ->addViolation();

            return;
        }
    }
}
