<?php

declare(strict_types=1);

namespace Relaticle\CustomFields\Integration\Actions\Imports\Matchers;

use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class LookupMatcher implements LookupMatcherInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function find(mixed $entityInstance, string $value): ?Model
    {
        try {
            return $entityInstance::query()
                ->where($entityInstance->getKeyName(), $value)
                ->first();
        } catch (Throwable $e) {
            // Log the error but don't throw - we'll handle this gracefully by returning null
            $this->logger->warning('Error matching lookup value', [
                'entity' => $entityInstance::class,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
