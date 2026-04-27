<?php declare(strict_types=1);

namespace Torr\SimpleNormalizer\Exception\Context;

use Torr\SimpleNormalizer\Exception\NormalizerExceptionInterface;

/**
 */
final class InvalidContextTypeException extends \RuntimeException implements NormalizerExceptionInterface
{
	/**
	 */
	public static function create (
		string $key,
		mixed $value,
		string $expected,
		?\Throwable $previous = null,
	) : static
	{
		return new self(
			\sprintf(
				"Invalid argument type for key '%s': expected %s, but was %s",
				$key,
				$expected,
				get_debug_type($value),
			),
			previous: $previous,
		);
	}
}
