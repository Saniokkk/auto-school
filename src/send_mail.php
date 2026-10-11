<?php

$config = require __DIR__ . '/../../../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

// Довідник філіалів — звідси беремо назву філіалу для теми/тіла листа.
// Значення мають відповідати src/data/branches.json (id → назва).
$branches = [
    'main' => 'Тетіїв',
    'skvyra' => 'Сквира',
];

$mail = new PHPMailer(true);

try {
    $mail->CharSet = "UTF-8";
    $mail->isHTML(true);

    // Налаштування SMTP
    $mail->isSMTP();
    $mail->Host = 'mail.adm.tools';
    $mail->SMTPAuth = true;
    $mail->Username = $config['mail_username'];
    $mail->Password = $config['mail_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // або PHPMailer::ENCRYPTION_STARTTLS
    $mail->Port = 465; // або 587 для TLS

    // Відправник
    $mail->setFrom('info@avtoshkoladriver.com.ua', 'Автошкола Driver');

    // Обробка JSON-даних
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        echo json_encode(["message" => "Помилка: Некоректний JSON"]);
        exit;
    }

    $name = $data["name"] ?? "";
    $phone = $data["phone"] ?? "";

    // branch приходить з фронтенду (data-branch на <body>), але довіряти йому
    // напряму не можна — звіряємо з довідником і відкочуємось на 'main'.
    $branchId = $data["branch"] ?? "main";
    if (!array_key_exists($branchId, $branches)) {
        $branchId = "main";
    }
    $branchLabel = $branches[$branchId];

    // Завантаження шаблону
    $body = file_get_contents(__DIR__ . "/template_mail.html");
    if ($body === false) {
        echo json_encode(["message" => "Помилка: Не вдалося прочитати шаблон email."]);
        exit;
    }

    $body = str_replace('%name%', $name, $body);
    $body = str_replace('%phone%', $phone, $body);
    $body = str_replace('%branch%', $branchLabel, $body);

    // Адресат і тема
//    $mail->addAddress("eijjeka@gmail.com");
    $mail->addAddress("avtoshkoladriver.tet@gmail.com");
    $mail->addAddress("solodovnik.denis123@gmail.com");
//    $mail->addAddress("kashirin.alexsandr91@gmail.com");
    $mail->Subject = "[Заявка з форми — {$branchLabel}]";
    $mail->MsgHTML($body);

    // Надсилання
    $mail->send();
    $message = "Заявка успішно відправлена, очікуйте дзвінка";
} catch (Exception $e) {
    $message = "Виникла помилка: " . $mail->ErrorInfo;
}

// Відповідь у форматі JSON
header('Content-type: application/json');
echo json_encode(["message" => $message]);