# OzzyBox

Site do Centro de Treinamento OzzyBox, em Pouso Alegre, MG. A página apresenta o treino híbrido, os canais da academia e três caminhos de consulta de planos pelo WhatsApp. O agendamento de aula experimental acontece pela Tecnofit.

## Rodar localmente

Na raiz do repositório, execute:

```sh
python -m http.server 8080 --bind 127.0.0.1
```

Abra [http://127.0.0.1:8080/](http://127.0.0.1:8080/). O projeto usa HTML, CSS e JavaScript e pode ser servido diretamente.

## Estrutura

- `index.html` contém a página, os textos e os links.
- `style.css` contém o layout responsivo.
- `site.js` controla o menu móvel, o efeito de aproximação e o compartilhamento da localização.
- `assets/` reúne imagens, ícones, fontes e licenças das fontes.

A página está em prévia e tem `noindex`. Antes de disponibilizar o site ao público, configure a hospedagem e revise a indexação. Valores, condições e matrícula são atendidos pela equipe no WhatsApp.

## Atualização responsiva

- A página começa com uma coluna e amplia as grades de treinos, planos, avaliações e localização conforme o espaço disponível.
- O menu compacto fecha ao navegar e leva o foco à seção escolhida. Sem JavaScript, os links ficam no cabeçalho em fluxo normal.
- Em telas baixas na horizontal, o texto e o mascote dividem a abertura. As imagens da galeria preservam sua proporção.
- Os cartões acomodam texto ampliado. Os números da seção de treino ocupam um espaço próprio ao lado do título, e o cabeçalho deixa de ficar fixo quando ocupa mais de um terço da altura da tela.

### Validação

- Chrome e Edge em telas de 320 a 1920 px, em retrato e paisagem, com e sem JavaScript.
- Cenários de ampliação de texto de 150% e 200%, navegação por teclado e acesso aos planos sem cortes horizontais.
- Fluxos conferidos na prévia local incluem a abertura da agenda Tecnofit, os três links de orçamento pelo WhatsApp, o FAQ e a cópia da localização.
- A validação em Firefox e Safari está pendente. Os motores Firefox e WebKit não iniciaram no ambiente Windows usado para os testes.

## Padrão responsivo para novas funcionalidades

- Comece pela tela estreita e use media queries de largura mínima para ampliar o layout. Baseie as mudanças no espaço disponível. Use consultas de altura e orientação quando a interface precisar de um ajuste em paisagem.
- Use Grid para distribuir seções e cartões, Flexbox com quebra para grupos de ações e imagens com dimensões declaradas e proporção preservada. Mantenha o zoom do navegador disponível.
- Mantenha os controles principais visíveis e operáveis por toque e teclado. Em novos formulários, use rótulos associados, campos que caibam na tela e mensagens próximas aos campos. Preserve as alternativas para ações que dependem de permissões do navegador.
- Antes de publicar, confira 320–390 px, tablet, desktop e orientação paisagem. Teste a navegação, o zoom do navegador e os fluxos principais em Chromium, Firefox e WebKit. Confira também o conteúdo essencial sem JavaScript.
