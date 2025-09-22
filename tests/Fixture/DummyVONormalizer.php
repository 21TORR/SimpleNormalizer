<?php declare(strict_types=1);

namespace Tests\Torr\SimpleNormalizer\Fixture;

use Torr\SimpleNormalizer\Normalizer\SimpleNormalizer;
use Torr\SimpleNormalizer\Normalizer\SimpleObjectNormalizerInterface;

/**
 * @final
 */
readonly class DummyVONormalizer implements SimpleObjectNormalizerInterface
{
	public function __construct (
		private mixed $returnValue = null,
	) {}

	public function normalize (object $value, array $context, SimpleNormalizer $normalizer) : mixed
	{
		return $this->returnValue;
	}

	public static function getNormalizedType () : string
	{
		return DummyVO::class;
	}
}
