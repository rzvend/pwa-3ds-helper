# PWA 3DS Helper

Plugin para WordPress e WooCommerce que mantém o **checkout estável em PWAs** durante o fluxo de autenticação **3DS (ex: PagBank, Cielo, etc.)**, garantindo que o cliente volte ao checkout corretamente e seja redirecionado para a tela de **pedido finalizado** após a autorização.

---

## ✨ Funcionalidades

- 🔁 **Recupera o estado do checkout** mesmo após o cliente minimizar o PWA e abrir o app do banco;
- 🕒 **Reenvia automaticamente o pedido** após o tempo configurado (padrão: 10 s) se a sessão WooCommerce expirar;
- ✅ **Redireciona automaticamente** para a página “**Pedido Recebido**” assim que o pedido pago é detectado (via usuário logado ou `billing_email`);
- 🧩 **Compatível com PWAs e temas personalizados**;
- 🧠 **Compatível com gateways PagBank / PagSeguro e plugins de terceiros**;
- 🧰 **Painel administrativo dedicado**:
  - Configuração do tempo de espera;
  - Logs detalhados com anonimização;
  - Botão “Limpar Log”.

---

## 🧭 Estrutura

pwa-3ds-helper/
├── assets/
│ ├── pwa3ds.css # Banner visual “Aguardando retorno do banco…”
│ └── pwa3ds.js # Script principal do PWA 3DS Helper
├── pwa-3ds-helper.php # Código principal do plugin
└── readme.txt # Metadados WordPress.org

## ⚙️ Instalação

1. Baixe o ZIP do plugin:
   
   pwa-3ds-helper-3.0.0.zip

No WordPress admin, vá em Plugins → Adicionar novo → Enviar plugin.

Ative o plugin PWA 3DS Helper.

Acesse Configurações → PWA 3DS Config para:

Ajustar o tempo de espera (padrão 10 s);

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

