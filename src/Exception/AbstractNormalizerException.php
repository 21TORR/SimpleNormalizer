<?php declare(strict_types=1);

namespace Torr\SimpleNormalizer\Exception;

abstract class AbstractNormalizerException extends \RuntimeException implements NormalizerExceptionInterface
{
	/** @var list<string> */
	private array $normalizationStack;

	/**
	 * @param list<string> $normalizationStack
	 */
	public function __construct (
		string $message = "",
		int $code = 0,
		?\Throwable $previous = null,
		array $normalizationStack = [],
	)
	{
		parent::__construct($message, $code, $previous);
		$this->normalizationStack = $normalizationStack;
	}

	/**
	 * Returns the stack to the normalization issue.
	 *
	 * @return list<string>
	 */
	public function getNormalizationStack () : array
	{
		return $this->normalizationStack;
	}
}
