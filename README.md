PWA 3DS Helper

A WordPress and WooCommerce plugin that keeps the checkout flow stable in Progressive Web Apps (PWAs) during the 3D Secure (3DS) authentication process (e.g., PagBank, Cielo, etc.).
It ensures the customer returns correctly to the checkout and is redirected to the “Order Received” page after authorization.

✨ Features

🔁 Session Recovery: Restores the checkout state even if the customer minimizes the PWA or opens the banking app.

🕒 Auto Retry: Automatically resubmits the order after a configurable delay (default: 10s) if the WooCommerce session expires.

✅ Auto Redirect: Detects when the order is successfully paid (via logged-in user or billing email) and redirects to the “Order Received” page.

🧩 Full Compatibility: Works seamlessly with PWAs and custom WordPress themes.

🧠 Gateway Support: Compatible with PagBank / PagSeguro gateways and most third-party payment plugins.

🧰 Admin Panel:

Set custom waiting time;

View detailed, anonymized logs;

“Clear Log” button for maintenance.

🧭 Plugin Structure
pwa-3ds-helper/
├── assets/
│   ├── pwa3ds.css          # Visual banner: “Waiting for bank confirmation…”
│   └── pwa3ds.js           # Core script of PWA 3DS Helper
├── pwa-3ds-helper.php      # Main plugin file
└── readme.txt              # WordPress.org metadata

⚙️ Installation

Download the plugin ZIP file:
pwa-3ds-helper-3.0.0.zip

In the WordPress admin panel, go to:
Plugins → Add New → Upload Plugin

Activate PWA 3DS Helper.

Go to Settings → PWA 3DS Config to:

Adjust waiting time (default: 10 seconds);

View and clear logs.

🔍 Logs and Debugging

Logs are automatically saved at:

wp-content/uploads/pwa3ds-logs.log

🧩 Requirements
Requirement	Minimum Version
WordPress	6.0
WooCommerce	7.0
PHP	7.4
HTTPS (SSL)	Recommended
# PWA 3DS Helper

......... // ......... 

🧠 PWA 3DS Helper

Plugin para WordPress e WooCommerce que mantém o checkout estável em PWAs durante o fluxo de autenticação 3DS (ex: PagBank, Cielo, etc.).
Garante que o cliente volte corretamente ao checkout e seja redirecionado para a tela de pedido finalizado após a autorização.

✨ Funcionalidades

🔁 Recupera o estado do checkout mesmo após o cliente minimizar o PWA e abrir o app do banco;

🕒 Reenvia automaticamente o pedido após o tempo configurado (padrão: 10 s) se a sessão WooCommerce expirar;

✅ Redireciona automaticamente para a página “Pedido Recebido” assim que o pedido pago é detectado (via usuário logado ou billing_email);

🧩 Compatível com PWAs e temas personalizados;

🧠 Compatível com gateways PagBank / PagSeguro e plugins de terceiros;

🧰 Painel administrativo dedicado:

Configuração do tempo de espera;

Logs detalhados com anonimização;

Botão “Limpar Log”.

🧭 Estrutura do Plugin
pwa-3ds-helper/
├── assets/
│   ├── pwa3ds.css          # Banner visual “Aguardando retorno do banco…”
│   └── pwa3ds.js           # Script principal do PWA 3DS Helper
├── pwa-3ds-helper.php      # Código principal do plugin
└── readme.txt              # Metadados WordPress.org

⚙️ Instalação

Baixe o ZIP do plugin:

pwa-3ds-helper-3.0.0.zip


No painel do WordPress, acesse:
Plugins → Adicionar novo → Enviar plugin

Ative o plugin PWA 3DS Helper.

Vá em Configurações → PWA 3DS Config para:

Ajustar o tempo de espera (padrão: 10 s);

Visualizar e limpar os logs.

🔍 Logs e Depuração

Os logs são gravados automaticamente em:

wp-content/uploads/pwa3ds-logs.log

🧩 Requisitos
Requisito	Versão mínima
WordPress	6.0
WooCommerce	7.0
PHP	7.4
HTTPS (SSL)	Recomendado
