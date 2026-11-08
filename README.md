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
