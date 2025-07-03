<?php declare(strict_types=1);

namespace Torr\SimpleNormalizer\Normalizer\Validator;

/**
 * @final
 */
readonly class InvalidJsonElement
{
	/**
	 */
	public function __construct (
		public mixed $value,
		public array $path,
	) {}
}
