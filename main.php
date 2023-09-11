<?php

namespace WebIdentifier;

use Exception;
class WebIdentifier {

	protected $_key;

	private $_options = [];

	private $_defaults = [
		"mode" => "text",
		"type" => "",
		"baseColour" => "222222",
		"imageSize" => 200,
		"pixelScale" => 1
	];

	private $_modes = [
		"text",
		"image"
	];

	private $_imageTypes = [

	];

	private $_zwc = [
		"\xE2\x80\x8B",
		"\xEF\xBB\xBF"
	];

	public function __construct($key, $options = [])
	{
		$this->_key = $key;

		$this->_options = array_merge($this->_defaults, $options);
	}

	/**
	 * Generate text or image with embedded identifier
	 *
	 * @param string $identifier
	 * @param string $content
	 * @return string
	 **/
	public function generate($identifier, $content = null)
	{
		if ($this->_options["mode"] === "text" && empty($content)) {
			throw new Exception("Text mode requires a second argument for content to insert identifier into");
		}

		$id = $this->_encryptIdentifier($identifier);

		if ($this->_options["mode"] === "text") {
			$id = $this->_stringToBinary($id);

			return $this->_embedText($id, $content);
		} elseif ($this->_options["mode"] === "image") {

			return $this->_generateImage($id);
		}
	}

	/**
	 * Insert binary identifier as zero-width spaces into given content
	 *
	 * @param string $bianry
	 * @param string $content
	 * @return string
	 **/
	private function _embedText($binary, $content)
	{
		$identifier = "";
		for ($i = 0; $i < strlen($binary); $i++) {
			$replacement = $this->_zwc[$binary[$i]];

			$identifier .= $replacement;
		}

		$output = str_replace(" ", " ".$identifier, $content);

		return $output;
	}

	/**
	 * Creates an image which encodes the given identifier as hexidecimal values, each character being a pixel shade
	 */
	private function _generateImage($identifier)
	{
		$imageSize = $this->_options["imageSize"];

		$img = imagecreatetruecolor($imageSize, $imageSize);

		$colourShades = $this->_generateColourShades();

		$newIdentifier = bin2hex($identifier);

		for ($i = 1; $i <= ($imageSize * $imageSize); $i++) {
			$y = ceil($i / $imageSize);
			$x = $i - (($y - 1) * $imageSize);

			$j = intval($i - ((ceil($i / strlen($newIdentifier)) - 1) * strlen($newIdentifier)) - 1);

			$char = hexdec($newIdentifier[$j]);

			$colour = $colourShades[$char];

			$c = imagecolorallocate($img, $colour["r"], $colour["g"], $colour["b"]);

			imagesetpixel($img, round($x),round($y), $c);
		}


		ob_start();
		imagejpeg($img, NULL, 100);
		$jpeg = ob_get_clean();
		ob_end_clean();

		return "data:image/jpeg;base64," . base64_encode($jpeg);
	}

	/**
	 * Receive string and encrypt using preset key and convert to binary
	 *
	 * @param string $input
	 * @return string
	 **/
	private function _encryptIdentifier($input)
	{
		$key = $this->_key;

		$cipher_method = "aes-256-cbc";
		$enc_key = openssl_digest(php_uname(), "SHA256", TRUE);
		$enc_iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher_method));
		$output = openssl_encrypt($input, $cipher_method, $enc_key, 0, $enc_iv) . "::" . bin2hex($enc_iv);
		unset($token, $cipher_method, $enc_key, $enc_iv);

		return $output;
	}

	/**
	 * Receive encrypted binary string and decrypt using preset key
	 *
	 * @param string $input
	 * @return string
	 **/
	private function _decryptIdentifier($input)
	{
		$key = $this->_key;

		list($crypted_token, $enc_iv) = explode("::", $input);
		$cipher_method = "aes-256-cbc";
		$enc_key = openssl_digest(php_uname(), 'SHA256', TRUE);
		$output = openssl_decrypt($crypted_token, $cipher_method, $enc_key, 0, hex2bin($enc_iv));
		unset($crypted_token, $cipher_method, $enc_key, $enc_iv);

		return $output;
	}

	/**
	 * Convert plain text into binary
	 *
	 * @param string $string
	 * @return string
	 **/
	private function _stringToBinary($string)
	{
		$characters = str_split($string);

		$binary = [];
		foreach ($characters as $character) {
			$data = unpack('H*', $character);
			$binary[] = str_pad(base_convert($data[1], 16, 2), 8, "0", STR_PAD_LEFT);
		}

		return implode('', $binary);
	}

	/**
	 * Convert binary into plain text
	 *
	 * @param string $binary
	 * @return string
	 **/
	private function _binaryToString($binary)
	{
		$binaries = str_split($binary, 8);

		$string = null;
		foreach ($binaries as $binary) {
			$string .= pack('H*', dechex(bindec($binary)));
		}

		return $string;
	}

	/**
	 * Convert binary into plain text
	 *
	 * @param string $binary
	 * @return string
	 **/
	private function _generateColourShades()
	{
		$base = $this->_options["baseColour"];
		$regex_output = [];
		preg_match_all("/[0-9A-F]{2}/", $base, $regex_output);
		$matches = $regex_output[0];

		$baseColour = [
			"r" => hexdec($matches[0]),
			"g" => hexdec($matches[1]),
			"b" => hexdec($matches[2])
		];

		$bright = false;
		if ($baseColour["r"] > 127 || $baseColour["g"] > 127 || $baseColour["b"] > 127) {
			$bright = true;
		}

		$colour_depth = 60;
		$shades = 17;
		$colours = [];
		for ($i = 0; $i <= $shades; $i++) {
			$inc = $bright ? (($colour_depth / $shades) * $i) * -1 : ($colour_depth / $shades) * $i;

			// New codes
			$r = $baseColour["r"] + $inc;
			$g = $baseColour["g"] + $inc;
			$b = $baseColour["b"] + $inc;

			// Clamp digits
			$r = ($r > 255 || $r < 0) ? ($r > 255 ? 255 : 0) : $r;
			$g = ($g > 255 || $g < 0) ? ($g > 255 ? 255 : 0) : $g;
			$b = ($b > 255 || $b < 0) ? ($b > 255 ? 255 : 0) : $b;

			// $r = strtoupper(str_pad(dechex($r), 2, "0", STR_PAD_LEFT));
			// $g = strtoupper(str_pad(dechex($g), 2, "0", STR_PAD_LEFT));
			// $b = strtoupper(str_pad(dechex($b), 2, "0", STR_PAD_LEFT));

			// $colours[] = $r . $g . $b;

			$colours[] = [
				"r" => intval($r),
				"g" => intval($g),
				"b" => intval($b)
			];
		}

		return $colours;
	}

}

?>