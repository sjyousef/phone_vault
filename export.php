<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/vendor/autoload.php';
requireLogin();

$pdo    = getPDO();
$saleId = (int)($_GET['sale_id']  ?? 0);
$phoneId = (int)($_GET['phone_id'] ?? 0);

// Fetch sale record
if ($saleId) {
    $stmt = $pdo->prepare(
        "SELECT s.*, p.brand, p.model, p.imei, p.storage, p.color,
                p.battery_health, p.condition_grade, p.selling_price
         FROM sales s JOIN phones p ON s.phone_id = p.id
         WHERE s.id = ? LIMIT 1"
    );
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch();
} elseif ($phoneId) {
    $stmt = $pdo->prepare("SELECT * FROM phones WHERE id = ? LIMIT 1");
    $stmt->execute([$phoneId]);
    $phone = $stmt->fetch();
    $sale  = null;
} else {
    http_response_code(400);
    die('Missing sale_id or phone_id parameter.');
}

// ── PDF Generation ──────────────────────────────────────────
class InvoicePDF extends FPDF {
    public string $invoiceNo = '';
    public string $docTitle  = 'PhoneVault Invoice';

    function Header() {
        $storeName = getStoreSetting('store_name', 'PhoneVault');
        $this->SetFillColor(30, 27, 75);
        $this->Rect(0, 0, 210, 28, 'F');
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(10, 7);
        $this->Cell(0, 12, $storeName, 0, 1, 'L');
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(180, 180, 220);
        $this->SetXY(10, 18);
        $this->Cell(0, 6, 'Second-Hand Phone Store & Warranty Management', 0, 0, 'L');
        $this->SetXY(0, 18);
        $this->Cell(200, 6, $this->docTitle, 0, 0, 'R');
        $this->SetTextColor(0, 0, 0);
        $this->Ln(18);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 10, 'PhoneVault | Generated: ' . date('Y-m-d H:i') . ' | Page ' . $this->PageNo(), 0, 0, 'C');
    }

    function SectionTitle(string $title) {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(240, 240, 250);
        $this->SetTextColor(30, 27, 75);
        $this->Cell(0, 8, '  ' . $title, 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);
    }

    function Row(string $label, string $value, bool $shade = false) {
        if ($shade) $this->SetFillColor(248, 248, 252);
        else        $this->SetFillColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(60, 7, $label, 0, 0, 'L', true);
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 7, $value, 0, 1, 'L', true);
    }

    function HRule() {
        $this->SetDrawColor(200, 200, 220);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(3);
    }
}

$pdf = new InvoicePDF('P', 'mm', 'A4');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

if ($sale) {
    // ── Sales Invoice ──
    $pdf->docTitle  = 'Sales Invoice';
    $pdf->invoiceNo = $sale['invoice_no'];

    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetTextColor(30, 27, 75);
    $pdf->Cell(0, 8, 'SALES INVOICE', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, 'Invoice No: ' . $sale['invoice_no'] . '   |   Date: ' . date('F j, Y', strtotime($sale['created_at'])), 0, 1, 'C');
    $pdf->Ln(4);
    $pdf->HRule();

    $pdf->SectionTitle('Customer Information');
    $pdf->Row('Customer Name',  $sale['customer_name'],  false);
    $pdf->Row('Contact',        $sale['customer_phone'] ?: 'N/A', true);
    $pdf->Row('Payment Method', $sale['payment_method'], false);
    $pdf->Ln(3);

    $pdf->SectionTitle('Device Details');
    $pdf->Row('Brand & Model',    $sale['brand'] . ' ' . $sale['model'], false);
    $pdf->Row('IMEI',             $sale['imei'],                          true);
    $pdf->Row('Storage',          $sale['storage'] ?: 'N/A',             false);
    $pdf->Row('Color',            $sale['color']   ?: 'N/A',             true);
    $pdf->Row('Battery Health',   $sale['battery_health'] . '%',         false);
    $pdf->Row('Condition Grade',  $sale['condition_grade'],               true);
    $pdf->Ln(3);

    $pdf->SectionTitle('Warranty Information');
    $warrantyEnd  = $sale['warranty_end_date'];
    $daysLeft     = (int)ceil((strtotime($warrantyEnd) - time()) / 86400);
    $warrantyStatus = $daysLeft > 0 ? $daysLeft . ' days remaining' : 'EXPIRED';
    $pdf->Row('Warranty Duration', $sale['warranty_duration_days'] . ' days', false);
    $pdf->Row('Warranty End Date', date('F j, Y', strtotime($warrantyEnd)),   true);
    $pdf->Row('Warranty Status',   $warrantyStatus,                            false);
    $pdf->Ln(3);

    $pdf->SectionTitle('Payment Summary');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetFillColor(30, 27, 75);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(60, 10, 'TOTAL AMOUNT PAID', 0, 0, 'L', true);
    $pdf->Cell(0,  10, 'PHP ' . number_format($sale['total_amount'], 2), 0, 1, 'R', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(5);

    // Warranty Policy
    $pdf->HRule();
    $pdf->SectionTitle('Warranty Policy & Return Terms');
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(80, 80, 80);
    $policies = [
        '1. Warranty covers manufacturing defects only. Physical damage, water damage, and unauthorized repairs void the warranty.',
        '2. Returns are accepted within 7 days of purchase for devices with verified defects.',
        '3. Battery health degradation below 20% within the warranty period qualifies for a free battery replacement.',
        '4. Warranty claims must be accompanied by this invoice and the original device.',
        '5. Refunds are processed within 5-7 business days after inspection and approval.',
        '6. Software issues are not covered under the hardware warranty.',
    ];
    foreach ($policies as $p) {
        $pdf->MultiCell(0, 5, $p, 0, 'L');
        $pdf->Ln(1);
    }

    $pdf->Ln(4);
    $pdf->HRule();
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 6, 'Thank you for your purchase! Keep this invoice safe for warranty claims.', 0, 1, 'C');

} else {
    // ── Phone Detail Sheet ──
    $pdf->docTitle = 'Phone Detail Sheet';
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetTextColor(30, 27, 75);
    $pdf->Cell(0, 8, 'PHONE DETAIL SHEET', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, 'Generated: ' . date('F j, Y H:i'), 0, 1, 'C');
    $pdf->Ln(4);
    $pdf->HRule();

    $pdf->SectionTitle('Device Information');
    $pdf->Row('Brand',           $phone['brand'],                false);
    $pdf->Row('Model',           $phone['model'],                true);
    $pdf->Row('IMEI',            $phone['imei'],                 false);
    $pdf->Row('Storage',         $phone['storage'] ?: 'N/A',    true);
    $pdf->Row('Color',           $phone['color']   ?: 'N/A',    false);
    $pdf->Row('Battery Health',  $phone['battery_health'] . '%',true);
    $pdf->Row('Condition Grade', $phone['condition_grade'],      false);
    $pdf->Row('Status',          $phone['status'],               true);
    $pdf->Row('Cost Price',      'PHP ' . number_format($phone['cost_price'], 2),    false);
    $pdf->Row('Selling Price',   'PHP ' . number_format($phone['selling_price'], 2), true);
    $pdf->Row('Added On',        date('F j, Y', strtotime($phone['created_at'])), false);
}

$pdf->Output('I', 'phonevault_' . ($sale ? $sale['invoice_no'] : 'phone_' . $phoneId) . '.pdf');
