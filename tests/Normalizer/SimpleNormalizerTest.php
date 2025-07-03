<?php declare(strict_types=1);

namespace Tests\Torr\SimpleNormalizer\Normalizer;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Tests\Torr\SimpleNormalizer\Fixture\DummyVO;
use Torr\SimpleNormalizer\Exception\IncompleteNormalizationException;
use Torr\SimpleNormalizer\Normalizer\SimpleNormalizer;
use PHPUnit\Framework\TestCase;
use Torr\SimpleNormalizer\Normalizer\SimpleObjectNormalizerInterface;
use Torr\SimpleNormalizer\Normalizer\Validator\ValidJsonVerifier;

class SimpleNormalizerTest extends TestCase
{
	/**
	 *
	 */
	public static function provideJsonVerifierEnabled () : iterable
	{
		yield "normalize" => [
			static fn (SimpleNormalizer $normalizer, mixed $value) => $normalizer->normalize($value),
		];

		yield "normalizeArray" => [
			static fn (SimpleNormalizer $normalizer, mixed $value) => $normalizer->normalizeArray($value),
		];

		yield "normalizeMap" => [
			static fn (SimpleNormalizer $normalizer, mixed $value) => $normalizer->normalizeMap($value),
		];
	}

	/**
	 * @dataProvider provideJsonVerifierEnabled
	 */
	public function testJsonVerifierEnabled (callable $call) : void
	{
		$verifier = $this->createMock(ValidJsonVerifier::class);

		$verifier
			->expects($this->once())
			->method('ensureValidOnlyJsonTypes');

		$normalizer = new SimpleNormalizer(
			objectNormalizers: $this->createNormalizerObjectNormalizers("ok"),
			isDebug: true,
			validJsonVerifier: $verifier,
		);

		$call($normalizer, [
			"nested" => [
				"deeply" => [
					"ohai" => new DummyVO(42),
				],
				"valid" => true,
			],
		]);
	}


	/**
	 *
	 */
	public function testJsonVerifierDisabled () : void
	{
		$verifier = $this->createMock(ValidJsonVerifier::class);

		$verifier
			->expects($this->never())
			->method('ensureValidOnlyJsonTypes');

		$normalizer = new SimpleNormalizer(
			objectNormalizers: $this->createNormalizerObjectNormalizers("ok"),
			isDebug: false,
			validJsonVerifier: $verifier,
		);

		$normalizer->normalize([
			"key" => new DummyVO(42),
		]);
		self::assertTrue(true); // Just to ensure the test runs without exceptions
	}


	/**
	 *
	 */
	public function testInvalidNormalizer () : void
	{
		$normalizer = new SimpleNormalizer(
			objectNormalizers: $this->createNormalizerObjectNormalizers(new DummyVO(11)),
			isDebug: true,
			validJsonVerifier: new ValidJsonVerifier(),
		);

		$this->expectException(IncompleteNormalizationException::class);
		$this->expectExceptionMessage("Found a JSON-incompatible value when normalizing. Found 'Tests\Torr\SimpleNormalizer\Fixture\DummyVO' at path '$', but expected only scalars, arrays and empty objects.");
		$normalizer->normalize(new DummyVO(42));
	}


	/**
	 *
	 */
	public function testInvalidNestedNormalizer () : void
	{
		$normalizer = new SimpleNormalizer(
			objectNormalizers: $this->createNormalizerObjectNormalizers(new DummyVO(11)),
			isDebug: true,
			validJsonVerifier: new ValidJsonVerifier(),
		);

		$this->expectException(IncompleteNormalizationException::class);
		$this->expectExceptionMessage("Found a JSON-incompatible value when normalizing. Found 'Tests\Torr\SimpleNormalizer\Fixture\DummyVO' at path '$.nested.deeply.ohai', but expected only scalars, arrays and empty objects.");
		$normalizer->normalize([
			"nested" => [
				"deeply" => [
					"ohai" => new DummyVO(42),
				],
				"valid" => true,
			],
		]);
	}


	/**
	 *
	 */
	private function createNormalizerObjectNormalizers (mixed $returnValue) : ServiceLocator
	{
		return new ServiceLocator([
			DummyVO::class => static fn () => new readonly class ($returnValue) implements SimpleObjectNormalizerInterface
			{
				public function __construct (
					private mixed $returnValue,
				) {}

				public function normalize (object $value, array $context, SimpleNormalizer $normalizer) : mixed
				{
					return $this->returnValue;
				}

				public static function getNormalizedType () : string
				{
					return DummyVO::class;
				}
			},
		]);
	}
}
