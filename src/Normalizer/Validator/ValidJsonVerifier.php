<?php declare(strict_types=1);

namespace Torr\SimpleNormalizer\Normalizer\Validator;

use Torr\SimpleNormalizer\Exception\IncompleteNormalizationException;

/**
 * Helper to verify that a given value is valid JSON (scalars, lists or empty objects)
 *
 * @final
 */
class ValidJsonVerifier
{
	/**
	 * Ensures that only valid JSON types are present in the value, that means scalars, arrays and empty objects.
	 */
	public function ensureValidOnlyJsonTypes (mixed $value) : void
	{
		$invalidElement = $this->findInvalidJsonElement($value);

		if (null !== $invalidElement)
		{
			throw new IncompleteNormalizationException(
				sprintf(
					"Found a JSON-incompatible value when normalizing. Found '%s' at path '%s', but expected only scalars, arrays and empty objects.",
					get_debug_type($invalidElement->value),
					implode(".", $invalidElement->path),
				),
			);
		}
	}


	/**
	 * Searches through the value and looks for anything that isn't valid JSON
	 * (scalars, arrays or empty objects).
	 *
	 * @return InvalidJsonElement|null Returns null if everything is valid, otherwise the invalid value.
	 */
	private function findInvalidJsonElement (mixed $value, array $path = ["$"]) : ?InvalidJsonElement
	{
		// scalars are always valid
		if (null === $value || \is_scalar($value))
		{
			return null;
		}

		// only empty stdClass objects are allowed (as they are used to serialize to `{}`)
		if (is_object($value))
		{
			return $value instanceof \stdClass && [] === get_object_vars($value)
				? null
				: new InvalidJsonElement($value, $path);
		}

		if (is_array($value))
		{
			foreach ($value as $key => $item)
			{
				$invalidItem = $this->findInvalidJsonElement(
					$item,
					[...$path, $key],
				);

				if (null !== $invalidItem)
				{
					return $invalidItem;
				}
			}

			return null;
		}

		return new InvalidJsonElement($value, $path);
	}
}
