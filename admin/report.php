<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';


// ======================
// Ambil data transaksi
// ======================
$query = $mysqli->prepare("
    SELECT t.id AS transaction_id, t.date_created, u.username, t.total_price
    FROM transactions t
    JOIN user u ON t.user_id = u.id
    ORDER BY t.date_created ASC
");
$query->execute();
$result = $query->get_result();

$transactions = [];

while ($row = $result->fetch_assoc()) {

    $detail = $mysqli->prepare("
        SELECT p.name, dt.size, dt.quantity
        FROM detail_transaction dt
        JOIN products p ON dt.product_id = p.id
        WHERE dt.transaction_id = ?
    ");
    $detail->bind_param("i", $row['transaction_id']);
    $detail->execute();
    $detailRes = $detail->get_result();

    $productsTxt = "";
    while ($p = $detailRes->fetch_assoc()) {
        $productsTxt .= $p['name']." (".$p['size'].", Qty: ".$p['quantity'].")\n";
    }

    $row['product_text'] = trim($productsTxt);
    $transactions[] = $row;
}

// ======================
// PDF Generator
// ======================
$pdf = new FPDF();
$pdf->AddPage();

// Judul
$pdf->SetFont('Times', 'B', 16);
$pdf->Cell(0, 10, 'Laporan Penjualan', 0, 1, 'C');
$pdf->Ln(5);

// Header tabel
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(20, 10, 'ID', 1, 0, 'C');
$pdf->Cell(40, 10, 'Tanggal', 1, 0, 'C');
$pdf->Cell(40, 10, 'Username', 1, 0, 'C');
$pdf->Cell(60, 10, 'Products', 1, 0, 'C');
$pdf->Cell(30, 10, 'Total', 1, 1, 'C');

$pdf->SetFont('Times', '', 11);

foreach ($transactions as $t) {

    $lineCount = substr_count($t['product_text'], "\n") + 1;
    $rowHeight = $lineCount * 6;

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // ID
    $pdf->MultiCell(20, $rowHeight, $t['transaction_id'], 1, 'C');
    $pdf->SetXY($x + 20, $y);

    // Tanggal
    $pdf->MultiCell(40, $rowHeight, $t['date_created'], 1);
    $pdf->SetXY($x + 60, $y);

    // Username
    $pdf->MultiCell(40, $rowHeight, $t['username'], 1);
    $pdf->SetXY($x + 100, $y);

    // Produk
    $pdf->MultiCell(60, 6, $t['product_text'], 1);
    $pdf->SetXY($x + 160, $y);

    // Total
    $pdf->MultiCell(
        30,
        $rowHeight,
        "Rp " . number_format($t['total_price'], 0, ',', '.'),
        1,
        'R'
    );

    $pdf->Ln();
}

$pdf->Output('D', 'laporan_penjualan.pdf');
exit;
