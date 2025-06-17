<?php

$config = require __DIR__ . '../../../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

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
    $driving = !empty($data["driving"]);
    $history = !empty($data["history"]);

    $drivingStatus = $driving ? '<span style="color: green;">&#10004;</span>' : '<span style="color: red;">&#10008;</span>';
    $historyStatus = $history ? '<span style="color: green;">&#10004;</span>' : '<span style="color: red;">&#10008;</span>';

    // Завантаження шаблону
    $body = file_get_contents("template_mail.html");
    if ($body === false) {
        echo json_encode(["message" => "Помилка: Не вдалося прочитати шаблон email."]);
        exit;
    }

    $body = str_replace('%name%', $name, $body);
    $body = str_replace('%phone%', $phone, $body);
    $body = str_replace('%driving%', $drivingStatus, $body);
    $body = str_replace('%history%', $historyStatus, $body);

    // Адресат і тема
//    $mail->addAddress("eijjeka@gmail.com");
    $mail->addAddress("avtoshkoladriver.tet@gmail.com");
    $mail->addAddress("solodovnik.denis123@gmail.com");
//    $mail->addAddress("kashirin.alexsandr91@gmail.com");
    $mail->Subject = "[Заявка з форми]";
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