<?php declare(strict_types=1);

namespace Torr\SimpleNormalizer\Normalizer;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Torr\SimpleNormalizer\Exception\ObjectTypeNotSupportedException;
use Torr\SimpleNormalizer\Exception\UnsupportedTypeException;
use Torr\SimpleNormalizer\Normalizer\Validator\ValidJsonVerifier;

/**
 * The normalizer to use in your app.
 *
 * Can't be readonly, as it needs to be mock-able.
 *
 * The verifier is done on the top-level of every method (instead of at the point where the invalid values could occur
 * = the object normalizers), as this way we can provide a full path to the invalid element in the JSON.
 *
 * @readonly
 * @final
 */
class SimpleNormalizer
{
	/**
	 * @param ServiceLocator<SimpleObjectNormalizerInterface> $objectNormalizers
	 */
	public function __construct (
		private readonly ServiceLocator $objectNormalizers,
		private readonly bool $isDebug = false,
		private readonly ?ValidJsonVerifier $validJsonVerifier = null,
	) {}

	/**
	 */
	public function normalize (mixed $value, array $context = []) : mixed
	{
		$normalizedValue = $this->recursiveNormalize($value, $context);

		if ($this->isDebug)
		{
			$this->validJsonVerifier?->ensureValidOnlyJsonTypes($normalizedValue);
		}

		return $normalizedValue;
	}
	/**
	 */
	public function normalizeArray (array $array, array $context = []) : array
	{
		$normalizedValue = $this->recursiveNormalizeArray($array, $context);

		if ($this->isDebug)
		{
			$this->validJsonVerifier?->ensureValidOnlyJsonTypes($normalizedValue);
		}

		return $normalizedValue;
	}

	/**
	 * Normalizes a map of values.
	 * Will JSON-encode to `{}` when empty.
	 */
	public function normalizeMap (array $array, array $context = []) : array|\stdClass
	{
		// return stdClass if the array is empty here, as it will be automatically normalized to `{}` in JSON.
		$normalizedValue = $this->recursiveNormalizeArray($array, $context) ?: new \stdClass();

		if ($this->isDebug)
		{
			$this->validJsonVerifier?->ensureValidOnlyJsonTypes($normalizedValue);
		}

		return $normalizedValue;
	}



	/**
	 * The actual normalize logic, that recursively normalizes the value.
	 * It must never call one of the public methods above and just normalizes the value.
	 */
	private function recursiveNormalize (mixed $value, array $context = []) : mixed
	{
		if (null === $value || \is_scalar($value))
		{
			return $value;
		}

		if (\is_array($value))
		{
			return $this->recursiveNormalizeArray($value, $context);
		}

		if (\is_object($value))
		{
			// Allow empty stdClass as a way to force a JSON {} instead of an
			// array which would encode to []
			if ($value instanceof \stdClass && [] === get_object_vars($value))
			{
				return $value;
			}

			try
			{
				$className = $value::class;

				if (class_exists(ClassUtils::class))
				{
					$className = ClassUtils::getRealClass($className);
				}

				$normalizer = $this->objectNormalizers->get($className);
				\assert($normalizer instanceof SimpleObjectNormalizerInterface);

				return $normalizer->normalize($value, $context, $this);
			}
			catch (ServiceNotFoundException $exception)
			{
				throw new ObjectTypeNotSupportedException(\sprintf(
					"Can't normalize type %s",
					get_debug_type($value),
				), 0, $exception);
			}
		}

		throw new UnsupportedTypeException(\sprintf(
			"Can't normalize type %s",
			get_debug_type($value),
		));
	}

	/**
	 * The actual customized normalization logic for arrays, that recursively normalizes the value.
	 * It must never call one of the public methods above and just normalizes the value.
	 */
	private function recursiveNormalizeArray (array $array, array $context = []) : array
	{
		$result = [];
		$isList = array_is_list($array);

		foreach ($array as $key => $value)
		{
			$normalized = $this->recursiveNormalize($value, $context);

			// if the array was a list and the normalized value is null, just filter it out
			if ($isList && null === $normalized)
			{
				continue;
			}

			if ($isList)
			{
				// for list: reindex without holes
				$result[] = $normalized;
			}
			else
			{
				// for map: keep key
				$result[$key] = $normalized;
			}
		}

		return $result;
	}
}
