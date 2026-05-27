<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

header('Content-Type: text/plain; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método não permitido.');
}

// Honeypot
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

// Evitar header injection
$nome = preg_replace("/[\r\n]+/", ' ', $nome);

// Rate limit simples por IP
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$key = sys_get_temp_dir() . '/cf_' . md5($ip);
$now = time();
$last = is_file($key) ? (int)file_get_contents($key) : 0;

if ($now - $last < 15) {
  http_response_code(429);
  exit('Muitas tentativas. Tente novamente em instantes.');
}
file_put_contents($key, (string)$now);

$destinoEmail = 'larissa.alecrim@grupoednave.com.br';
$destinoNome  = 'Ednave / Edlopes - Contacto';

$workspaceUser = $_ENV['SMTP_USER'] ?? '';
$appPassword   = $_ENV['SMTP_PASS'] ?? '';

if (!$workspaceUser || !$appPassword) {
  http_response_code(500);
  exit('Erro: Credenciais SMTP não configuradas.');
}

$assunto = 'Novo contacto do site (Edlopes)';

$bodyHtml  = "<h2>Novo contacto do site</h2>";
$bodyHtml .= "<p><strong>Nome:</strong> " . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . "</p>";
$bodyHtml .= "<p><strong>Email:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p>";
$bodyHtml .= "<p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8')) . "</p>";

$bodyTxt  = "Novo contacto do site\n\n";
$bodyTxt .= "Nome: " . $nome . "\n";
$bodyTxt .= "Email: " . $email . "\n\n";
$bodyTxt .= "Mensagem:\n" . $mensagem . "\n";

$mail = new PHPMailer(true);

try {
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = $workspaceUser; // ✅ corrigido
  $mail->Password = $appPassword;
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = 587;
  $mail->CharSet = 'UTF-8';

  $mail->Timeout = 10;
  $mail->SMTPDebug = 0;
  $mail->Debugoutput = 'error_log';

  $mail->setFrom($workspaceUser, 'Formulário do Site');
  $mail->addReplyTo($email, $nome);
  $mail->addAddress($destinoEmail, $destinoNome);

  $mail->isHTML(true);
  $mail->Subject = $assunto;
  $mail->Body    = $bodyHtml;
  $mail->AltBody = $bodyTxt;

  $mail->send();
  echo "Mensagem enviada com sucesso! Obrigado, " . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . ".";

} catch (Exception $e) {
  http_response_code(500);
  echo "Não foi possível enviar a mensagem neste momento. Tente novamente mais tarde.";
  error_log("SMTP Error: " . $mail->ErrorInfo);
  error_log("Exception: " . $e->getMessage());
}