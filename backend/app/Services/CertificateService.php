<?php

namespace App\Services;

use App\Models\Logbook;
use Symfony\Component\HttpFoundation\Response;

class CertificateService
{
    /**
     * Generate standard E-Certificate PDF content.
     */
    public function generateCertificatePdf(Logbook $logbook): string
    {
        $userName = $logbook->user?->name ?? 'Pendaki';
        $gunung = $logbook->pesanan?->jalur?->gunung?->nama_gunung ?? 'Gunung Indonesia';
        $tinggi = $logbook->pesanan?->jalur?->gunung?->tinggi_mdpl ?? 0;
        $jalur = $logbook->pesanan?->jalur?->nama_jalur ?? 'Jalur Pendakian';
        $invoice = $logbook->pesanan?->invoice ?? 'INV-000';
        $date = ($logbook->waktu_summit ?? $logbook->validated_at ?? now())->format('d F Y');

        // Clean structured PDF document
        $text = "SERTIFIKAT PENDAKIAN DIGITAL - SUMMIT v2\n\n"
            ."Diberikan kepada: {$userName}\n"
            ."Atas keberhasilan menaklukkan puncak {$gunung} ({$tinggi} MDPL)\n"
            ."Melalui: {$jalur}\n"
            ."Tanggal Pendakian: {$date}\n"
            ."Nomor Sertifikat: CERT/{$invoice}\n\n"
            .'Terverifikasi secara digital oleh Petugas Basecamp Resmi Summit v2.';

        $pdfStream = "BT\n/F1 14 Tf\n50 700 Td\n18 TL\n";
        foreach (explode("\n", $text) as $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $pdfStream .= "({$escaped}) '\n";
        }
        $pdfStream .= 'ET';

        $streamLen = strlen($pdfStream);

        $pdf = "%PDF-1.4\n"
            ."1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            ."2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
            ."3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n"
            ."4 0 obj\n<< /Length {$streamLen} >>\nstream\n{$pdfStream}\nendstream\nendobj\n"
            ."5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n"
            ."xref\n0 6\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000244 00000 n \n0000000300 00000 n \ntrailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n370\n%%EOF\n";

        return $pdf;
    }

    /**
     * Create streaming PDF response for the certificate.
     */
    public function streamCertificate(Logbook $logbook): Response
    {
        $pdf = $this->generateCertificatePdf($logbook);
        $invoice = $logbook->pesanan?->invoice ?? 'INV';
        $filename = 'Certificate-'.str_replace('/', '-', $invoice).'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Content-Length' => strlen($pdf),
        ]);
    }
}
