<?php
/**
 * SAZUG SRMS — PDF Generation Endpoint
 * Generates: Student Biodata Form, Course Registration Form (CRF), Certificate
 * Uses pure PHP + HTML for PDF generation (no external dependencies)
 */
session_start();
require_once 'SystemCore.php';

$auth = false;
$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    $auth = true;
    $isAdmin = in_array($_SESSION['role'] ?? '', ['Super Administrator','Administrator','Registrar','Department Officer']);
}

// For certificate PDF - allow with cert ID parameter (public verify)
$studentId = (int)($_GET['student_id'] ?? 0);
$certId = (int)($_GET['cert_id'] ?? 0);
$sessionId = (int)($_GET['session_id'] ?? 0);
$type = $_GET['type'] ?? '';

$allowedTypes = ['biodata','crf','certificate'];
if (!in_array($type, $allowedTypes)) {
    die('Invalid PDF type.');
}

$core = new SystemCore();

// For student biodata and CRF, resolve student ID
if ($type === 'biodata' || $type === 'crf') {
    if (!$studentId && $auth && $_SESSION['role'] === 'Student') {
        $me = $core->getStudentByUserId((int)$_SESSION['user_id']);
        $studentId = (int)$me['id'];
    }
    if (!$studentId) die('Student ID required.');
    if (!$auth) die('Authentication required.');
    // Admin can view any student's PDF
    if (!$isAdmin && $_SESSION['role'] === 'Student') {
        $me = $core->getStudentByUserId((int)$_SESSION['user_id']);
        if ((int)$me['id'] !== $studentId) die('Access denied.');
    }
}

if ($type === 'certificate' && $certId) {
    if (!$isAdmin) die('Access denied.');
}

// Fetch data
$data = [];
if ($type === 'biodata') {
    $data = $core->getStudentBiodata($studentId);
} elseif ($type === 'crf') {
    $data = $core->getStudentBiodata($studentId);
    if (!$sessionId) $sessionId = $core->getActiveSessionId();
    $data['courses'] = $core->getRegisteredCoursesForSession($studentId, $sessionId);
    $data['crf_session'] = $core->fetchOne('sessions', $sessionId);
} elseif ($type === 'certificate') {
    $data = $core->getCertificateById($certId);
}

if (empty($data) && $type !== 'crf') {
    die('Data not found.');
}

// Build HTML for PDF
$html = '';
$uniName = "Sa'adu Zungur University, Gadau";
$uniLocation = 'Bauchi State, Nigeria';
$logoUrl = 'data:image/svg+xml,' . urlencode('<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60"><rect width="60" height="60" rx="8" fill="#0f3460"/><text x="30" y="38" text-anchor="middle" font-size="24" font-weight="bold" fill="white">S</text></svg>');

