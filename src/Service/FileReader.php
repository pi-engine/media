<?php

namespace Pi\Media\Service;

use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

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
            // Use a lightweight reader
            $reader = SpreadsheetIOFactory::createReaderForFile($this->filePath);
            $reader->setReadDataOnly(true);

            // Optionally read only specific sheet(s)
            // $reader->setLoadSheetsOnly(['Sheet1']);

            // If you expect large files, use chunked reading
            $chunkSize   = 500; // rows per chunk
            $chunkFilter = new class implements IReadFilter {
                private int $startRow = 0;
                private int $endRow   = 0;

                public function setRows(int $startRow, int $chunkSize): void
                {
                    $this->startRow = $startRow;
                    $this->endRow   = $startRow + $chunkSize - 1;
                }

                public function readCell($column, $row, $worksheetName = ''): bool
                {
                    return $row >= $this->startRow && $row <= $this->endRow;
                }
            };
            $reader->setReadFilter($chunkFilter);

            $data     = [];
            $startRow = 1;

            do {
                $chunkFilter->setRows($startRow, $chunkSize);
                $spreadsheet = $reader->load($this->filePath);
                $sheet       = $spreadsheet->getActiveSheet();

                foreach ($sheet->getRowIterator($startRow, $startRow + $chunkSize - 1) as $row) {
                    $rowData = [];
                    foreach ($row->getCellIterator() as $cell) {
                        $value = $cell->getValue();
                        // skip empty cells
                        if ($value !== null && $value !== '') {
                            $rowData[] = $value;
                        }
                    }
                    if (!empty($rowData)) {
                        $data[] = $rowData;
                    }
                }

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                $startRow += $chunkSize;
                gc_collect_cycles(); // free memory
            } while (!empty($rowData)); // stop when no data returned

            return [
                'result' => true,
                'data'   => $data,
                'error'  => [],
            ];
        } catch (Throwable $e) {
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
            $textLines = [];

            // Recursive closure for traversing all elements
            $extractText = function ($element) use (&$extractText, &$textLines) {
                if ($element instanceof Text) {
                    $txt = trim($element->getText());
                    if ($txt !== '') {
                        $textLines[] = $txt;
                    }
                } elseif ($element instanceof TextRun) {
                    foreach ($element->getElements() as $child) {
                        $extractText($child);
                    }
                } elseif ($element instanceof Table) {
                    foreach ($element->getRows() as $row) {
                        foreach ($row->getCells() as $cell) {
                            foreach ($cell->getElements() as $cellElement) {
                                $extractText($cellElement);
                            }
                        }
                    }
                } elseif ($element instanceof AbstractContainer) {
                    foreach ($element->getElements() as $child) {
                        $extractText($child);
                    }
                }
            };

            // Process all sections
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $extractText($element);
                }
            }

            // Remove duplicates and empty lines, normalize line breaks
            $textLines = array_filter(array_map('trim', $textLines));
            $textLines = array_values(array_unique($textLines));

            return [
                'result' => true,
                'data'   => $textLines,
                'error'  => [],
            ];
        } catch (Throwable $e) {
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
