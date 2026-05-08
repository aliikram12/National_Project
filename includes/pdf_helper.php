<?php
require_once __DIR__ . '/../vendor/autoload.php';

class NationalCollegePDF extends TCPDF {
    private $settings;
    
    public function __construct($pdo) {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Fetch settings
        $stmt = $pdo->query("SELECT * FROM pdf_settings WHERE id = 1");
        $this->settings = $stmt->fetch() ?: [
            'college_name' => 'National College',
            'college_logo' => '',
            'college_address' => '123 Education Blvd, Lahore',
            'college_phone' => '+92 300 1234567',
            'college_email' => 'info@nationalcollege.edu',
            'footer_text' => 'System Generated Document',
            'watermark_text' => 'NATIONAL COLLEGE',
            'header_color' => '#0A1628',
            'show_logo' => 1,
            'show_watermark' => 0
        ];
        
        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor($this->settings['college_name']);
        $this->SetTitle('Report - ' . $this->settings['college_name']);
        $this->SetSubject('System Report');
        
        // Margins
        $this->SetMargins(15, 40, 15);
        $this->SetHeaderMargin(10);
        $this->SetFooterMargin(15);
        
        // Auto page break
        $this->SetAutoPageBreak(TRUE, 25);
        
        // Set default font
        $this->SetFont('helvetica', '', 10);
    }
    
    public function getSettings() {
        return $this->settings;
    }

    public function Header() {
        // Header Text
        $this->SetY(12);
        
        // Deep navy blue color
        $this->SetTextColor(10, 22, 40);
        
        $this->SetFont('helvetica', 'B', 22);
        $this->Cell(0, 10, 'NATIONAL COLLEGE OF TECHNOLOGY', 0, 1, 'C');
        
        $this->SetFont('helvetica', '', 11);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 6, 'National Building Near UBL Bank University Road Sargodha', 0, 1, 'C');
        $this->Cell(0, 6, 'ncet.sgd@gmail.com', 0, 1, 'C');
        $this->Cell(0, 6, '0316-7772003 | 0316-7772004', 0, 1, 'C');
        
        // Line break and bottom border
        $this->SetY(42);
        $this->SetDrawColor(10, 22, 40);
        $this->SetLineWidth(0.6);
        $this->Line(15, 39, $this->getPageWidth() - 15, 39);
        $this->SetLineWidth(0.2); // reset line width
        
        // Watermark
        if ($this->settings['show_watermark'] && !empty($this->settings['watermark_text'])) {
            // Store current graphic variables
            $this->StartTransform();
            // Set alpha to semi-transparency
            $this->SetAlpha(0.08);
            $this->SetFont('helvetica', 'B', 50);
            $this->SetTextColor(0, 0, 0);
            
            // Calculate coordinates for center of page
            $width = $this->getPageWidth();
            $height = $this->getPageHeight();
            
            // Rotate and write watermark
            $this->Translate($width/2, $height/2);
            $this->Rotate(45);
            $this->Text(-($this->GetStringWidth($this->settings['watermark_text'])/2), -10, $this->settings['watermark_text']);
            
            // Restore graphic variables
            $this->StopTransform();
            $this->SetAlpha(1);
        }
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, $this->settings['footer_text'] . ' | Generated: ' . date('d M Y, h:i A') . ' | Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}
