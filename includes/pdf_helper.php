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
        // Logo
        if ($this->settings['show_logo'] && !empty($this->settings['college_logo']) && file_exists(__DIR__ . '/../assets/img/' . $this->settings['college_logo'])) {
            $this->Image(__DIR__ . '/../assets/img/' . $this->settings['college_logo'], 15, 10, 0, 20, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
            $left_margin = 40;
        } else {
            $left_margin = 15;
        }

        // Header Text
        $this->SetY(10);
        
        // Convert hex color to RGB
        list($r, $g, $b) = sscanf($this->settings['header_color'], "#%02x%02x%02x");
        $this->SetTextColor($r, $g, $b);
        
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 8, $this->settings['college_name'], 0, 1, 'C');
        
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, $this->settings['college_address'], 0, 1, 'C');
        $this->Cell(0, 5, $this->settings['college_email'], 0, 1, 'C');
        $this->Cell(0, 5, str_replace(' | ', ' | ', $this->settings['college_phone']), 0, 1, 'C');
        
        // Line break
        $this->SetY(35);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(15, 32, $this->getPageWidth() - 15, 32);
        
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
