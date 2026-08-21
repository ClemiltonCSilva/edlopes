<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "1. PHP version: " . PHP_VERSION . "\n";

echo "2. Tentando carregar vendor/autoload.php...\n";
require __DIR__ . '/vendor/autoload.php';
echo "   OK - autoload carregado\n";

echo "3. Tentando carregar .env...\n";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
echo "   OK - .env carregado\n";

echo "4. Variaveis de ambiente presentes:\n";
echo "   SMTP_USER: " . (!empty($_ENV['SMTP_USER']) ? 'preenchido' : 'VAZIO/AUSENTE') . "\n";
echo "   SMTP_PASS: " . (!empty($_ENV['SMTP_PASS']) ? 'preenchido' : 'VAZIO/AUSENTE') . "\n";
echo "   TURNSTILE_SECRET: " . (!empty($_ENV['TURNSTILE_SECRET']) ? 'preenchido' : 'VAZIO/AUSENTE') . "\n";

echo "5. Testando pasta storage/ (gravavel?)...\n";
$testFile = __DIR__ . '/storage/diag_test.txt';
$ok = @file_put_contents($testFile, 'test');
echo "   " . ($ok !== false ? 'OK - gravavel' : 'FALHOU - sem permissao de escrita') . "\n";
if ($ok !== false) @unlink($testFile);

echo "6. Extensoes PHP carregadas: ";
echo implode(', ', array_intersect(['curl','mbstring','iconv','ctype','pcre'], get_loaded_extensions())) . "\n";

echo "\nDIAGNOSTICO CONCLUIDO SEM ERRO FATAL.\n";
