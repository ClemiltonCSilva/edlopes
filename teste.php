<?php

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

if (class_exists(PHPMailer::class)) {
    echo "✅ PHPMailer está instalado corretamente!";
} else {
    echo "❌ PHPMailer NÃO está instalado!";
}
