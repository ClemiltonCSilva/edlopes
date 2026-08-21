<?php
// Nunca exibir erros/avisos do PHP na resposta (evita vazar caminhos do servidor)
ini_set('display_errors', '0');
error_reporting(E_ALL);

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

// Rate limiting por IP (evita flood no endpoint de contato)
$rateLimitWindow = 600; // 10 minutos
$rateLimitMax = 3;
$now = time();
$ipKey = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');

if (!is_dir(__DIR__ . '/storage')) { @mkdir(__DIR__ . '/storage', 0755, true); }

// Lê, verifica e grava dentro de uma única seção travada (flock) para evitar
// que duas requisições simultâneas leiam o mesmo estado antes de gravar.
$rateLimited = true;
$lockHandle = @fopen(__DIR__ . '/storage/rate_limit.json', 'c+');
if ($lockHandle && flock($lockHandle, LOCK_EX)) {
  $raw = stream_get_contents($lockHandle);
  $attempts = $raw ? (json_decode($raw, true) ?: []) : [];

  foreach ($attempts as $key => $timestamps) {
    $attempts[$key] = array_values(array_filter($timestamps, fn($t) => ($now - $t) < $rateLimitWindow));
    if (empty($attempts[$key])) unset($attempts[$key]);
  }

  $rateLimited = count($attempts[$ipKey] ?? []) >= $rateLimitMax;
  if (!$rateLimited) {
    $attempts[$ipKey][] = $now;
  }

  ftruncate($lockHandle, 0);
  rewind($lockHandle);
  fwrite($lockHandle, json_encode($attempts));
  fflush($lockHandle);
  flock($lockHandle, LOCK_UN);
  fclose($lockHandle);
}

if ($rateLimited) {
  http_response_code(429);
  $status = 'error';
  $mensagemFeedback = 'Você atingiu o limite de envios. Aguarde alguns minutos antes de tentar novamente.';
} else {
  // Honeypot para Spambots
  $hp = trim($_POST['empresa'] ?? '');
  if ($hp !== '') { exit('OK'); }

  $nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');
$lgpd = $_POST['lgpd'] ?? '';

// Verificação do Cloudflare Turnstile
$turnstileOk = false;
$turnstileToken = $_POST['cf-turnstile-response'] ?? '';
$turnstileSecret = $_ENV['TURNSTILE_SECRET'] ?? '';
if ($turnstileToken !== '' && $turnstileSecret !== '') {
  $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
      'secret'   => $turnstileSecret,
      'response' => $turnstileToken,
      'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 8,
  ]);
  $turnstileRaw = curl_exec($ch);
  $turnstileData = $turnstileRaw ? json_decode($turnstileRaw, true) : null;
  $turnstileOk = !empty($turnstileData['success']);
}

// Validações básicas
if (!$turnstileOk) {
  http_response_code(400);
  $status = 'error';
  $mensagemFeedback = 'Não foi possível confirmar que você não é um robô. Atualize a página e tente novamente.';
} elseif ($nome === '' || $email === '' || $mensagem === '') {
  http_response_code(400);
  $status = 'error';
  $mensagemFeedback = 'Por favor, preencha todos os campos do formulário.';
} elseif ($lgpd !== '1') {
  http_response_code(400);
  $status = 'error';
  $mensagemFeedback = 'Para enviar a mensagem, é necessário concordar com a Política de Privacidade.';
} elseif (mb_strlen($nome) > 120 || mb_strlen($email) > 120 || mb_strlen($mensagem) > 3000) {
  http_response_code(400);
  $status = 'error';
  $mensagemFeedback = 'Um ou mais campos excedem o tamanho permitido. Revise o texto e tente novamente.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  $status = 'error';
  $mensagemFeedback = 'O e-mail informado não parece ser válido. Verifique e tente novamente.';
} else {
  // Higienização do nome
  $nome = preg_replace("/[\r\n]+/", ' ', $nome);

  $destinoEmail = 'ednave@grupoednave.com.br';
  $destinoNome  = 'Ednave / Edlopes - Contacto';

  $workspaceUser = $_ENV['SMTP_USER'] ?? '';
  $appPassword   = $_ENV['SMTP_PASS'] ?? '';

  if (!$workspaceUser || !$appPassword) {
    http_response_code(500);
    $status = 'error';
    $mensagemFeedback = 'Não foi possível enviar sua mensagem no momento. Por favor, tente mais tarde.';
    error_log('Erro de configuração: SMTP_USER ou SMTP_PASS ausentes no .env');
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
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Contato | Edlopes Transportes</title>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="css/layout.css">
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 24px;
      background: var(--cinza-claro);
    }
    .card {
      position: relative;
      background: var(--branco);
      max-width: 520px;
      width: 100%;
      border-radius: 4px;
      box-shadow: 0 12px 28px rgba(20, 33, 61, 0.12);
      padding: 40px 32px;
      text-align: center;
      overflow: hidden;
    }
    .card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 2px;
      background: linear-gradient(90deg, var(--red-edlopes) 0%, rgba(214, 39, 42, 0) 45%);
    }
    .status-tag {
      font-family: var(--font-mono);
      font-size: 12px;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      margin-bottom: 16px;
    }
    .status-tag.success { color: var(--azul-edlopes); }
    .status-tag.error { color: var(--red-edlopes); }
    h1 {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 12px;
      line-height: 1.25;
      color: var(--azul-edlopes);
    }
    p {
      font-size: 16px;
      color: #4b5568;
      line-height: 1.7;
      margin-bottom: 28px;
    }
    .btn {
      display: inline-block;
      background: var(--azul-edlopes);
      color: var(--branco);
      text-decoration: none;
      padding: 12px 28px;
      border-radius: 4px;
      font-weight: 700;
      transition: 0.25s;
    }
    .btn:hover { background: var(--red-edlopes); }
  </style>
</head>
<body>
  <div class="card">
    <?php if ($status === 'success'): ?>
      <p class="status-tag success">// Envio confirmado</p>
      <h1>Mensagem enviada</h1>
    <?php else: ?>
      <p class="status-tag error">// Falha no envio</p>
      <h1>Não foi possível enviar</h1>
    <?php endif; ?>

    <p><?php echo $mensagemFeedback; ?></p>

    <a href="index.html" class="btn">Voltar para a Home</a>
  </div>
</body>
</html>