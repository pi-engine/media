<?php

namespace Media\Service;

/**
 * $filePath = 'path/to/your/file.pdf'; // Change this to your file path
 * $fileReader = new FileReader($filePath);
 * $output = $fileReader->readFile();
 */
class FileReader implements ServiceInterface
{
    private string $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function readFile(): array
    {
        $extension = pathinfo($this->filePath, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'pdf':
                return $this->readPdf();
            case 'xlsx':
            case 'xls':
                return $this->readExcel();
            case 'csv':
                return $this->readCsv();
            case 'json':
                return $this->readJson();
            case 'docx':
                return $this->readWord();
            case 'txt':
                return $this->readTxt();
            default:
                throw new Exception("Unsupported file type: $extension");
        }
    }

    private function readPdf(): array
    {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($this->filePath);
        $text = $pdf->getText();
        return explode("\n", $text); // Return as array of lines
    }

    private function readExcel(): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->filePath);
        $data = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $data[] = $rowData; // Add row to data
            }
        }
        return $data;
    }

    private function readCsv(): array
    {
        $data = [];
        if (($file = fopen($this->filePath, 'r')) !== false) {
            while (($row = fgetcsv($file)) !== false) {
                $data[] = $row; // Add row to data
            }
            fclose($file);
        }
        return $data;
    }

    private function readJson(): array
    {
        $data = json_decode(file_get_contents($this->filePath), true);
        return is_array($data) ? $data : []; // Return as array
    }

    private function readWord(): array
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($this->filePath);
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n"; // Concatenate text
                }
            }
        }
        return explode("\n", trim($text)); // Return as array of lines
    }

    private function readTxt(): array
    {
        return explode("\n", file_get_contents($this->filePath)); // Return as array of lines
    }
}