if ($type === 'biodata') {
    $s = $data;
    $passportImg = $s['passport_path'] ? (file_exists(__DIR__.'/'.$s['passport_path']) ? $s['passport_path'] : '') : '';
    $passportHtml = $passportImg 
        ? '<img src="'.htmlspecialchars($passportImg).'" style="width:120px;height:150px;object-fit:cover;border:2px solid #0f3460;border-radius:6px">'
        : '<div style="width:120px;height:150px;border:2px dashed #ccc;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#999;font-size:.7rem">No Photo</div>';

    $html = '
    <div style="text-align:center;margin-bottom:20px">
        <h1 style="margin:0;font-size:18px;color:#0f3460">'.htmlspecialchars($uniName).'</h1>
        <p style="margin:2px 0 0;font-size:12px;color:#555">'.htmlspecialchars($uniLocation).'</p>
        <p style="margin:2px 0 0;font-size:11px;color:#888">Student Biodata Form</p>
        <hr style="border:1px solid #0f3460;margin:8px 0">
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:11px">
        <tr>
            <td colspan="4" style="text-align:center;padding:10px;border:1px solid #ddd">'.$passportHtml.'</td>
        </tr>
        <tr><th colspan="4" style="background:#0f3460;color:#fff;padding:6px;text-align:left;font-size:12px">A. Personal Information</th></tr>
    </table>
    <table style="width:100%;border-collapse:collapse;font-size:11px">
        <tr><td style="width:25%;padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Full Name</td><td style="width:25%;padding:5px;border:1px solid #ddd" colspan="3">'.htmlspecialchars($s['full_name']).'</td></tr>
        <tr><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Date of Birth</td><td style="padding:5px;border:1px solid #ddd">'.(!empty($s['dob'])?date('d M Y',strtotime($s['dob'])):'N/A').'</td><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Gender</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['gender']).'</td></tr>
        <tr><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Place of Birth</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['place_of_birth']??'N/A').'</td><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Nationality</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['nationality']??'Nigerian').'</td></tr>
        <tr><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">State of Origin</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['state']??'N/A').'</td><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">LGA</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['lga']??'N/A').'</td></tr>
        <tr><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Home Town</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['home_town']??'N/A').'</td><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Religion</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['religion']??'N/A').'</td></tr>
        <tr><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Marital Status</td><td style="padding:5px;border:1px solid #ddd" colspan="3">'.htmlspecialchars($s['marital_status']??'N/A').'</td></tr>
        <tr><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Phone Number</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['phone']??'N/A').'</td><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Email</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['student_email']??'N/A').'</td></tr>
        <tr><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Address</td><td style="padding:5px;border:1px solid #ddd" colspan="3">'.htmlspecialchars($s['address']??'N/A').'</td></tr>
    </table>
    <table style="width:100%;border-collapse:collapse;font-size:11px;margin-top:0">
        <tr><th colspan="4" style="background:#0f3460;color:#fff;padding:6px;text-align:left;font-size:12px">B. Academic Information</th></tr>
        <tr><td style="width:25%;padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Admission No.</td><td style="width:25%;padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['admission_number']).'</td><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Matric No.</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['matric_number']??'Not Assigned').'</td></tr>
        <tr><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Faculty</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['faculty_name']).'</td><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Department</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['department_name']).'</td></tr>
        <tr><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Programme</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['programme_name']).' ('.htmlspecialchars($s['programme_type']).')</td><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Current Level</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['level']).'</td></tr>
        <tr><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Admission Date</td><td style="padding:5px;border:1px solid #ddd">'.(!empty($s['admission_date'])?date('d M Y',strtotime($s['admission_date'])):'N/A').'</td><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Session</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['session_name']).'</td></tr>
        <tr><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Duration</td><td style="padding:5px;border:1px solid #ddd" colspan="3">'.htmlspecialchars($s['duration_years'].' years').'</td></tr>
    </table>
    <table style="width:100%;border-collapse:collapse;font-size:11px;margin-top:0">
        <tr><th colspan="4" style="background:#0f3460;color:#fff;padding:6px;text-align:left;font-size:12px">C. Guardian / Next of Kin</th></tr>
        <tr><td style="width:25%;padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Guardian Name</td><td style="width:25%;padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['guardian_name']??'N/A').'</td><td style="padding:5px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Guardian Phone</td><td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($s['guardian_phone']??'N/A').'</td></tr>
    </table>
    <div style="margin-top:30px;font-size:10px">
        <table style="width:100%"><tr>
        <td style="width:50%"><div style="border-top:1px solid #333;width:180px;padding-top:4px">Student Signature</div></td>
        <td style="width:50%"><div style="border-top:1px solid #333;width:180px;padding-top:4px">Date: _______________</div></td>
        </tr></table>
        <br><table style="width:100%"><tr>
        <td style="width:50%"><div style="border-top:1px solid #333;width:180px;padding-top:4px">HOD Signature</div></td>
        <td style="width:50%"><div style="border-top:1px solid #333;width:180px;padding-top:4px">Dean Signature</div></td>
        </tr></table>
    </div>';

} elseif ($type === 'crf') {
    $s = $data;
    $courses = $s['courses'] ?? [];
    $sesInfo = $s['crf_session'] ?? [];
    $totalCU = 0;
    foreach ($courses as $c) $totalCU += (int)$c['credit_units'];

    $html = '
    <div style="text-align:center;margin-bottom:15px">
        <h1 style="margin:0;font-size:18px;color:#0f3460">'.htmlspecialchars($uniName).'</h1>
        <p style="margin:2px 0 0;font-size:12px;color:#555">'.htmlspecialchars($uniLocation).'</p>
        <p style="margin:4px 0 0;font-size:13px;font-weight:700">COURSE REGISTRATION FORM (CRF)</p>
        <p style="margin:2px 0 0;font-size:11px;color:#555">'.htmlspecialchars(($sesInfo['name']??''). ' ' . ($sesInfo['semester']??'') . ' Semester').'</p>
        <hr style="border:1px solid #0f3460;margin:8px 0">
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:11px;margin-bottom:15px">
        <tr><td style="padding:4px 8px;border:1px solid #ddd;background:#f8f9fa;font-weight:600;width:20%">Name</td><td style="padding:4px 8px;border:1px solid #ddd">'.htmlspecialchars($s['full_name']).'</td><td style="padding:4px 8px;border:1px solid #ddd;background:#f8f9fa;font-weight:600;width:18%">Matric No.</td><td style="padding:4px 8px;border:1px solid #ddd">'.htmlspecialchars($s['matric_number']??'N/A').'</td></tr>
        <tr><td style="padding:4px 8px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Faculty</td><td style="padding:4px 8px;border:1px solid #ddd">'.htmlspecialchars($s['faculty_name']).'</td><td style="padding:4px 8px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Department</td><td style="padding:4px 8px;border:1px solid #ddd">'.htmlspecialchars($s['department_name']).'</td></tr>
        <tr><td style="padding:4px 8px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Programme</td><td style="padding:4px 8px;border:1px solid #ddd">'.htmlspecialchars($s['programme_name'].' ('.$s['programme_type'].')').'</td><td style="padding:4px 8px;border:1px solid #ddd;background:#f8f9fa;font-weight:600">Level</td><td style="padding:4px 8px;border:1px solid #ddd">'.htmlspecialchars($s['level']).'</td></tr>
    </table>
    <table style="width:100%;border-collapse:collapse;font-size:11px">
        <tr style="background:#0f3460;color:#fff">
            <th style="padding:6px;border:1px solid #0f3460;width:8%">S/N</th>
            <th style="padding:6px;border:1px solid #0f3460;width:15%">Course Code</th>
            <th style="padding:6px;border:1px solid #0f3460;text-align:left">Course Title</th>
            <th style="padding:6px;border:1px solid #0f3460;width:10%">Credit Units</th>
            <th style="padding:6px;border:1px solid #0f3460;width:12%">Semester</th>
            <th style="padding:6px;border:1px solid #0f3460;width:12%">Status</th>
        </tr>';
    if (empty($courses)) {
        $html .= '<tr><td colspan="6" style="padding:20px;text-align:center;border:1px solid #ddd;color:#999">No courses registered for this session.</td></tr>';
    } else {
        $sn = 1;
        foreach ($courses as $c) {
            $semLabel = $c['semester'] === 'First' ? '1st' : '2nd';
            $compLabel = $c['is_compulsory'] ? 'Compulsory' : 'Elective';
            $html .= '
            <tr>
                <td style="padding:5px;border:1px solid #ddd;text-align:center">'.$sn++.'</td>
                <td style="padding:5px;border:1px solid #ddd;font-weight:600">'.htmlspecialchars($c['code']).'</td>
                <td style="padding:5px;border:1px solid #ddd">'.htmlspecialchars($c['title']).'</td>
                <td style="padding:5px;border:1px solid #ddd;text-align:center">'.(int)$c['credit_units'].'</td>
                <td style="padding:5px;border:1px solid #ddd;text-align:center">'.$semLabel.'</td>
                <td style="padding:5px;border:1px solid #ddd;text-align:center">'.$compLabel.'</td>
            </tr>';
        }
        $html .= '
        <tr style="font-weight:700;background:#f0f4f8"><td colspan="3" style="padding:6px;border:1px solid #ddd;text-align:right">Total Credit Units:</td><td style="padding:6px;border:1px solid #ddd;text-align:center">'.$totalCU.'</td><td colspan="2" style="padding:6px;border:1px solid #ddd"></td></tr>';
    }
    $html .= '</table>
    <div style="margin-top:25px;font-size:10px">
        <table style="width:100%"><tr>
        <td style="width:50%"><div style="border-top:1px solid #333;width:180px;padding-top:4px">Student Signature &amp; Date</div></td>
        <td style="width:50%"><div style="border-top:1px solid #333;width:180px;padding-top:4px">Academic Adviser</div></td>
        </tr></table>
        <br><table style="width:100%"><tr>
        <td style="width:50%"><div style="border-top:1px solid #333;width:180px;padding-top:4px">HOD Signature &amp; Date</div></td>
        <td style="width:50%"><div style="border-top:1px solid #333;width:180px;padding-top:4px">Dean Signature &amp; Date</div></td>
        </tr></table>
    </div>';

} elseif ($type === 'certificate') {
    $c = $data;
    if (!$c) die('Certificate not found.');
    $passportHtml = '';
    if (!empty($c['passport_path']) && file_exists(__DIR__.'/'.$c['passport_path'])) {
        $passportHtml = '<img src="'.htmlspecialchars($c['passport_path']).'" style="width:100px;height:125px;object-fit:cover;border:2px solid #0f3460;border-radius:4px">';
    }
    $html = '
    <div style="border:3px solid #0f3460;padding:40px;text-align:center;min-height:700px;display:flex;flex-direction:column;align-items:center;justify-content:center;position:relative">
        <div style="position:absolute;top:20px;left:20px">'.$logoUrl.'</div>
        <div style="position:absolute;top:20px;right:20px;font-size:9px;color:#666">Ref: '.htmlspecialchars($c['certificate_number']).'</div>
        <div style="margin-bottom:5px"><h2 style="margin:0;color:#0f3460;font-size:16px">'.htmlspecialchars($uniName).'</h2></div>
        <p style="margin:0;font-size:11px;color:#555">'.htmlspecialchars($uniLocation).'</p>
        <hr style="width:60%;border:1px solid #0f3460;margin:15px auto">
        <p style="margin:5px 0;font-size:20px;font-weight:800;color:#0f3460;text-transform:uppercase;letter-spacing:3px">Certificate</p>
        <p style="margin:3px 0 0;font-size:10px;color:#666">of '.htmlspecialchars(ucfirst($c['programme_type'])).' Degree</p>
        <div style="margin:25px 0">'.$passportHtml.'</div>
        <p style="margin:0;font-size:11px;color:#555">This is to certify that</p>
        <h1 style="margin:8px 0;font-size:22px;color:#0f3460">'.htmlspecialchars($c['full_name']).'</h1>
        <p style="margin:0;font-size:11px;color:#555">having successfully completed the requirements for the</p>
        <p style="margin:5px 0;font-size:13px;font-weight:700;color:#0f3460">'.htmlspecialchars($c['programme_name'].' ('.$c['programme_type'].')').'</p>
        <p style="margin:0;font-size:11px;color:#555">in the Department of '.htmlspecialchars($c['department_name']).'</p>
        <p style="margin:0;font-size:11px;color:#555">Faculty of '.htmlspecialchars($c['faculty_name']).'</p>
        <p style="margin:15px 0 0;font-size:10px;color:#555">Matriculation Number: <strong>'.htmlspecialchars($c['matric_number']??'N/A').'</strong></p>
        <p style="margin:3px 0 0;font-size:10px;color:#555">Admission Number: <strong>'.htmlspecialchars($c['admission_number']).'</strong></p>
        '.(!empty($c['graduation_date']) ? '<p style="margin:3px 0 0;font-size:10px;color:#555">Date of Graduation: <strong>'.date('d M Y', strtotime($c['graduation_date'])).'</strong></p>' : '').'
        '.(!empty($c['issue_date']) ? '<p style="margin:3px 0 0;font-size:10px;color:#555">Date of Issue: <strong>'.date('d M Y', strtotime($c['issue_date'])).'</strong></p>' : '').'
        <div style="margin-top:30px;width:100%">
        <table style="width:100%;font-size:10px"><tr>
        <td style="width:33%;text-align:center"><div style="border-top:1px solid #333;width:140px;margin:0 auto;padding-top:4px">Registrar</div></td>
        <td style="width:33%;text-align:center"><div style="border-top:1px solid #333;width:140px;margin:0 auto;padding-top:4px">Dean</div></td>
        <td style="width:33%;text-align:center"><div style="border-top:1px solid #333;width:140px;margin:0 auto;padding-top:4px">Vice Chancellor</div></td>
        </tr></table>
        </div>
        <p style="margin-top:20px;font-size:8px;color:#999">Certificate No: '.htmlspecialchars($c['certificate_number']).'</p>
    </div>';
}

// Output HTML that can be printed to PDF via browser
$filename = match($type) {
    'biodata' => 'Student_Biodata_Form',
    'crf' => 'Course_Registration_Form',
    'certificate' => 'Graduation_Certificate',
};
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= $filename ?></title>
<style>
@page{margin:15mm}
body{font-family:'Times New Roman',Georgia,serif;margin:0;padding:15px;color:#1a1a1a}
@media print{body{padding:0} .no-print{display:none!important}}
.no-print{margin-bottom:15px;text-align:center}
.no-print button{padding:10px 30px;background:#0f3460;color:#fff;border:none;border-radius:8px;font-size:14px;cursor:pointer;margin:0 5px}
.no-print button:hover{background:#1d4ed8}
</style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()"><i class="fas fa-print"></i> Print / Save as PDF</button>
    <button onclick="window.close()"><i class="fas fa-times"></i> Close</button>
</div>
<?= $html ?>
</body>
</html>