=== rc affiliates ===
Contributors: martins56
Tags: woocommerce, afiliados, pagbank, comissÃµes, programa de afiliados
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Transforme clientes e parceiros em uma rede de vendas: programa de afiliados completo para WooCommerce com PagBank â€” links, comissÃµes flexÃ­veis, repasse manual ou split automÃ¡tico e relatÃ³rios que mostram o que funciona.

== Description ==

**rc affiliates** foi feito para lojas que querem crescer com indicaÃ§Ãµes sem planilhas nem gambiarras. VocÃª define as regras; o plugin rastreia cliques, atribui vendas, calcula comissÃµes e dÃ¡ ao afiliado uma Ã¡rea clara na conta â€” enquanto vocÃª mantÃ©m o controle total no painel WordPress.

= Por que escolher este plugin? =

* **Pronto para o Brasil** â€” IntegraÃ§Ã£o com **PagBank Connect** (split ou pagamento manual).
* **FlexÃ­vel** â€” ComissÃ£o por loja, por categoria, por afiliado ou por cupom; percentual ou valor fixo.
* **Transparente** â€” Dashboard, relatÃ³rios e histÃ³rico para afiliados e para a Ã¡rea administrativa.
* **Profissional** â€” Rastreamento com cookie, identificador na URL e opÃ§Ã£o de atribuiÃ§Ã£o por domÃ­nio (referer verificado).

= Crie um time de afiliados e turbine suas vendas =

* Mais conversÃ£o com **links de indicaÃ§Ã£o** fÃ¡ceis de copiar e compartilhar.
* **Montador de links**: o afiliado cola o URL de um produto, categoria ou pÃ¡gina da loja e recebe o link jÃ¡ com o cÃ³digo â€” sem erro de cÃ³pia.
* Encurtamento opcional e gratuito
* **Materiais promocionais**: disponibilize arquivos para download na Ã¡rea do afiliado.
* **GrÃ¡ficos e relatÃ³rios** pra vocÃª e seus afiliados crescerem juntos.
* **RelatÃ³rios avanÃ§ados no admin** (incluindo anÃ¡lise de cliques e desempenho por afiliado).
* **E-mails transacionais** para cadastro, nova comissÃ£o e comissÃ£o paga (via WooCommerce).
* Modo **pagamento manual** (saques, comprovantes) ou **split automÃ¡tico PagBank** conforme a configuraÃ§Ã£o da loja.
* Gere **cupons** com descontos e comissÃµes personalizadas para afiliados
* Defina comissÃµes diferentes **por categoria** e tambÃ©m **por afiliado**

= Requisitos =

* **WooCommerce** ativo  
* **PagBank Connect** ativo com pelo menos um mÃ©todo de pagamento disponÃ­vel  
* **HPOS** (armazenamento de pedidos de alto desempenho) ativado na loja â€” requisito para ativaÃ§Ã£o do plugin

Sem estes itens, o plugin nÃ£o ativa ou nÃ£o opera como esperado â€” Ã© uma escolha deliberada para integraÃ§Ã£o sÃ³lida com pagamentos e pedidos modernos.

== Installation ==

1. Envie a pasta `rc-affiliates` para `wp-content/plugins/`.
2. Confirme que WooCommerce estÃ¡ com **HPOS** habilitado e que o **PagBank Connect** estÃ¡ configurado.
3. Ative **rc affiliates** no painel Plugins.
4. Ajuste **Afiliados â†’ ConfiguraÃ§Ãµes** (comissÃ£o padrÃ£o, cookie, modo de pagamento, etc.).
5. (Opcional) Defina uma pÃ¡gina de termos e a polÃ­tica de cadastro de afiliados.

ApÃ³s ativar, os afiliados passam a ver a Ã¡rea do programa em **Minha conta** (endpoints de afiliado).

== Frequently Asked Questions ==

= Preciso do PagBank mesmo usando sÃ³ pagamento manual? =

Sim. O plugin foi desenhado em torno do ecossistema PagBank Connect; a exigÃªncia garante um caminho Ãºnico de integraÃ§Ã£o e compatibilidade com mÃ©todos de pagamento da loja.

= O afiliado precisa instalar algo? =

