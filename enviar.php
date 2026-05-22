<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método não permitido.');
}

$hp = trim($_POST['empresa'] ?? '');
if ($hp !== '') { exit('OK'); }

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');

if ($nome === '' || $email === '' || $mensagem === '') {
  http_response_code(400);
  exit('Por favor, preencha todos os campos.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  exit('Email inválido.');
}

$nome = preg_replace("/[\r\n]+/", ' ', $nome);

$destinoEmail = 'ednave@grupoednave.com.br';
$destinoNome  = 'Ednave / Edlopes - Contacto';

$workspaceUser = 'ednave@grupoednave.com.br';
$appPassword   = 'SENHA_DE_APP_16_DIGITOS';

$assunto = 'Novo contacto do site (Edlopes)';

$bodyHtml = "
  <h2>Novo contacto do site</h2>
  <p><strong>Nome:</strong> " . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . "</p>
  <p><strong>Email:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p>
  <p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8')) . "</p>
";

$bodyTxt = "Novo contacto do site\n\n"
  . "Nome: {$nome}\n"
  . "Email: {$email}\n\n"
  . "Mensagem:\n{$mensagem}\n";

require __DIR__ . '/vendor/autoload.php';

$mail = new PHPMailer(true);

try {
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = $workspaceUser;
  $mail->Password = $appPassword;

  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = 587;

  $mail->CharSet = 'UTF-8';

  $mail->setFrom($workspaceUser, 'Formulário do Site');
  $mail->addReplyTo($email, $nome);

  $mail->addAddress($destinoEmail, $destinoNome);

  $mail->isHTML(true);
  $mail->Subject = $assunto;
  $mail->Body    = $bodyHtml;
  $mail->AltBody = $bodyTxt;

  $mail->send();
  echo "Mensagem enviada com sucesso! Obrigado, {$nome}.";

} catch (Exception $e) {
  http_response_code(500);
  echo "Não foi possível enviar a mensagem neste momento.";
  error_log("Mailer Error: " . $mail->ErrorInfo);
}