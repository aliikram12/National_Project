<?php
require __DIR__ . '/../config/db.php';

$content = '
<div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; font-family: \'Poppins\', sans-serif; border-top: 10px solid #0A2540; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f0f4f8; padding-bottom: 20px; margin-bottom: 30px;">
        <div>
            <h1 style="color: #0A2540; margin: 0; font-size: 28px; font-weight: 700;">NATIONAL COLLEGE</h1>
            <p style="color: #718096; margin: 5px 0 0 0; font-size: 14px;">Excellence in Education & Leadership</p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 12px; color: #718096; text-transform: uppercase; letter-spacing: 1px;">Admission Record</div>
            <div style="font-size: 18px; font-weight: 600; color: #00D2D3; margin-top: 5px;">NC-{enrollment_date}</div>
        </div>
    </div>
    
    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 30px; display: flex; gap: 20px;">
        <div style="width: 120px; height: 120px; background: #e2e8f0; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #a0aec0; font-size: 12px; border: 2px dashed #cbd5e0;">
            Attach Photo
        </div>
        <div style="flex: 1;">
            <h2 style="color: #0A2540; font-size: 20px; margin: 0 0 15px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Student Information</h2>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr><td style="padding: 5px 0; color: #718096; width: 140px;">Full Name</td><td style="font-weight: 600; color: #2D3748;">{student_name}</td></tr>
                <tr><td style="padding: 5px 0; color: #718096;">Father\'s Name</td><td style="font-weight: 600; color: #2D3748;">{father_name}</td></tr>
                <tr><td style="padding: 5px 0; color: #718096;">Date of Birth</td><td style="font-weight: 600; color: #2D3748;">{dob}</td></tr>
                <tr><td style="padding: 5px 0; color: #718096;">Contact Info</td><td style="font-weight: 600; color: #2D3748;">{contact}</td></tr>
                <tr><td style="padding: 5px 0; color: #718096;">Home Address</td><td style="font-weight: 600; color: #2D3748;">{address}</td></tr>
            </table>
        </div>
    </div>
    
    <h2 style="color: #0A2540; font-size: 20px; margin: 0 0 15px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Enrollment Details</h2>
    <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 40px; text-align: left;">
        <thead>
            <tr style="background: #f0f4f8; color: #0A2540;">
                <th style="padding: 12px; border: 1px solid #e2e8f0;">Program / Course</th>
                <th style="padding: 12px; border: 1px solid #e2e8f0;">Duration</th>
                <th style="padding: 12px; border: 1px solid #e2e8f0;">Allocated Time Slot</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding: 12px; border: 1px solid #e2e8f0; font-weight: 600; color: #2D3748;">{course_name}</td>
                <td style="padding: 12px; border: 1px solid #e2e8f0; color: #2D3748;">{duration}</td>
                <td style="padding: 12px; border: 1px solid #e2e8f0; font-weight: 600; color: #00D2D3;">{time_slot}</td>
            </tr>
        </tbody>
    </table>
    
    <div style="display: flex; justify-content: space-between; margin-top: 80px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
        <div style="text-align: center; width: 200px;">
            <div style="border-bottom: 1px solid #2D3748; height: 30px; margin-bottom: 5px;"></div>
            <div style="font-size: 12px; color: #718096;">Applicant Signature</div>
        </div>
        <div style="text-align: center; width: 200px;">
            <div style="border-bottom: 1px solid #2D3748; height: 30px; margin-bottom: 5px;"></div>
            <div style="font-size: 12px; color: #718096;">Authorized Signatory</div>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 40px; font-size: 11px; color: #a0aec0;">
        This document is generated automatically by National College LMS.<br>
        Date of generation: {enrollment_date}
    </div>
</div>
';

$stmt = $pdo->prepare("UPDATE pdf_templates SET content = ? WHERE name = 'admission_form'");
$stmt->execute([$content]);
echo "PDF Template Updated!";
