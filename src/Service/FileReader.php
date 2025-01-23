<?php

namespace Pi\Media\Service;

use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class FileReader implements ServiceInterface
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function readFile(): array
    {
        // Check file is valid
        if (!$this->isFileValid()) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => "File is not readable or does not exist: {$this->filePath}",
                ],
            ];
        }

        // Set extension
        $extension = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));

        // Read file
        return match ($extension) {
            'pdf'         => $this->readPdf(),
            'xlsx', 'xls' => $this->readExcel(),
            'csv'         => $this->readCsv(),
            'json'        => $this->readJson(),
            'docx', 'doc' => $this->readWord(),
            'txt'         => $this->readTxt(),
            default       => [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => "Unsupported file type: $extension",
                ],
            ],
        };
    }

    private function isFileValid(): bool
    {
        return file_exists($this->filePath)/* && is_readable($this->filePath)*/ ;
    }

    private function readPdf(): array
    {
        try {
            $parser = new PdfParser();
            $pdf    = $parser->parseFile($this->filePath);
            $text   = $pdf->getText();

            if (empty(trim($text))) {
                return [
                    'result' => false,
                    'data'   => [],
                    'error'  => [
                        'message' => 'PDF contains non-readable content (e.g., images or non-extractable text).',
                    ],
                ];
            }

            return [
                'result' => true,
                'data'   => explode("\n", $text),
                'error'  => [],
            ]; // Return as array of lines
        } catch (Exception $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error reading PDF file: ' . $e->getMessage(),
                ],
            ];
        }
    }

    private function readExcel(): array
    {
        try {
            $spreadsheet = SpreadsheetIOFactory::load($this->filePath);
            $data        = [];

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rowData = [];
                    foreach ($row->getCellIterator() as $cell) {
                        $rowData[] = $cell->getValue();
                    }
                    $data[] = $rowData; // Add row to data
                }
            }

            return [
                'result' => true,
                'data'   => $data,
                'error'  => [],
            ];
        } catch (Exception $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error reading Excel file: ' . $e->getMessage(),
                ],
            ];
        }
    }

    private function readCsv(): array
    {
        try {
            $data = [];
            if (($file = fopen($this->filePath, 'r')) !== false) {
                while (($row = fgetcsv($file)) !== false) {
                    $data[] = $row; // Add row to data
                }
                fclose($file);
            }

            return [
                'result' => true,
                'data'   => $data,
                'error'  => [],
            ];
        } catch (Exception $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error reading CSV file: ' . $e->getMessage(),
                ],
            ];
        }
    }

    private function readJson(): array
    {
        try {
            $data = json_decode(file_get_contents($this->filePath), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['result' => false, 'data' => [], 'error' => ['message' => 'Invalid JSON file format.']];
            }

            return [
                'result' => true,
                'data'   => is_array($data) ? $data : [],
                'error'  => [],
            ];
        } catch (Exception $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error reading JSON file: ' . $e->getMessage(),
                ],
            ];
        }
    }

    private function readWord(): array
    {
        try {
            $phpWord = WordIOFactory::load($this->filePath);
            $text    = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n"; // Concatenate text
                    }
                }
            }

            return [
                'result' => true,
                'data'   => explode("\n", trim($text)),
                'error'  => [],
            ];
        } catch (Exception $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error reading Word file: ' . $e->getMessage(),
                ],
            ];
        }
    }

    private function readTxt(): array
    {
        try {
            return [
                'result' => true,
                'data'   => explode("\n", file_get_contents($this->filePath)),
                'error'  => [],
            ];
        } catch (Exception $e) {
            return [
                'result' => false,
                'data'   => [],
                'error'  => [
                    'message' => 'Error reading text file: ' . $e->getMessage(),
                ],
            ];
        }
    }
}
