<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

use Monolog\LogRecord;

final class RequestContextProcessor
{
    public function __construct(
        private readonly RequestContext $requestContext
    ) {
    }

    /**
     * @param array<string, mixed>|LogRecord $record
     *
     * @return array<string, mixed>|LogRecord
     */
    public function __invoke(array|LogRecord $record): array|LogRecord
    {
        if ($record instanceof LogRecord) {
            foreach ($this->requestContext->toArray() as $key => $value) {
                $record->extra[$key] = $value;
            }

            return $record;
        }

        if (!isset($record['extra']) || !is_array($record['extra'])) {
            $record['extra'] = [];
        }

        foreach ($this->requestContext->toArray() as $key => $value) {
            $record['extra'][$key] = $value;
        }

        return $record;
    }
}
