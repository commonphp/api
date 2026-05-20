<?php

declare(strict_types=1);

namespace CommonPHP\API\Exceptions;

use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Request;
use Throwable;

class UnsupportedContentTypeException extends ApiException
{
    /**
     * @param list<string> $supportedContentTypes
     */
    public function __construct(
        private readonly ?string $contentType = null,
        private readonly array $supportedContentTypes = ['application/json'],
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            'Unsupported API content type' . ($contentType === null ? '.' : ' "' . $contentType . '".'),
            ResponseStatus::UNSUPPORTED_MEDIA_TYPE,
            'api.unsupported_content_type',
            [
                'content_type' => $contentType,
                'supported_content_types' => $supportedContentTypes,
            ],
            previous: $previous,
        );
    }

    /**
     * @param list<string> $supportedContentTypes
     */
    public static function forRequest(Request $request, array $supportedContentTypes = ['application/json']): self
    {
        return new self($request->firstHeader('Content-Type'), $supportedContentTypes);
    }

    public function contentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * @return list<string>
     */
    public function supportedContentTypes(): array
    {
        return $this->supportedContentTypes;
    }
}
