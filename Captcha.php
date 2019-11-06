<?php
namespace captcha;
use captcha\Exception;
class Captcha
{
	protected $image;
	protected $width;
	protected $len;
	protected $code;
	protected $type; //验证码类型 0:数字 1:字母 2:混合
	protected $fontPathChinese = "fonts/HYLiGuoXingXingKaiJ-2.ttf";
	protected $fontPathEnglish = "fonts/AlexBrush-Regular.ttf";
	public function __construct($width = 100, $height = 50, $len = 5,$type = 2) 
	{
		if (!extension_loaded("GD")) {
			throw new Exception("未加载GD扩展");
		}

		$this->width = $width;
		$this->height = $height;
		$this->len = in_array($len, [3,4,5,6])?$len:4;
		$this->type = $type;
		$this->code = $this->createCode();
	}

	public function __get($name) 
	{
		if ($name == 'code')
		{
			return $this->code;
		}
		return false;
	}

	public function __destruct()
	{
		imagedestroy($this->image);
	}

	public function regenerateCode($type = 2,$len = 5)
	{
		$this->len = $len;
		$this->type = $type;
		$this->createCode();
	}

	public function outImage()
	{
		//创建画布
		$this->createImage();
		//填充背景
		$this->fillBack();
		//将验证码画到画布上
		$this->drawCode();
		//添加干扰点线
		$this->drawDistrub();
		//输出
		$this->show();
	}

	protected function show()
	{
		header("Content-Type:".image_type_to_mime_type(IMAGETYPE_PNG));
		imagepng($this->image);
	}

	// protected function drawCode()
	// {
	// 	for ($i = 0; $i < mb_strlen($this->code); $i++) {
	// 		imagechar($this->image, $this->height / 2, ceil($this->width / $this->len)  * $i + 5, mt_rand($this->height / 2 - 5,$this->height / 2 + 5), substr($this->code, $i, 1), $this->randomColor(1));
	// 	}
	// }

	protected function drawCode()
	{
		$font = realpath($this->fontPathChinese);
		for ($i = 0; $i < $this->len; $i++) {
			imagettftext($this->image, $this->height / 4, mt_rand(-10,10), ceil($this->width / $this->len)  * $i + 5, mt_rand($this->height / 2 - 5,$this->height / 2 + 5), $this->randomColor(1), $font,mb_substr($this->code, $i, 1));
		}
	}

	/**
	 * $type 0:暗色 1:亮色 2:不限
	 */
	protected function randomColor($type = 0)
	{
		switch ($type) {
			case '0':
				$color = imagecolorallocate($this->image, mt_rand(0,100), mt_rand(0,100), mt_rand(0,100));
				break;
			case '1':
				$color = imagecolorallocate($this->image, mt_rand(120,255), mt_rand(120,255), mt_rand(120,255));
				break;
			case '2':
				$color = imagecolorallocate($this->image, mt_rand(0,255), mt_rand(0,255), mt_rand(0,255));
				break;
			default:
				$color = imagecolorallocate($this->image, mt_rand(0,255), mt_rand(0,255), mt_rand(0,255));
				break;
		}
		return $color;
	}

	protected function fillBack()
	{
		imagefill($this->image, 0, 0, $this->randomColor(0));
		// imagefill($this->image, 0, 0, imagecolorallocate($this->image, 255, 255, 255));
	}

	protected function drawDistrub() 
	{
		//干扰点
		for ($i = 0; $i < 300; $i++) {
			imagesetpixel($this->image, mt_rand(0, $this->width), mt_rand(0,$this->height), $this->randomColor(0));
		}

		//干扰曲线
		for ($i = 0; $i < $this->len; $i++) {
			imagearc($this->image, mt_rand(0, $this->width), mt_rand($this->height / 2 - 5,$this->height / 2 + 5), mt_rand(5,$this->height / 2), mt_rand(5,$this->height / 2), mt_rand(-5,5), mt_rand(-5,5), $this->randomColor(0));
		}
	}

	protected function createImage()
	{
		$this->image = imagecreatetruecolor($this->width, $this->height);
	}

	protected function createCode() 
	{
		switch ($this->type) {
			case '0':
				$code = $this->getNumberCode();
				break;
			case '1':
				$code = $this->getCharCode();
				break;
			case '2':
				$code = $this->getMixedCode();
				break;
			case '3':	//中文
				$code = $this->getChineseCode();
				break;
			default:
				throw new Exception("不支持的验证码类型");
				break;
		}
		return $code;
	}

	protected function getNumberCode()
	{
		$nums = implode("", range(0,9));
		return substr(str_shuffle($nums), 0, $this->len);
	}

	protected function getCharCode()
	{
		$chars = implode("", range('a', 'z'));
		// $chars .= strtoupper($chars);
		return substr(str_shuffle($chars), 0, $this->len);
	}

	protected function getMixedCode()
	{
		$nums = implode("", range(0,9));
		$chars = implode("", range('a', 'z'));
		// $chars .= strtoupper($chars);
		return substr(str_shuffle($nums . $chars), 0, $this->len);
	}

	protected function getChineseCode()
	{
		$code = '';
		for ($i = 0; $i < $this->len; $i++) {
			//chr() 拼接双字节汉字,前一个chr()为高位字节, 后一个为低位字节
			$tmp = chr(mt_rand(0xB0,0xD0)).chr(mt_rand(0xA1,0xF0));
			$code .= iconv('GB2312','UTF-8',$tmp);
		}
		return $code;
	}
}

// $code = new Captcha(200,100,5,3);
// header("code:".$code->code);
// $code->outImage();
