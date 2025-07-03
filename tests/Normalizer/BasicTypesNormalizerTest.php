<?php declare(strict_types=1);

namespace Tests\Torr\SimpleNormalizer\Normalizer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Torr\SimpleNormalizer\Exception\UnsupportedTypeException;
use Torr\SimpleNormalizer\Test\SimpleNormalizerTestTrait;

/**
 * @internal
 */
final class BasicTypesNormalizerTest extends TestCase
{
	use SimpleNormalizerTestTrait;

	/**
	 *
	 */
	public static function provideBasicValue () : iterable
	{
		yield [null, null];
		yield [5, 5];
		yield [11.0, 11.0];
		yield [true, true];
		yield ["example", "example"];
	}

	/**
	 *
	 */
	#[DataProvider("provideBasicValue")]
	public function testBasicValue (mixed $input, mixed $expected) : void
	{
		$normalizer = $this->createNormalizer();
		self::assertSame($expected, $normalizer->normalize($input));
	}

	/**
	 *
	 */
	public static function provideInvalidValue () : iterable
	{
		yield [fopen("php://memory", "rb")];
	}

	/**
	 *
	 */
	#[DataProvider("provideInvalidValue")]
	public function testInvalidValue (mixed $input) : void
	{
		$this->expectException(UnsupportedTypeException::class);

		$normalizer = $this->createNormalizer();
		$normalizer->normalize($input);
	}
}
