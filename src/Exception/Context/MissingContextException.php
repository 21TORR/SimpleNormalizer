<?php declare(strict_types=1);

namespace Torr\SimpleNormalizer\Exception\Context;

use Torr\SimpleNormalizer\Exception\NormalizerExceptionInterface;

/**
 */
final class MissingContextException extends \InvalidArgumentException implements NormalizerExceptionInterface
{
	/**
	 * @param string[] $allKeys
	 */
	public static function create (
		string $missingKey,
		array $allKeys,
		?\Throwable $previous = null,
	) : self
	{
		return new self(
			\sprintf(
				"Missing argument '%s'. Only keys registered are %s",
				$missingKey,
				implode(", ", $allKeys),
			),
			previous: $previous,
		);
	}
}