NÃ£o. Tudo roda na sua loja: conta do cliente, cookies para rastreamento e scripts compatÃ­veis com cache de pÃ¡gina quando necessÃ¡rio.

= Posso misturar regras de comissÃ£o? =

Sim. HÃ¡ precedÃªncia clara: cupom de afiliado e regra no perfil do usuÃ¡rio podem sobrepor o cÃ¡lculo â€œpor linhaâ€ com categorias e taxa padrÃ£o da loja. Veja a secÃ§Ã£o tÃ©cnica abaixo.

= O split substitui o saque manual? =

No modo split, o repasse segue as regras do conector PagBank; a interface de saques manuais e histÃ³rico de pagamentos refletem o que a loja registra â€” ideal para evitar expectativas erradas de quem sÃ³ recebe via split.

== Regras de cÃ¡lculo de comissÃ£o (referÃªncia tÃ©cnica) ==

A comissÃ£o sÃ³ Ã© calculada para pedidos atribuÃ­dos a um afiliado **ativo** e quando hÃ¡ base de cÃ¡lculo vÃ¡lida.

* **Base de cÃ¡lculo**  
  Ã‰ derivada do subtotal dos itens do pedido, conforme as opÃ§Ãµes em Afiliados â†’ ConfiguraÃ§Ãµes: podem ser descontadas **taxas (fees)** do pedido. O frete nÃ£o entra no subtotal dos itens. A base nunca fica negativa.

* **PrecedÃªncia da regra de taxa (qual percentual ou valor fixo usar)**  
  1. **Cupom de afiliado** â€” Se o pedido tiver cupom ligado ao programa com tipo e valor de comissÃ£o definidos no cupom, usa-se essa regra para **todo o pedido** (uma Ãºnica taxa).  
  2. **Perfil do afiliado** â€” Se o administrador tiver definido comissÃ£o personalizada no utilizador (tipo + valor), usa-se essa regra para **todo o pedido**.  
  3. **Caso contrÃ¡rio** â€” Aplica-se o modo **por linha de item** (ver abaixo), usando a comissÃ£o **padrÃ£o da loja** como valor de referÃªncia quando nenhuma categoria impÃµe regra Ã quele produto.

* **Modo por linha (sem cupom e sem comissÃ£o no perfil do afiliado)**  
  - A base total do pedido Ã© **repartida proporcionalmente** pelo subtotal de cada linha de produto.  
  - Para cada linha, determina-se uma taxa **(percentual ou valor fixo)** a aplicar Ã  **base alocada Ã quela linha**.  
  - O produto considerado para categorias Ã© o **pai** se o item for uma **variaÃ§Ã£o**.  
  - **Categoria de produto:** nas categorias que tiverem comissÃ£o prÃ³pria (Produtos â†’ Categorias â†’ secÃ§Ã£o rc affiliates), recolhem-se todas as regras aplicÃ¡veis ao produto. Se existir mais do que uma categoria com regra, **prevalece a regra que produzir a menor comissÃ£o monetÃ¡ria** para a base **daquela linha** (compara-se percentual e fixo sobre essa base).  
  - Se o produto **nÃ£o** tiver nenhuma categoria com regra personalizada, usa-se a **comissÃ£o padrÃ£o** definida em Afiliados â†’ ConfiguraÃ§Ãµes (tipo + valor).  
  - A comissÃ£o final do pedido Ã© a **soma** das comissÃµes calculadas por linha. Se as linhas nÃ£o partilharem a mesma taxa, o sistema pode registar o tipo como combinaÃ§Ã£o mista e um percentual equivalente sobre a base total (para referÃªncia em relatÃ³rios).

* **Onde configurar**  
  - **PadrÃ£o da loja:** Afiliados â†’ ConfiguraÃ§Ãµes (tipo e valor).  
  - **Por categoria:** editar cada categoria em Produtos â†’ Categorias.  
  - **Por afiliado:** perfil do utilizador no admin (quando existir).  
  - **Por cupom:** metadados do cupom de afiliado no WooCommerce.

== Screenshots ==

1. Ãrea do afiliado no WooCommerce â€” dashboard, mÃ©tricas e ferramentas de divulgaÃ§Ã£o.

== Changelog ==

= 1.0.0 =
* VersÃ£o inicial.


