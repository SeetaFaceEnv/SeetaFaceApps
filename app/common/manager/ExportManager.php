<?php

namespace SeetaAiBuildingCommunity\Common\Manager;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use SeetaAiBuildingCommunity\Common\InterfaceDir\ExportStrategyInterface;
use SeetaAiBuildingCommunity\Modules\Backend\Controllers\export\StaffRecordExportStrategy;

class ExportManager
{
    const EXPORT_STAFF_INFO = 1;

    /**
     * 引擎类型
     * @var string
     * */
    private $type;

    /**
     * 需导出的数据
     * @var array
     * */
    private $data;

    /**
     * ZIP文件名
     * @var string
     * */
    private $zipFileName;

    /**
     * excel文件名
     * @var string
     * */
    private $excelFileName;

    /**
     * ZIP全路径
     * @var string
     * */
    private $dirZip;

    /**
     * excel全路径
     * @var string
     * */
    private $dirExcel;

    /**
     * @var ExportStrategyInterface strategy
     * */

    private $strategy;

    /**
     *构造函数，响应导出类型和数据
     *
     * @param string $type
     * @param string $zipFileName
     * @param string $excelFileName
     * @param array $data
     */
    public function __construct($type, $excelFileName, $data, $zipFileName)
    {
        $this->data = $data;
        $this->type = $type;
        $this->zipFileName = $zipFileName;
        $this->excelFileName = $excelFileName;
        $this->dirZip = EXPORT_FILE_PATH . $this->zipFileName;
        $this->dirExcel = EXPORT_FILE_PATH . $this->excelFileName;

        switch ($type){
            case self::EXPORT_STAFF_INFO:
                $this->strategy = new StaffRecordExportStrategy();
                break;
        }

    }

    /**
     * 导出为zip格式
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export(){

        //假如导出目录不存在，则创建目录
        if (!file_exists(EXPORT_FILE_PATH)) {
            mkdir(EXPORT_FILE_PATH, 0777, true);
        }

        //制作压缩包
        $zip = new \ZipArchive();
        if ($zip->open ($this->dirZip ,\ZipArchive::OVERWRITE) !== true) {
            if($zip->open ($this->dirZip ,\ZipArchive::CREATE) !== true){   // 文件不存在则生成一个新的文件 用CREATE打开文件会追加内容至zip
                throw new \Exception('ZIP 创建失败');
            }
        }
        $zip->addEmptyDir("images"); //生成图片文件夹

        //生成excel文件
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Seetacloud');

        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();

        $this->strategy->exportZip($sheet, $zip, $this->data);

        $writer = new Xlsx($spreadsheet);

        //存储excel文件
        $writer->save($this->dirExcel);

        //将excel添加至压缩包
        $zip->addFile($this->dirExcel, $this->excelFileName);

        //完成压缩包
        $zip->close ();

        //传输压缩包
        $fp=fopen($this->dirZip,"r");
        $file_size=filesize($this->dirZip);

        Header("Content-type: application/zip");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length:".$file_size);
        Header("Content-Disposition: attachment; filename=".$this->zipFileName);

        Header("Access-Control-Allow-Origin:*");
        Header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, OPTIONS');
        Header("Access-Control-Allow-Headers:x-requested-with,content-type");
        $buffer=1024;
        $file_count=0;

        while(!feof($fp) && $file_count<$file_size){
            $file_con=fread($fp,$buffer);
            $file_count+=$buffer;
            echo $file_con;
        }
        fclose($fp);
        unlink($this->dirExcel);
        unlink($this->dirZip);
    }

    /**
     * 导出为excel格式
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportExcel(){

        //假如导出目录不存在，则创建目录
        if (!file_exists(EXPORT_FILE_PATH)) {
            mkdir(EXPORT_FILE_PATH, 0777, true);
        }

        //生成excel文件
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Park');

        $spreadsheet->setActiveSheetIndex(0);
        $sheet = $spreadsheet->getActiveSheet();

        $this->strategy->exportExcel($sheet, $this->data);

        $writer = new xlsx($spreadsheet);

        //存储excel文件
        $writer->save($this->dirExcel);

        //传输压缩包
        $fp=fopen($this->dirExcel,"r");
        $file_size=filesize($this->dirExcel);

        Header("Content-type: application/vnd.ms-excel");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length:".$file_size);
        Header("Content-Disposition: attachment; filename=".$this->excelFileName);

        Header("Access-Control-Allow-Origin: *");
        Header('Access-Control-Allow-Methods: POST');
        Header("Access-Control-Allow-Headers: x-requested-with,content-type");
        $buffer=1024;
        $file_count=0;

        while(!feof($fp) && $file_count<$file_size){
            $file_con=fread($fp,$buffer);
            $file_count+=$buffer;
            echo $file_con;
        }
        fclose($fp);
        unlink($this->dirExcel);
    }
}