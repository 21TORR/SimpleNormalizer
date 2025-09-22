<?php declare(strict_types=1);

namespace Tests\Torr\SimpleNormalizer\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Tests\Torr\SimpleNormalizer\Fixture\DummyVO;
use Tests\Torr\SimpleNormalizer\Fixture\DummyVONormalizer;
use Torr\SimpleNormalizer\Exception\IncompleteNormalizationException;
use Torr\SimpleNormalizer\Normalizer\SimpleNormalizer;
use Torr\SimpleNormalizer\Normalizer\Validator\ValidJsonVerifier;

/**
 * @internal
 */
final class SimpleNormalizerTest extends TestCase
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
	 *
	 */
	#[DataProvider('provideJsonVerifierEnabled')]
	public function testJsonVerifierEnabled (callable $call) : void
	{
		$verifier = $this->createMock(ValidJsonVerifier::class);

		$verifier
			->expects(self::once())
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
			->expects(self::never())
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
	 */
	public function testWithoutEntityManager () : void
	{
		$locator = $this->createMock(ServiceLocator::class);

		$locator->expects(self::once())
			->method("get")
			->with(DummyVO::class)
			->willReturn(new DummyVONormalizer(5));

		$normalizer = new SimpleNormalizer(
			objectNormalizers: $locator,
			isDebug: true,
			validJsonVerifier: new ValidJsonVerifier(),
		);

		$normalizer->normalize(new DummyVO(5));
	}

	/**
	 */
	public function testWithEntityManagerButNoMapping () : void
	{
		$metadataFactory = $this->createMock(ClassMetadataFactory::class);
		$metadataFactory
			->expects(self::once())
			->method("hasMetadataFor")
			->with(DummyVO::class)
			->willReturn(false);

		$entityManager = $this->createMock(EntityManagerInterface::class);
		$entityManager->method("getMetadataFactory")->willReturn($metadataFactory);

		$locator = $this->createMock(ServiceLocator::class);

		$locator->expects(self::once())
			->method("get")
			->with(DummyVO::class)
			->willReturn(new DummyVONormalizer(5));

		$normalizer = new SimpleNormalizer(
			objectNormalizers: $locator,
			isDebug: true,
			validJsonVerifier: new ValidJsonVerifier(),
			entityManager: $entityManager,
		);

		$normalizer->normalize(new DummyVO(5));
	}

	/**
	 */
	public function testWithEntityManagerWithMapping () : void
	{
		$classMetaData = new ClassMetadata("SomeClass");

		$metadataFactory = $this->createMock(ClassMetadataFactory::class);
		$metadataFactory
			->expects(self::once())
			->method("hasMetadataFor")
			->with(DummyVO::class)
			->willReturn(true);

		$metadataFactory
			->expects(self::once())
			->method("getMetadataFor")
			->with(DummyVO::class)
			->willReturn($classMetaData);

		$entityManager = $this->createMock(EntityManagerInterface::class);
		$entityManager->method("getMetadataFactory")->willReturn($metadataFactory);

		$locator = $this->createMock(ServiceLocator::class);

		$locator->expects(self::once())
			->method("get")
			->with("SomeClass")
			->willReturn(new DummyVONormalizer(5));

		$normalizer = new SimpleNormalizer(
			objectNormalizers: $locator,
			isDebug: true,
			validJsonVerifier: new ValidJsonVerifier(),
			entityManager: $entityManager,
		);

		$normalizer->normalize(new DummyVO(5));
	}

	/**
	 * @return ServiceLocator<mixed>
	 */
	private function createNormalizerObjectNormalizers (mixed $returnValue) : ServiceLocator
	{
		return new ServiceLocator([
			DummyVO::class => static fn () => new DummyVONormalizer($returnValue),
		]);
	}
}
