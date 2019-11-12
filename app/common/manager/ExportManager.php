<?php

namespace SeetaAiBuildingCommunity\Common\Manager;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;


class ExportManager
{
    /**
     * 需导出的数据
     * @var array
     * */
    public $data;


    /**
     * 导出title
     * @var array
     * */
    private $titles;

    /**
     * excel文件名
     * @var string
     * */
    private $excelFileName;


    /**
     * excel全路径
     * @var string
     * */
    private $dirExcel;


    /**
     * 构造函数，响应导出类型和数据
     * @param string $excelFileName
     */
    public function __construct($excelFileName)
    {
        $this->excelFileName = $excelFileName;
        $this->dirExcel = FILE_ROOT_PATH;
    }


    /**
     * 导出为excel格式
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportMemberRecordExcel()
    {

        //生成excel文件
        try {
            $spreadsheet = new Spreadsheet();
            $spreadsheet->getProperties()
                ->setCreator('SeetaFaceEnv')
                ->setTitle('PassRecord')
                ->setSubject('PassRecord')
                ->setDescription('PassRecord');

            /* $spreadsheet->setActiveSheetIndex(0);*/

            $sheet = $spreadsheet->getActiveSheet();
        } catch ( \Exception $e ) {
            echo $e->getMessage();
            return;
        }


        $titles = array_values($this->titles);
        //设置第一行为粗体
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getRowDimension('1')->setRowHeight(18);

        foreach ($titles as $key => $title) {
            $col = $key + 1;
            $sheet->setCellValueExplicitByColumnAndRow($col, 1, $title, DataType::TYPE_STRING);
        }

        $sheet->getDefaultColumnDimension()->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(9);
        $sheet->getColumnDimension('E')->setWidth(9);
        $sheet->getColumnDimension('G')->setWidth(22);

        foreach ($this->data as $num => $rows) {
            $rowNumber = $num + 2;

            foreach (array_values($rows) as $key => $row) {
                $i = $key + 1;
                $sheet->setCellValueExplicitByColumnAndRow($i, $rowNumber, $row, DataType::TYPE_STRING);
            }
        }

        $date = date('Y-m-d_H-i');
        $ExcelName = $this->excelFileName . "_" . $date . ".Xlsx";
        $fileName = $this->dirExcel . "/" . $ExcelName;


        $writer = new Xlsx($spreadsheet);
        //存储excel文件
        $writer->save($fileName);


        //传输压缩包
        $fp = fopen($fileName, "r");
        $file_size = filesize($fileName);


        ob_clean();
        header("Access-Control-Expose-Headers:Filename");
        header("Content-Type:application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length:" . $file_size);
        header("Content-Disposition: attachment; filename=" . $ExcelName);
        header("Filename: " . $ExcelName);
        header("Access-Control-Allow-Origin:*");
        header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, OPTIONS');
        header("Access-Control-Allow-Headers:x-requested-with,content-type");

        $buffer = 1024;
        $file_count = 0;

        while (!feof($fp) && $file_count < $file_size) {
            $file_con = fread($fp, $buffer);
            $file_count += $buffer;
            echo $file_con;
        }
        fclose($fp);
        unlink($fileName);
        exit;
    }

    /**
     * 导出为excel格式
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function statisticsRecordRecordExcel()
    {
        //假如导出目录不存在，则创建目录
        if (!file_exists(FILE_ROOT_PATH)) {
            mkdir(FILE_ROOT_PATH, 0777, true);
        }


        //生成excel文件
        try {
            $spreadsheet = new Spreadsheet();
            $spreadsheet->getProperties()
                ->setCreator('SeetaFaceEnv')
                ->setTitle('statisticsRecord')
                ->setSubject('statisticsRecord')
                ->setDescription('statisticsRecord');

            $spreadsheet->setActiveSheetIndex(0);
            $sheet = $spreadsheet->getActiveSheet();
        } catch ( \Exception $e ) {
            echo $e->getMessage();
        }

        $titles = $this->titles;
        //设置第一行为粗体
        $sheet->getStyle('1')->getFont()->setBold(true);
        $sheet->getRowDimension('1')->setRowHeight(18);

        foreach ($titles as $key => $title) {
            $col = $key + 1;
            $sheet->setCellValueExplicitByColumnAndRow($col, 1, $title, DataType::TYPE_STRING);
        }

        $col_tmp = count($this->titles);
        // 获取列数并设置为自动宽度
        for ($i = 1; $i <= $col_tmp; $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }


        foreach ($this->data as $num => $rows) {
            $rowNumber = $num + 2;
            $j = 1;
            foreach ($rows as $key => $row) {
                $sheet->setCellValueExplicitByColumnAndRow($j, $rowNumber, $row, DataType::TYPE_STRING);
                $j++;
            }
        }
        $writer = new Xlsx($spreadsheet);


        $date = date('Y-m-d_H-i');
        $ExcelName = $this->excelFileName . "_" . $date . ".Xlsx";
        $fileName = $this->dirExcel . "/" . $ExcelName;

        //存储excel文件
        $writer->save($fileName);


        //传输压缩包
        $fp = fopen($fileName, "r");
        $file_size = filesize($fileName);
        header("Access-Control-Expose-Headers:Filename");
        header("Content-Type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Accept-Length:" . $file_size);
        header("Content-Disposition: attachment; filename=" . $ExcelName);
        header("Filename: " . $ExcelName);
        header("Access-Control-Allow-Origin:*");
        header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, OPTIONS');
        header("Access-Control-Allow-Headers:x-requested-with,content-type");
        $buffer = 1024;
        $file_count = 0;

        while (!feof($fp) && $file_count < $file_size) {
            $file_con = fread($fp, $buffer);
            $file_count += $buffer;
            echo $file_con;
        }

        fclose($fp);
        unlink($fileName);

        exit;
    }

    /**
     * 设置表格的表头
     * @param array $data
     * @return array
     */
    public function seTitle($data)
    {
        if (empty($data)) {
            $data['A'] = "设备名称";
            $data['B'] = "设备编码";
            $data['C'] = "人员信息";
            $data['D'] = "流名称";
            $data['E'] = "相似度";
            $data['F'] = "是否通过";
            $data['G'] = "通行时间";
        }

        $this->titles = $data;
        return $this->titles;
    }
}