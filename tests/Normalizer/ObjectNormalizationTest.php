<?php declare(strict_types=1);

namespace Tests\Torr\SimpleNormalizer\Normalizer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Tests\Torr\SimpleNormalizer\Fixture\DummyVO;
use Torr\SimpleNormalizer\Exception\ObjectTypeNotSupportedException;
use Torr\SimpleNormalizer\Normalizer\SimpleNormalizer;
use Torr\SimpleNormalizer\Normalizer\SimpleObjectNormalizerInterface;
use Torr\SimpleNormalizer\Test\SimpleNormalizerTestTrait;

/**
 * @internal
 */
final class ObjectNormalizationTest extends TestCase
{
	use SimpleNormalizerTestTrait;

	/**
	 *
	 */
	public function testNormalizeObject () : void
	{
		$dummyNormalizer = new class() implements SimpleObjectNormalizerInterface {
			public function normalize (object $value, array $context, SimpleNormalizer $normalizer) : mixed
			{
				\assert($value instanceof DummyVO);

				return [
					"id" => $value->id,
				];
			}

			public static function getNormalizedType () : string
			{
				return DummyVO::class;
			}
		};

		$value = new DummyVO(42);
		$normalizer = $this->createNormalizer($dummyNormalizer);

		self::assertSame(["id" => 42], $normalizer->normalize($value));
	}

	/**
	 *
	 */
	public function testEmptyStdClass () : void
	{
		$normalizer = $this->createNormalizer();
		$object = new \stdClass();

		self::assertSame(
			$object,
			$normalizer->normalize($object),
		);
	}

	/**
	 *
	 */
	public function testNonEmptyStdClassIsInvalid () : void
	{
		$normalizer = $this->createNormalizer();
		$object = new \stdClass();
		$object->prop = 5;

		$this->expectException(ObjectTypeNotSupportedException::class);
		$this->expectExceptionMessage("Can't normalize type 'stdClass' in stack stdClass");
		$normalizer->normalize($object);
	}

	/**
	 *
	 */
	public function testMissingNormalizer () : void
	{
		$this->expectException(ObjectTypeNotSupportedException::class);

		$normalizer = $this->createNormalizer();
		$normalizer->normalize(new DummyVO(11));
	}

	/**
	 */
	public function testMissingNormalizerUsesGet () : void
	{
		$locator = $this->createMock(ServiceLocator::class);
		$locator->expects(self::once())
			->method("get")
			->with(DummyVO::class)
			->willThrowException(new \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException(DummyVO::class));

		$normalizer = new SimpleNormalizer($locator);

		$this->expectException(ObjectTypeNotSupportedException::class);
		$this->expectExceptionMessage("Can't normalize type 'Tests\Torr\SimpleNormalizer\Fixture\DummyVO' in stack Tests\Torr\SimpleNormalizer\Fixture\DummyVO");
		$normalizer->normalize(new DummyVO(11));
	}
}
