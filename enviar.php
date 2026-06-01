<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

header('Content-Type: text/html; charset=UTF-8');

// Configurações e Variáveis de Controle de Resposta
$status = ''; // 'success' ou 'error'
$mensagemFeedback = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método não permitido.');
}

// Honeypot para Spambots
$hp = trim($_POST['empresa'] ?? '');
if ($hp !== '') { exit('OK'); }

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');

// Validações básicas
if ($nome === '' || $email === '' || $mensagem === '') {
  http_response_code(400);
  $status = 'error';
  $mensagemFeedback = 'Por favor, preencha todos os campos do formulário.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  $status = 'error';
  $mensagemFeedback = 'O e-mail informado não parece ser válido. Verifique e tente novamente.';
} else {
  // Higienização do nome
  $nome = preg_replace("/[\r\n]+/", ' ', $nome);

  $destinoEmail = 'clemiltonsilva@gmail.com';
  $destinoNome  = 'Ednave / Edlopes - Contacto';

  $workspaceUser = $_ENV['SMTP_USER'] ?? '';
  $appPassword   = $_ENV['SMTP_PASS'] ?? '';

  if (!$workspaceUser || !$appPassword) {
    http_response_code(500);
    $status = 'error';
    $mensagemFeedback = 'Erro interno: As credenciais de envio não foram configuradas.';
  } else {
    $assunto = 'Novo contato do site (Edlopes)';

    // Corpo do Email (HTML)
    $bodyHtml = "<h2>Novo contato do site do Grupo Ednave / Edlopes</h2>";
    $bodyHtml .= "<p><strong>Nome:</strong> " . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . "</p>";
    $bodyHtml .= "<p><strong>Email:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p>";
    $bodyHtml .= "<p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8')) . "</p>";

    // Corpo do Email (Texto Puro)
    $bodyTxt = "Novo contato do site\n\n";
    $bodyTxt .= "Nome: " . $nome . "\n";
    $bodyTxt .= "Email: " . $email . "\n\n";
    $bodyTxt .= "Mensagem:\n" . $mensagem . "\n";

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
      $status = 'success';
      $mensagemFeedback = "Obrigado, <strong>" . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . "</strong>! Sua mensagem foi enviada com sucesso e em breve entraremos em contato.";

    } catch (Exception $e) {
      http_response_code(500);
      $status = 'error';
      $mensagemFeedback = "Não foi possível enviar sua mensagem no momento. Por favor, tente mais tarde.";
      
      // Logs de erro em background
      error_log("SMTP Error: " . $mail->ErrorInfo);
      error_log("Exception: " . $e->getMessage());
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contato - Grupo Ednave / Edlopes</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f4f6f9;
      color: #333;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    .card {
      background: #ffffff;
      max-width: 500px;
      width: 100%;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
      padding: 40px 30px;
      text-align: center;
    }
    .icon {
      font-size: 50px;
      margin-bottom: 20px;
      display: inline-block;
    }
    .icon.success { color: #2ecc71; }
    .icon.error { color: #e74c3c; }
    h1 {
      font-size: 24px;
      margin-bottom: 15px;
      color: #2c3e50;
    }
    p {
      font-size: 16px;
      color: #666;
      line-height: 1.6;
      margin-bottom: 30px;
    }
    .btn {
      display: inline-block;
      background-color: #3498db; /* Cor padrão azul, altere para a identidade da marca */
      color: #ffffff;
      text-decoration: none;
      padding: 12px 30px;
      border-radius: 6px;
      font-weight: 600;
      transition: background-color 0.2s ease, transform 0.1s ease;
    }
    .btn:hover {
      background-color: #2980b9;
    }
    .btn:active {
      transform: scale(0.98);
    }
  </style>
</head>
<body>

  <div class="card">
    <?php if ($status === 'success'): ?>
      <div class="icon success">✓</div>
      <h1>Mensagem Enviada!</h1>
    <?php else: ?>
      <div class="icon error">✕</div>
      <h1>Ops, algo deu errado</h1>
    <?php endif; ?>

    <p><?php echo $mensagemFeedback; ?></p>

    <a href="/" class="btn">Voltar para a Home</a>
  </div>

</body>
</html>