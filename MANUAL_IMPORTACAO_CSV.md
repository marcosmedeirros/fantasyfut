# Sistema de Importa��o de Jogadores do Draft via CSV

## Como Usar

### 1. Acesse a p�gina de importa��o
Acesse: `https://seusite.com/import-draft-players.php`

Ou atrav�s da p�gina de **Temporadas** ? se��o **Gerenciar Draft** ? bot�o **Importar CSV**

> ?? **Apenas administradores** t�m acesso a esta p�gina

### 2. Formato do arquivo CSV

O arquivo CSV deve ter **exatamente** estas colunas na primeira linha:

```csv
nome,posicao,idade,ovr
```

Voc� tamb�m pode usar os nomes em ingl�s:

```csv
name,position,age,ovr
```

### 3. Exemplo de CSV

```csv
nome,posicao,idade,ovr
LeBron James,SF,39,96
Stephen Curry,PG,35,95
Kevin Durant,PF,35,94
Giannis Antetokounmpo,PF,29,97
Nikola Jokic,C,29,98
```

### 4. Valida��es

O sistema valida automaticamente:

- ? **Nome**: Obrigat�rio, n�o pode ser vazio
- ? **Posi��o**: Obrigat�ria (PG, SG, SF, PF, C, etc.)
- ? **Idade**: Deve estar entre 18 e 50 anos
- ? **OVR**: Deve estar entre 40 e 99

### 5. Passo a Passo

1. **Selecione a Liga**: ELITE, NEXT, RISE ou ROOKIE
2. **Escolha a Temporada**: O sistema listar� todas as temporadas dispon�veis da liga selecionada
3. **Clique em "Confirmar Temporada"**: Verifique se selecionou a temporada correta
4. **Escolha o arquivo CSV**: Selecione seu arquivo .csv preparado
5. **Clique em "Importar Jogadores"**: Os jogadores ser�o adicionados ao draft pool da temporada

**Importante:** Os jogadores s�o importados para a lista de "Jogadores do Draft" da temporada selecionada, e ficam dispon�veis para sele��o durante o draft.

### 6. Template CSV

Na p�gina de importa��o h� um bot�o **"Baixar Template CSV"** que fornece um arquivo de exemplo pronto para usar.

## Criando CSV no Excel

### Op��o 1: Salvar como CSV

1. Crie uma planilha com as colunas: `nome`, `posicao`, `idade`, `ovr`
2. Preencha os dados dos jogadores
3. Clique em **Arquivo ? Salvar Como**
4. Escolha o formato **CSV (separado por v�rgulas) (*.csv)**
5. Salve o arquivo

### Op��o 2: Google Sheets

1. Crie uma planilha no Google Sheets
2. Preencha com as colunas e dados
3. Clique em **Arquivo ? Fazer download ? Valores separados por v�rgula (.csv)**

## Exemplo Completo

```csv
nome,posicao,idade,ovr
LeBron James,SF,39,96
Stephen Curry,PG,35,95
Kevin Durant,PF,35,94
Giannis Antetokounmpo,PF,29,97
Nikola Jokic,C,29,98
Joel Embiid,C,30,96
Luka Doncic,PG,25,97
Jayson Tatum,SF,26,95
Shai Gilgeous-Alexander,PG,26,94
Anthony Davis,PF,31,94
```

## Mensagens de Erro Comuns

### "Linha X: Nome � obrigat�rio"
- H� uma linha com o campo nome vazio
- Verifique se todas as linhas t�m nome preenchido

### "Linha X: Idade inv�lida"
- A idade est� fora do intervalo 18-50
- Verifique se digitou a idade corretamente

### "Linha X: OVR inv�lido"
- O OVR est� fora do intervalo 40-99
- Verifique se digitou o overall corretamente

### "Nenhum jogador v�lido encontrado"
- O arquivo est� vazio ou s� tem cabe�alho
- Adicione pelo menos um jogador

## Dicas

? Use o template fornecido para evitar erros de formato
? N�o use acentos nas colunas do cabe�alho
? Certifique-se de que n�o h� linhas vazias entre os dados
? Verifique se salvou como CSV, n�o como XLSX
? O sistema importa m�ltiplos jogadores de uma vez

## Suporte

Em caso de d�vidas ou problemas, entre em contato com o administrador do sistema.

