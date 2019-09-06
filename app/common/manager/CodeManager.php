<?php
namespace SeetaAiBuildingCommunity\Common\Manager;

use SeetaAiBuildingCommunity\Common\Library\SeetaRedis;

class CodeManager extends RedisManager
{

    public function __construct(SeetaRedis $redis)
    {
        parent::__construct($redis);
    }

    /**
     * 添加验证码
     * @param string $code_tag
     * @param string $verify_code
     * @param array $info
     */
    protected function addCodeBase($code_tag, $verify_code, $info)
    {
        $this->setCache($info, $code_tag, $verify_code);
    }

    /**
     * 检查验证码
     * @param string $code_tag
     * @param string $verify_code
     * @param boolean $ignore_case 是否忽视大小写
     * @param array $info
     * @return int 结果码
     */
    protected function checkCodeBase($code_tag, $verify_code, $ignore_case = true, $info)
    {
        $data = $this->getCache($info, $code_tag);
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
     * 删除验证码
     * @param string $code_tag
     * @param array $info
     */
    protected function clearCodeBase($code_tag, $info)
    {
        $this->delCache($info, $code_tag);
    }
}