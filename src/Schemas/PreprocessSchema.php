<?php

declare(strict_types=1);

namespace Arbor\Validator\Schemas;

use Arbor\Validator\Core\ValidationContext;

class PreprocessSchema extends Schema
{
    /**
     * @var callable(mixed): mixed
     */
    protected $preprocessor;
    protected Schema $innerSchema;

    public function __construct(callable $preprocessor, Schema $innerSchema)
    {
        $this->preprocessor = $preprocessor;
        $this->innerSchema = $innerSchema;
    }

    public function validateValue(mixed $value, ValidationContext $context): mixed
    {
        $preprocessed = ($this->preprocessor)($value);
        return $this->innerSchema->execute($preprocessed, $context);
    }
}
