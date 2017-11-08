<?php
namespace Sellastica\VatId;

use Sellastica\VatId\Exception;

class VatId
{
	const FORMAT_VISUALLY = 1,
		FORMAT_CONDENSED = 2;

	/** @var string */
	private $originalVatId;
	/** @var string */
	private $vatId;
	/** @var string */
	private $countryCode;
	/** @var array */
	private $data;


	/**
	 * @param string $vatId
	 * @param string $countryCode
	 */
	public function __construct(string $vatId, string $countryCode)
	{
		$this->originalVatId = $vatId;
		$this->vatId = $this->replaceUnknownCharacters($vatId);
		$this->countryCode = strtoupper($countryCode);
	}

	/**
	 * @return bool
	 */
	public function isValid(): bool
	{
		return preg_match('~^' . $this->getData('pattern') . '$~i', $this->vatId);
	}

	/**
	 * @throws Exception\VatIdValidationException
	 */
	public function validate()
	{
		if (!$this->isValid()) {
			throw new Exception\VatIdValidationException();
		}
	}

	/**
	 * @param int $format
	 * @return string
	 */
	public function format(int $format = self::FORMAT_CONDENSED): string
	{
		$formatPattern = $format === self::FORMAT_CONDENSED
			? $this->getData('formatCondensed')
			: $this->getData('formatVisually');
		return preg_replace(
			'~^' . $this->getData('pattern') . '$~i',
			$formatPattern,
			$this->getData('uppercase') ? strtoupper($this->vatId) : $this->vatId
		);
	}

	/**
	 * @param string $vatId
	 * @return string
	 */
	private function replaceUnknownCharacters(string $vatId): string
	{
		return preg_replace('~[^0-9a-zA-Z]~', '', $vatId);
	}

	/**
	 * @param string|null $key
	 * @return array|string
	 * @throws Exception\MissingVatIdDataException
	 */
	private function getData(string $key = null)
	{
		if (!isset($this->data)) {
			$file = __DIR__ . '/data/' . $this->countryCode . '.php';
			if (!file_exists($file)) {
				throw new Exception\MissingVatIdDataException();
			}

			$this->data = include($file);
		}

		return $key ? $this->data[$key] : $this->data;
	}

	/**
	 * @param string $vatId
	 * @param string $countryCode
	 * @param int $format
	 * @return string
	 */
	public static function formatIfPossible(string $vatId, string $countryCode, int $format = self::FORMAT_CONDENSED)
	{
		try {
			$vatIdObject = new VatId($vatId, $countryCode);
			$vatIdObject->validate();
			return $vatIdObject->format($format);
		} catch (Exception\MissingVatIdDataException $e) {
			return $vatId;
		}
	}
}