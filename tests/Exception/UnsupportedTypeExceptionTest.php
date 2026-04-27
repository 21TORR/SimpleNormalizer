<?php declare(strict_types=1);

namespace Tests\Torr\SimpleNormalizer\Exception;

use PHPUnit\Framework\TestCase;
use Torr\SimpleNormalizer\Exception\UnsupportedTypeException;

/**
 * @internal
 */
final class UnsupportedTypeExceptionTest extends TestCase
{
	/**
	 */
	public function testStoresNormalizationStack () : void
	{
		$exception = new UnsupportedTypeException(
			message: "failed",
			normalizationStack: ["Root", "Nested", "Leaf"],
		);

		self::assertSame(["Root", "Nested", "Leaf"], $exception->getNormalizationStack());
	}
}
