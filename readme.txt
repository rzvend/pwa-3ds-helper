=== PWA 3DS Helper ===
Contributors: you
Tags: woocommerce, 3ds, pagbank, pwa, checkout
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 3.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Mantém o checkout do WooCommerce estável em PWA durante 3DS e redireciona automaticamente para "pedido finalizado".

== Descrição ==
- Redireciona para a tela "pedido finalizado" assim que detecta um pedido recente pago (processing/completed/on-hold) do cliente (por usuário logado ou billing_email).
- Se a sessão do WooCommerce cair, tenta re-submeter o checkout após Xs (10s por padrão) quando o app volta ao primeiro plano.
- Logs com anonimização e limpeza automática (a cada 10 min, retém 24h e limita a ~3MB).

== Configuração ==
- Vá em **PWA 3DS Config** para ajustar o tempo de espera.
- Em **Logs** você visualiza e limpa o log.

== Segurança ==
Campos sensíveis (número de cartão, cvc, cpf, senha, token) são mascarados no log.
