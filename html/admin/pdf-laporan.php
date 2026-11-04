<?php
/**
 * PDF Export Laporan
 * 
 * Menggunakan Dompdf untuk convert HTML template ke PDF.
 * Akses: /admin/pdf-laporan.php?tab=overview&periode=bulan_ini
 */
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Capture the HTML from print-laporan.php
ob_start();
include __DIR__ . '/print-laporan.php';
$html = ob_get_clean();

// Remove the auto-print script
$html = preg_replace('/<script>.*?<\/script>/s', '', $html);
$html = preg_replace('/<canvas.*?<\/canvas>/s', '', $html);
$html = preg_replace('/<script src="https:\/\/cdn.*?<\/script>/s', '', $html);

// Setup Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('defaultFont', 'sans-serif');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Generate filename
$tab = $_GET['tab'] ?? 'overview';
$filename = 'Laporan_' . ucfirst($tab) . '_' . date('Ymd_His') . '.pdf';
$originalName = ($tabTitles[$tab] ?? 'Laporan') . ' - ' . ($periodeLabel ?? date('M Y'));

// Save to artifacts folder
$savePath = __DIR__ . '/../assets/reports/' . $filename;
file_put_contents($savePath, $dompdf->output());
$fileSize = filesize($savePath);

// Save to database
$dbConn = getDB();
$userId = $_SESSION['user']['id'] ?? 1;
$dbConn->prepare("INSERT INTO laporan_artifacts (filename, original_name, tab, periode_label, file_size, created_by) VALUES (:f, :n, :t, :p, :s, :u)")
    ->execute([':f' => $filename, ':n' => $originalName, ':t' => $tab, ':p' => $periodeLabel ?? '', ':s' => $fileSize, ':u' => $userId]);

// Output PDF
$dompdf->stream($filename, ['Attachment' => false]);
