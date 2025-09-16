<?php
// Ensure error reporting is on for debugging PDF issues, turn off for production
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php'; 
require_once __DIR__ . '/includes/fpdf/fpdf.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die("Access Denied. Please login to view invoices.");
}

if (!isset($_GET['invoice_id']) || !is_numeric($_GET['invoice_id']) || (int)$_GET['invoice_id'] <= 0) {
    die("Invalid or missing Invoice ID specified in URL."); // More specific error
}

$invoice_id_param = (int)$_GET['invoice_id'];
$current_user_id = (int)$_SESSION['id'];
$current_user_role = $_SESSION['role'];

$sql = "SELECT 
            i.id as invoice_id, i.invoice_uid, i.amount, i.currency, i.status as invoice_status, 
            i.created_at as invoice_created_at, i.due_date,
            a.id as appointment_id, a.appointment_date, a.appointment_time,
            u.name as patient_name, u.email as patient_email,
            d.name as doctor_name, d.specialization as doctor_specialization, d.email as doctor_email
        FROM invoices i
        JOIN appointments a ON i.appointment_id = a.id
        JOIN users u ON i.user_id = u.id
        JOIN doctors d ON i.doctor_id = d.id
        WHERE i.id = ?";

$can_access_this_invoice = false;

if ($current_user_role == 'admin') {
    // Admin can access any invoice by its ID
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $invoice_id_param);
    $can_access_this_invoice = true; 
} elseif ($current_user_role == 'user') {
    $sql_user = $sql . " AND i.user_id = ?";
    $stmt = mysqli_prepare($conn, $sql_user);
    mysqli_stmt_bind_param($stmt, "ii", $invoice_id_param, $current_user_id);
    $can_access_this_invoice = true;
} elseif ($current_user_role == 'doctor') {
    $sql_doctor = $sql . " AND i.doctor_id = ?";
    $stmt = mysqli_prepare($conn, $sql_doctor);
    mysqli_stmt_bind_param($stmt, "ii", $invoice_id_param, $current_user_id);
    $can_access_this_invoice = true;
} else {
    die("Access Denied: Invalid user role for this action."); // Should not happen
}


if (!$stmt) {
    die("Database query preparation error: " . mysqli_error($conn));
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$invoice = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$invoice) { // This check now correctly considers admin or role-specific query results
    die("Invoice not found, or you do not have permission to access it. (ID: " . htmlspecialchars($invoice_id_param) . ")");
}


// --- Currency Symbol Handling ---
$db_currency_code = strtoupper($invoice['currency']);
$display_currency_symbol_utf8 = '₹'; // The UTF-8 Rupee symbol
$display_currency_symbol_fpdf = 'Rs.'; // Fallback for FPDF core fonts or if iconv fails

// Try to convert the UTF-8 Rupee symbol to Windows-1252 for FPDF's core fonts
// FPDF's core fonts are typically ISO-8859-1 or Windows-1252.
// The Rupee symbol (U+20B9) is NOT in these character sets.
// So, iconv will likely fail to convert it to a single byte representation.
// For robust Rupee symbol display, you'd need a FPDF version/extension with UTF-8 font support (like tFPDF)
// or use TCPDF.
// As a simpler fallback for now, we will use "INR" or "Rs." text if the symbol doesn't render.

// Let's use "INR" or "Rs." if the DB currency is INR.
if ($db_currency_code == 'INR') {
    $display_currency_symbol_fpdf = 'INR '; // Or use 'Rs. '
} elseif ($db_currency_code == 'USD') {
    $display_currency_symbol_fpdf = '$ ';
} else {
    $display_currency_symbol_fpdf = htmlspecialchars($invoice['currency']) . ' '; // Default to DB code
}


// --- Start PDF Generation using FPDF ---
class PDF_Invoice extends FPDF {
    public $invoiceData;
    public $displayCurrencySymbol; // Store the symbol to use

    function setInvoiceData($data, $currencySymbol) {
        $this->invoiceData = $data;
        $this->displayCurrencySymbol = $currencySymbol;
    }

