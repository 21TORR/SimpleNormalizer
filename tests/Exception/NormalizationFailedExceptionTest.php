<?php declare(strict_types=1);

namespace Tests\Torr\SimpleNormalizer\Exception;

use PHPUnit\Framework\TestCase;
use Torr\SimpleNormalizer\Exception\NormalizationFailedException;

/**
 * @internal
 */
final class NormalizationFailedExceptionTest extends TestCase
{
	/**
	 */
	public function testStoresNormalizationStack () : void
	{
		$exception = new NormalizationFailedException(
			message: "failed",
			normalizationStack: ["Root", "Nested", "Leaf"],
		);

		self::assertSame(["Root", "Nested", "Leaf"], $exception->getNormalizationStack());
	}
}
