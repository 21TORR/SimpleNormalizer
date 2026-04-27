<?php declare(strict_types=1);

namespace Tests\Torr\SimpleNormalizer\Normalizer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Torr\SimpleNormalizer\Test\SimpleNormalizerTestTrait;

/**
 * @internal
 */
final class ArrayNormalizerTest extends TestCase
{
	use SimpleNormalizerTestTrait;

	/**
	 *
	 */
	public static function provideListArray () : iterable
	{
		yield [[1, 2, 3, 4], [1, 2, 3, 4]];
		yield [[1, null, 3, null], [1, 3]];
		yield [[], []];
		yield [[null], []];
	}

	/**
	 *
	 */
	#[DataProvider("provideListArray")]
	public function testListArray (array $input, array $expected) : void
	{
		$normalizer = $this->createNormalizer();
		$actual = $normalizer->normalize($input);

		self::assertSame($expected, $actual);
		self::assertTrue(array_is_list($actual));
	}

	/**
	 *
	 */
	public static function provideAssociativeArray () : iterable
	{
		yield [["a" => 1, "b" => 2, "c" => 3, "d" => 4], ["a" => 1, "b" => 2, "c" => 3, "d" => 4]];
		yield [["a" => 1, "b" => null, "c" => 3, "d" => null], ["a" => 1, "b" => null, "c" => 3, "d" => null]];
		yield [["empty" => null], ["empty" => null]];
		yield [[0 => 1, 1 => null, 3 => 4], [0 => 1, 1 => null, 3 => 4]];
		yield [[1 => 1, 2 => null], [1 => 1, 2 => null]];
		yield [["01" => 1, 1 => 2], ["01" => 1, 1 => 2]];
	}

	/**
	 *
	 */
	#[DataProvider("provideAssociativeArray")]
	public function testAssociativeArray (array $input, array $expected) : void
	{
		$normalizer = $this->createNormalizer();
		$actual = $normalizer->normalize($input);

		self::assertSame($expected, $actual);
		self::assertFalse(array_is_list($actual));
	}
}
