<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use ZipArchive;

class CvTextExtractor
{
    public function extract(string $path, ?string $mimeType = null): string
    {
        $mimeType = $mimeType ?: (mime_content_type($path) ?: '');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $text = match (true) {
            str_contains($mimeType, 'pdf') || $extension === 'pdf' => $this->fromPdf($path),
            str_contains($mimeType, 'wordprocessingml') || $extension === 'docx' => $this->fromDocx($path),
            default => throw new \InvalidArgumentException('Unsupported CV format. Upload a PDF or DOCX file.'),
        };

        $text = trim(preg_replace('/[ \t]+/', ' ', $text) ?? $text);
        $text = trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);

        if ($text === '') {
            throw new \RuntimeException('Could not extract text from the uploaded CV.');
        }

        return $text;
    }

    protected function fromPdf(string $path): string
    {
        try {
            $pdf = (new Parser)->parseFile($path);

            return $pdf->getText();
        } catch (\Throwable $e) {
            Log::error('PDF parse failed: '.$e->getMessage());

            throw new \RuntimeException('Could not read the PDF. Try exporting it again or upload a DOCX file.');
        }
    }

    protected function fromDocx(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Could not read the DOCX file.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false || $xml === '') {
            throw new \RuntimeException('The DOCX file does not contain readable text.');
        }

        $xml = str_replace(['</w:p>', '<w:br', '</w:tr>'], ["\n", "\n", "\n"], $xml);
        $text = strip_tags($xml);

        return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