    function Header() {
        $this->SetFont('Arial','B',16);
        $this->Cell(0,10,'MediTrack Invoice',0,1,'C');
        $this->SetFont('Arial','',10);
        $this->Cell(0,6,'Appointment & Billing Statement',0,1,'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-20); // Adjusted position for two lines
        $this->SetFont('Arial','I',8);
        $this->Cell(0,5,'Page '.$this->PageNo().'/{nb}',0,1,'C'); // Use 1 for new line
        $this->Cell(0,5,'Thank you for choosing MediTrack!',0,0,'C');
    }

    function FancyTable($header, $data_items) {
        $this->SetFillColor(230,230,230); 
        $this->SetTextColor(0);
        $this->SetDrawColor(180,180,180); 
        $this->SetLineWidth(.3);
        $this->SetFont('Arial','B',10);
        
        $w = array(100, 25, 30, 35); 
        for($i=0;$i<count($header);$i++)
            $this->Cell($w[$i],7,$header[$i],1,0,'C',true);
        $this->Ln();
        
        $this->SetFont('Arial','',10);
        $this->SetFillColor(245,245,245); 
        $this->SetTextColor(0);
        $fill = false;
        foreach($data_items as $row) {
            // For text cells, try iconv to handle potential special characters in names/descriptions
            $desc_cell = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row[0]);
            if ($desc_cell === false) $desc_cell = $row[0]; // Fallback if iconv fails

            $this->Cell($w[0],6,$desc_cell,'LR',0,'L',$fill); 
            $this->Cell($w[1],6,$row[1],'LR',0,'R',$fill);
            $this->Cell($w[2],6, $this->displayCurrencySymbol . number_format($row[2],2),'LR',0,'R',$fill);
            $this->Cell($w[3],6, $this->displayCurrencySymbol . number_format($row[3],2),'LR',0,'R',$fill);
            $this->Ln();
            $fill = !$fill;
        }
        $this->Cell(array_sum($w),0,'','T');
        $this->Ln(1); 
    }

    function InvoiceDetails() {
        $this->SetFont('Arial','B',11);
        $this->Cell(0,7,'Invoice #: ' . $this->invoiceData['invoice_uid'],0,1);
        $this->SetFont('Arial','',10);
        $this->Cell(0,5,'Date Issued: ' . date("F j, Y", strtotime($this->invoiceData['invoice_created_at'])),0,1);
        $this->Cell(0,5,'Due Date: ' . ($this->invoiceData['due_date'] ? date("F j, Y", strtotime($this->invoiceData['due_date'])) : 'N/A'),0,1);
        $this->Cell(0,5,'Appointment: ' . date("F j, Y", strtotime($this->invoiceData['appointment_date'])) . ' at ' . date("g:i A", strtotime($this->invoiceData['appointment_time'])),0,1);
        $this->Ln(6);

        $this->SetFont('Arial','B',10);
        $this->Cell(95, 6, 'Bill To:', 0, 0, 'L');
        $this->Cell(95, 6, 'From (Clinic/Doctor):', 0, 1, 'L');
        
        $this->SetFont('Arial','',10);
        $this->Cell(95, 5, $this->invoiceData['patient_name'], 0, 0, 'L');
        $this->Cell(95, 5,''. $this->invoiceData['doctor_name'], 0, 1, 'L');
        
        $this->Cell(95, 5, $this->invoiceData['patient_email'], 0, 0, 'L');
        $this->Cell(95, 5, $this->invoiceData['doctor_email'], 0, 1, 'L');
        $this->Ln(8);
    }
}

$pdf = new PDF_Invoice();
$pdf->setInvoiceData($invoice, $display_currency_symbol_fpdf); // Pass data and chosen symbol
$pdf->AliasNbPages(); 
$pdf->AddPage(); 

$pdf->InvoiceDetails(); 

$header_items = array('Service Description', 'Qty', 'Unit Price', 'Amount');
$consultation_description = 'Consultation with ' . $invoice['doctor_name'] . ' (' . $invoice['doctor_specialization'] . ')';
$items_data = array(
    array($consultation_description, '1', $invoice['amount'], $invoice['amount'])
);
$pdf->FancyTable($header_items, $items_data);

$pdf->SetFont('Arial','B',11);
$pdf->Cell(array_sum([100, 25]), 8, '', 0, 0); 
$pdf->Cell(30,8,'Total:',0,0,'R');
$pdf->SetFillColor(230,230,230);
$pdf->Cell(35,8, $display_currency_symbol_fpdf . number_format($invoice['amount'],2),1,1,'R', true); 
$pdf->Ln(5);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,10,'Status: ' . ucfirst($invoice['invoice_status']),0,1,'L');
$pdf->Ln(5);

if (strtolower($invoice['invoice_status']) == 'unpaid') {
    $pdf->SetFont('Arial','',10);
    $pdf->SetTextColor(100);
    $payment_instructions = 'Payment Instructions: Kindly complete the payment by the due date. For payment options or queries, please contact our clinic reception. Please mention Invoice #'.$invoice['invoice_uid'].' with your payment.';
    $pdf->MultiCell(0,5, @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $payment_instructions));
    $pdf->SetTextColor(0);
}

mysqli_close($conn);

$pdf_filename = 'Invoice-' . $invoice['invoice_uid'] . '.pdf';
$pdf->Output('I', $pdf_filename); 
exit;
?>