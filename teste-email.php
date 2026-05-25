<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'site.ednave@grupoednave.com.br';
$mail->Password = 'pirv msgs imql ezrw';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->setFrom('larissa.alecrim@grupoednave.com.br', 'Teste');
$mail->addAddress('larissa.silva@grupoednave.com.br');

$mail->Subject = 'Teste';
$mail->Body = 'Teste funcionando';

$mail->send();

echo "✅ Email enviado!";

$mail->SMTPDebug = 2;