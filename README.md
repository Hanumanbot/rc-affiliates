# rc affiliates
![Plugin de Afiliado 100% gratuito para WooCommerce](assets/banner-1544x500.png)
Programa de afiliados para WooCommerce com integraÃ§Ã£o PagBank Connect: links de indicaÃ§Ã£o, comissÃµes flexÃ­veis, pagamentos manuais ou split automÃ¡tico, relatÃ³rios e dashboard para afiliados.

## VisÃ£o geral

O **rc affiliates** foi criado para lojas que querem crescer com indicaÃ§Ãµes sem planilhas e sem processos manuais complexos.  
VocÃª define as regras, e o plugin:

- rastreia cliques e atribui pedidos;
- calcula comissÃµes com regras de precedÃªncia;
- oferece Ã¡rea do afiliado em **Minha conta**;
- exibe materiais de apoio ao afiliado definidos por vocÃª;
- entrega relatÃ³rios administrativos e operacionais.

### Como funciona
1. VocÃª instala e define as regras de comissÃ£o
2. Novos afiliados aderem ao programa e comeÃ§am a divulgar sua loja e seus produtos em seus sites, redes sociais e atravÃ©s de cupons especiais, levando trÃ¡fego para seu e-commerce
3. Ao comprar em sua loja, a comissÃ£o Ã© atribuÃ­da ao afiliado imediatamente de forma 100% automÃ¡tica

![Mais do que apenas comissÃµes](assets/flow.svg)

## Principais recursos

- IntegraÃ§Ã£o com **PagBank Connect** (modo de pagamento manual ou split).
- ComissÃ£o global ou especÃ­fica, por categoria, por afiliado e por cupom.
- Tipos de comissÃ£o: percentual ou valor fixo.
- Link builder para afiliados gerarem URLs prontas para divulgaÃ§Ã£o.
- Materiais promocionais para download na Ã¡rea do afiliado.
- RelatÃ³rios com cliques, pedidos e desempenho por afiliado.
- Fluxo de saque manual com registro de comprovante.
- E-mails transacionais do programa (via WooCommerce).
- Encurtador de URLs personalizadas

## Requisitos

- WordPress 6.2+
- PHP 7.4+
- WooCommerce ativo
- PagBank Connect ativo com ao menos um mÃ©todo de pagamento disponÃ­vel
- HPOS (High-Performance Order Storage) habilitado

## InstalaÃ§Ã£o
1. Baixe o [.zip do plugin](https://github.com/r-martins/rc-affiliates/archive/refs/heads/master.zip).
2. No painel WordPress, clique em **Plugins** -> ** Adicionar Plugin** e depois em ** Enviar Plugin**.
3. Envie o .zip que baixou no passo 1 e clique em **Instalar agora**.
4. Ative o plugin e configure em **Afiliados -> ConfiguraÃ§Ãµes**

## ConfiguraÃ§Ã£o inicial recomendada
Para obter sucesso com seu programa de afiliados:

1. Defina comissÃ£o padrÃ£o (tipo e valor).
2. Configure duraÃ§Ã£o de cookie e parÃ¢metro de tracking.
3. Escolha modo de pagamento:
   - manual (saques e marcaÃ§Ã£o de pagamento manuais), ou
   - split (repasse automÃ¡tico via PagBank Connect).
4. (Opcional) Defina pÃ¡gina de termos e polÃ­tica de cadastro de afiliados.
5. Revise regras por categoria e por afiliado, se necessÃ¡rio.
6. Crie materiais de apoio para seus afiliados (banners, Ã­cones, logotipos, manual da marca, guias de divulgaÃ§Ã£o) e suba eles em **Afiliados** -> **Materiais**.
7. ðŸ† FaÃ§a parcerias estratÃ©gicas com influenciadores atravÃ©s de cupons que dÃªem condiÃ§Ãµes e comissÃµes especiais e diferenciadas. VocÃª pode definir regras de comissÃ£o na ediÃ§Ã£o de cupom em **Marketing** -> **Cupons**.

## Regras de comissÃ£o (resumo)

Ordem de precedÃªncia:

1. Regra de comissÃ£o definida no cupom de afiliado.
2. Regra personalizada no perfil do afiliado.
3. CÃ¡lculo por linha de item usando regra de categoria (quando existir) ou padrÃ£o da loja.

ObservaÃ§Ãµes:

- Em variaÃ§Ãµes, a categoria considerada Ã© a do produto pai.
- Se mÃºltiplas categorias tiverem regra, prevalece a de menor comissÃ£o monetÃ¡ria para a linha.
- A comissÃ£o final Ã© a soma das linhas.

## Screenshots

<details>
  <summary><strong>1) Dashboard do afiliado</strong></summary>
  <br>
  <img src="assets/screenshot-1.png" alt="Dashboard do afiliado">
</details>

<details>
  <summary><strong>2) RelatÃ³rios de desempenho</strong></summary>
  <br>
  <img src="assets/screenshot-2.png" alt="RelatÃ³rios de desempenho">
</details>

<details>
  <summary><strong>3) Cupom especial pro afiliado</strong></summary>
  <br>
  <img src="assets/screenshot-3.png" alt="Cupom especial pro afiliado">
</details>

<details>
  <summary><strong>4) RelatÃ³rios administrativos</strong></summary>
  <br>
  <img src="assets/screenshot-4.png" alt="RelatÃ³rios administrativos">
</details>

<details>
  <summary><strong>5) ConfiguraÃ§Ãµes do plugin de afiliado</strong></summary>
  <br>
  <img src="assets/screenshot-5.png" alt="ConfiguraÃ§Ãµes do plugin de afiliado">
</details>

## FAQ

### Posso usar sem PagBank Connect?

NÃ£o. O plugin depende do ecossistema PagBank Connect, especialmente no modo de pagamento via Split.
<br>
No caso de pagamento manual, ao menos um meio de pagamento PagBank Connect precisa estar ativo.

### Funciona com checkout legado (shortcode)?

Sim para o programa de afiliados em geral (atribuiÃ§Ã£o, comissÃµes, admin e Ã¡rea do afiliado).  
Recursos especÃ­ficos de pagamento e disponibilidade dependem da configuraÃ§Ã£o do WooCommerce/PagBank.

### O afiliado precisa instalar algo?

NÃ£o. Tudo funciona na prÃ³pria loja, na conta do cliente/afiliado. No entanto, ele precisa ter uma conta PagBank para receber pagamentos via split.

## DocumentaÃ§Ã£o

- Central de ajuda: https://ajuda.pbintegracoes.com/hc/pt-br/sections/44721017341069-PB-Afiliados

## LicenÃ§a

GPL-3.0  
https://www.gnu.org/licenses/gpl-3.0.html


