<?php
namespace SeetaAiBuildingCommunity\Common\Manager;

use Phalcon\Di;

class VerifyCodeManager extends RedisManager
{
    const DEF_INFO = [
        self::DEF_INFO_PREFIX => 'code_verify:',
        self::DEF_INFO_LIFETIME => 180,
        self::DEF_INFO_IS_HASH => false,
    ];

    public function __construct()
    {
        $config = Di::getDefault()->get('config')->redis;
        parent::__construct($config->prefix . $config->host . ':' . $config->port, $config->password);
    }

    /**
     * add the code
     * @param $code_tag
     * @param $verify_code
     * @throws \Exception
     */
    public function addCode($code_tag, $verify_code)
    {
        parent::setCache( self::DEF_INFO, $code_tag, $verify_code);
    }

    /**
     * check the code
     * @param $code_tag
     * @param $verify_code
     * @param bool $ignore_case 是否忽视大小写
     * @return int 结果码
     * @throws \Exception
     */
    public function checkCode($code_tag, $verify_code, $ignore_case = true)
    {

        $data = parent::getCache(self::DEF_INFO, $code_tag);
        if ($ignore_case) {
            $data = strtolower($data);
            $verify_code = strtolower($verify_code);
        }
        if (!empty($data) && $verify_code == $data) {
            return ERR_SUCCESS;
        }
        else if(empty($data)){
            return ERR_VERIFY_CODE_EXPIRE;
        }
        return ERR_VERIFY_WRONG;
    }

    /**
     * delthe code
     * @param $code_tag
     * @throws \Exception
     */
    public function clearCode($code_tag)
    {
        parent::delCache(self::DEF_INFO, $code_tag);
    }

    /**
     * creat the picture of tag
     * @param $code
     * @param int $width
     * @param int $height
     * @param int $font
     * @return resource
     */
    public static function getCaptcha($code, $width = 100, $height = 30, $fontsize = 19)
    {
        $fontfile = RESOURCE_PATH_VERIFY_CODE;

        // 创建黑色画布
        $image = imagecreatetruecolor($width, $height);
        // 画布定义背景颜色
        $bgcolor = imagecolorallocate($image, 255, 255, 255);
        // 填充颜色
        imagefill($image, 0, 0, $bgcolor);
        // 字符串转数组
        $content = str_split($code);
        // 将验证码填充进入图片
        for ($i = 0; $i < count($content); $i ++) {
            // 字体颜色
            $fontcolor = imagecolorallocate($image, mt_rand(50, 200), mt_rand(50, 200), mt_rand(50, 200));
            // 字体显示的坐标
            $x = $i * $width / count($content) + mt_rand(3, 6);
            $y = mt_rand($fontsize, $height - $fontsize/3.2);
            $angle = mt_rand(-20, 20);
            // 填充内容到画布中
            imagettftext($image, $fontsize, $angle, $x, $y, $fontcolor, $fontfile, $content[$i]);
        }

        // 设置干扰元素
        for ($i = 0; $i < 6; $i++) {
            $pointcolor = imagecolorallocate($image, mt_rand(50, 200), mt_rand(50, 200), mt_rand(50, 200));
            $x = mt_rand(1, $width);
            $y = mt_rand(1, $height);

            imageellipse($image,$x,$y,mt_rand(50, 200),mt_rand(50, 200),$pointcolor);
        }

        for ($i = 0; $i < 200; $i++) {
            $pointcolor = imagecolorallocate($image, mt_rand(50, 200), mt_rand(50, 200), mt_rand(50, 200));
            imagesetpixel($image, mt_rand(1, 99), mt_rand(1, 29), $pointcolor);
        }

        // 设置干扰线
        $numLine = 3;
        $interval = $height / $numLine;
        $lineWith = 1;
        for ($i = 0; $i < $numLine; $i++) {
            $x1 = 1;
            $y1 = mt_rand($interval * $i, $interval * ($i+1));
            $x2 = $width;
            $y2 = mt_rand($interval * $i, $interval * ($i+1));
            $linecolor = imagecolorallocate($image, mt_rand(50, 200), mt_rand(50, 200), mt_rand(50, 200));
            for ($j = 0; $j < $lineWith; $j++){
                imageline($image, $x1, $y1+$j, $x2, $y2+$j, $linecolor);
            }
        }
        // 返回图片信息
        return $image;
    }
}