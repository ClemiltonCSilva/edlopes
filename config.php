<?php
// config.php

return [
  // SMTP (preenche com os dados da tua conta/serviço SMTP na Locaweb)
  'smtp_host' => 'SEU_HOST_SMTP',     // ex.: smtp.seudominio.com.br (confirma no painel Locaweb)
  'smtp_port' => 587,                // comum com STARTTLS [3](https://blog.ricardopoffo.dev/post/formspree/)
  'smtp_user' => 'SEU_USUARIO_SMTP',
  'smtp_pass' => 'SUA_SENHA_SMTP',

  // Remetente (idealmente um e-mail do teu domínio)
  'from_email' => 'nao-responda@seudominio.com.br',
  'from_name'  => 'Edlopes | Site',

  // Para onde queres receber as mensagens
  'to_email'   => 'ednave@grupoednave.com.br',
  'to_name'    => 'Contacto Edlopes',
];¢