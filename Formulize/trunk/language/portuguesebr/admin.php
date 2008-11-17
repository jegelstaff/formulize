<?php
/**
* --------------------------------------------------------------------
*      Portuguese Translation by Paulo Sergio de Araujo alias Gopala
*                     gopala at harekrishna dot org dot br
*    http://www.x-trad.org the XOOPS Official Brazilian Translate Site            
* Translation version 3.0 by GibaPhp - http://br.impresscms.org   
* -------------------------------------------------------------------- 
*/
// Admin
define("_FORM_RENAME_TEXT", "Renomear este formulário"); //GibaPhp 3.0
define("_FORM_EDIT_ELEMENTS_TEXT", "Editar elementos do formulário"); //GibaPhp 3.0
define("_FORM_EDIT_SETTINGS_TEXT", "Editar as configurações do formulário"); //GibaPhp 3.0
define("_FORM_CLONE_TEXT", "Clone este formulário"); //GibaPhp 3.0
define("_FORM_CLONEDATA_TEXT", "Clone este formulário e seus dados"); //GibaPhp 3.0
define("_FORM_DELETE_TEXT", "Excluir este formulário"); //GibaPhp 3.0

define("_AM_SAVE","Salvar");
define("_AM_COPIED","%s copiado");
define("_AM_DBUPDATED","Base de Dados atualizada com sucesso!");
define("_AM_ELE_CREATE","Criar elementos do formulário");
define("_AM_ELE_EDIT","Editar elemento de formulário: %s");
define("_AM_FORM","Formulário: ");
define("_AM_REQ","Resultados do módulo de formulário: ");
define("_AM_SEPAR",'{SEPAR}');
define("_AM_ELE_FORM","Elementos de formulário");
define("_AM_PARA_FORM","Parâmetros de Formulário");

define("_AM_ELE_CAPTION","Legenda");
define("_AM_ELE_CAPTION_DESC","<br /></b>{SEPAR} permite a você não exibir o nome do elemento");
define("_AM_ELE_DEFAULT","Valor padrão");
define("_AM_ELE_DESC","Texto descritivo");
define("_AM_ELE_DESC_HELP","O que você digitar nesta caixa aparecerá abaixo da legenda, da mesma forma que faz este texto.");
define("_AM_ELE_COLHEAD","Cabeçalho de coluna (opcional)");
define("_AM_ELE_COLHEAD_HELP","Se você especificar um cabeçalho de coluna, esse texto será usado no lugar da legenda, na tela <b>Lista de Entradas</b>. Isso é útil quando a legenda é muito longa, ou se você quer as legendas escritas do ponto de vista do usuário, e os cabeçalhos de coluna escritos do ponto de vista do leitor do relatório.");
define("_AM_ELE_HANDLE","Manipular dados (opcional)");
define("_AM_ELE_HANDLE_HELP","You can specify a short name for this element.  The short name will be used by the database when storing information.  If you leave this blank, the element ID number will be used."); //GibaPhp 3.0
define("_AM_ELE_DETAIL","Detalhe");
define("_AM_ELE_REQ","Obrigatório");
define("_AM_ELE_ORDER","Ordem");
define("_AM_ELE_DISPLAY","Exibir");
//define("_AM_ELE_DISPLAYLIST","Mostrar este elemento para estes grupos, na lista de entradas");
define("_AM_ELE_PRIVATE","Privado");
define("_AM_ELE_HANDLE_HEADING","Manipulador de Dados/ID"); //GibaPhp 3.0
define("_AM_ELE_TYPE_HEADING","Tipo"); //GibaPhp 3.0
define("_AM_ELE_DISPLAY_HEADING","Mostrar"); //GibaPhp 3.0


define("_AM_ELE_TEXT","Caixa de texto");
define("_AM_ELE_TEXT_DESC","{NAME} imprimirá o nome completo;<br />{UNAME} imprimirá o nome de usuário;<br />{EMAIL} imprimirá o e-mail do usuário;<br />Código PHP (terminando com a linha '&#36;default = &#36;something;') será interpretado para gerar o valor padrão.");
define("_AM_ELE_TEXT_DESC2","<br />Código PHP é o única situação em que será lida mais de uma linha desta caixa de texto.");
define("_AM_ELE_TAREA","Área de texto");
define("_AM_ELE_MODIF","Texto a exibir (células esquerda e direita)");
define("_AM_ELE_MODIF_ONE","Texto a exibir (perpassar as células)");
define("_AM_ELE_INSERTBREAK","Conteúdo HTML para esta linha:");
define("_AM_ELE_IB_DESC","A legenda não será exibida. Somente o texto nesta caixa aparecerá na tela, em uma linha única atravessando as colunas do formulário.");
define("_AM_ELE_IB_CLASS","Classe CSS para a linha:");
define("_AM_ELE_SELECT","Caixa de seleção");
define("_AM_ELE_CHECK","Caixas de verificação");
define("_AM_ELE_RADIO","Botões de rádio");
define("_AM_ELE_YN","Simples botão de rádio sim/não");
define("_AM_ELE_DATE","Data");
define("_AM_ELE_REQ_USELESS","Não aplicável a caixas de seleção, caixas de verificação ou botões de rádio");
define("_AM_ELE_SEP","Linha de separação");
define("_AM_ELE_NOM_SEP","Nome da separação");
define("_AM_ELE_UPLOAD","Junte um arquivo");
define("_AM_ELE_CLR","com a cor");

// number options for textboxes
define("_AM_ELE_NUMBER_OPTS","Se apenas os números são permitidos ...");
define("_AM_ELE_NUMBER_OPTS_DESC","Você pode definir estas opções para controlar o modo como são manipulados os decimais, e como os números são formatados na tela.");
define("_AM_ELE_NUMBER_OPTS_DEC","Número de casas decimais:");
define("_AM_ELE_NUMBER_OPTS_PREFIX","Mostrar números com esse prefixo (ex: '$'):");
define("_AM_ELE_NUMBER_OPTS_DECSEP","Separe os decimais com esse caráter (ex: '.'):");
define("_AM_ELE_NUMBER_OPTS_SEP","Separe os milhares com o carácter (ex: ','):");
define("_AM_ELE_DERIVED_NUMBER_OPTS","Se essa fórmula produz uma série ...");

// added - start - August 227 2005 - jpc
define("_AM_ELE_TYPE","O que as pessoas devem digitar nesta caixa?");
define("_AM_ELE_TYPE_DESC","Escolha 'Somente números' para excluir caracteres não-numéricos desta caixa quando uma entrada for salva. Isto assegura que operações matemáticas poderão ser executadas com o conteúdo digitado");
define("_AM_ELE_TYPE_STRING","Qualquer coisa");
define("_AM_ELE_TYPE_NUMBER","Somente números");
// added - end - August 22 2005 - jpc


define("_AM_ELE_SIZE","Tamanho");
define("_AM_ELE_MAX_LENGTH","Comprimento máximo");
define("_AM_ELE_ROWS","Linhas");
define("_AM_ELE_COLS","Colunas");
define("_AM_ELE_OPT","Opções");
define("_AM_ELE_OPT_DESC","Selecionar '{FULLNAMES}' ou '{USERNAMES}' produzirá uma lista de usuários baseada nas limitações de grupo ajustadas abaixo.<br /><br />Marque as caixas de verificação para selecionar os valores padrão");
define("_AM_ELE_OPT_DESC_CHECKBOXES","Tick the check boxes for selecting default values<br>Boxes with no text in them will be ignored when you click <i>Save</i>"); //GibaPhp 3.0
define("_AM_ELE_OPT_DESC1","<br />Somente a primeira opção será usada, caso a seleção múltipla não seja permitida");
define("_AM_ELE_OPT_DESC2","Selecione o valor padrão clicando nos botões de rádio. Caixas sem texto nelas serão ignoradas quando você clicar no botão <i>Salvar<>");
define("_AM_ELE_OPT_UITEXT", "The text visible to the user can be different from what is stored in the database.  This is useful if you want to have numbers saved in the database, but text visible to the user so they can make their selection.  To do this, use the \"pipe\" character (usually above the Enter key) like this:  \"10|It has been 10 days since I visited this website\""); //GibaPhp 3.0
define("_AM_ELE_ADD_OPT","Adicionar %s opções");
define("_AM_ELE_ADD_OPT_SUBMIT","Adicionar");
define("_AM_ELE_SELECTED","Selecionado");
define("_AM_ELE_CHECKED","Marcado");
define("_AM_ELE_MULTIPLE","Permitir seleções múltiplas");
define("_AM_ELE_TYPE","Exibir a separação interna");
define("_AM_ELE_GRAS","Verde");
define("_AM_ELE_RGE","Vermelho");
define("_AM_ELE_CTRE","Centro");
define("_AM_ELE_ITALIQ","Itálico");
define("_AM_ELE_SOUL","Sublinhado");
define("_AM_ELE_BLEU","Azul");
define("_AM_ELE_FICH",'Arquivo');
define("_AM_ELE_TAILLEFICH","Tamanho máximo do arquivo");
define("_AM_ELE_PDS","Unidade");
define("_AM_ELE_TYPE",'Tipos permitidos');
define("_AM_ELE_DELIM_CHOICE",'Delimitador entre cada opção');
define("_MI_formulize_DELIMETER_SPACE","Espaço em branco");
define("_MI_formulize_DELIMETER_BR","Quebra de linha");
define("_MI_formulize_DELIMETER_CUSTOM","HTML Personalizado");

//added to handle the formlink part of the selectbox element -- jwe 7/29/04
define("_AM_ELE_FORMLINK", "Vincular opções a outro formulário");
define("_AM_ELE_FORMLINK_DESC","Selecione um campo em outro formulário e use as entradas dele como as opções para esta Caixa de Seleção. (Esta opção sobrescreve qualquer opção especificada acima.)");
define("_AM_FORMLINK_NONE", "Não vincular -- opções acima em efeito");
define("_AM_ELE_FORMLINK_TEXTBOX", "Associar valores com outro elemento de formulário");
define("_AM_ELE_FORMLINK_DESC_TEXTBOX","Se você selecionar aqui outro elemento de formulário, o texto que os usuários digitarem neste elemento será comparado com os valores do outro elemento. Se uma coincidência for encontrada, o texto que o usuário digitou neste elemento será 'clicável' na tela \"Lista de Entradas\", e permitirá aos usuários encontrar entradas em outro formulário");
define("_AM_FORMLINK_NONE_TEXTBOX", "Nenhuma associação em efeito");
define("_AM_ELE_FORMLINK_SCOPE", "Se as opções são vincular, associar ou, ainda, {FULLNAMES} ou {USERNAMES}, limite-as aos valores disponíveis para os grupos selecionados aqui.");
define("_AM_ELE_FORMLINK_SCOPE_DESC", "Os grupos que você escolheu definem o total de possíveis opções a serem usadas. Opcionalmente, você pode limitar ainda mais as opções para os atuais membros dos grupos selecionados. Neste caso, os grupos que você selecionou serão ignorados se o usuário atual não for também um membro do grupo.");
define("_AM_ELE_FORMLINK_SCOPE_ALL", "Usar todos os grupos");
define("_AM_ELE_FORMLINK_SCOPELIMIT_NO", "Usar todos esses grupos");
define("_AM_ELE_FORMLINK_SCOPELIMIT_YES", "Usar somente os grupos dos quais o usuário atual é membro");
define("_AM_ELE_FORMLINK_ANYALL_ANY", "Incluir entradas de usuários que são membros de algum grupo em uso<br>"); //GibaPhp 3.0
define("_AM_ELE_FORMLINK_ANYALL_ALL", "Incluir entradas de usuários que são membros de todos os grupos em uso"); //GibaPhp 3.0

// formlink scope filters -- feb 6 2008
define("_AM_ELE_FORMLINK_SCOPEFILTER", "Caso as opções sejam ligadas -- ou estão {FULLNAMES} OR {USERNAMES} -- filtro baseia-las sobre estas propriedades de sua entrada na fonte do formulário."); //GibaPhp 3.0
define("_AM_ELE_FORMLINK_SCOPEFILTER_DESC", "Quando você faz um link para os valores de outro formulário, talvez você queira limitar os valores incluídos na lista com base em certas propriedades das entradas no outro formulário. Por exemplo, se você está ligando para os nomes das tarefas em uma tarefa do formulário, você pode querer listar apenas tarefas que estão incompletas. Se há uma pergunta no formulário da tarefa que lhe pergunta se a tarefa está concluída, você poderia especificar um filtro como:\"Task is complete = No\".<br><br> Se as opções são {FULLNAMES} or {USERNAMES} , E você estiver usando um formulário de perfil personalizado em conjunto com o módulo Registrador de Códigos para o registro, você pode filtrar os nomes baseados no perfil formulário."); //GibaPhp 3.0
define("_AM_ELE_FORMLINK_SCOPEFILTER_ALL", "Nenhum filtro em vigor (para selecionar este, limpe os filtros existentes)."); //GibaPhp 3.0
define("_AM_ELE_FORMLINK_SCOPEFILTER_CON", "Filtrar as opções baseadas neste/nestas condições:"); //GibaPhp 3.0
define("_AM_ELE_FORMLINK_SCOPEFILTER_ADDCON", "Adicionar outra condição"); //GibaPhp 3.0
define("_AM_ELE_FORMLINK_SCOPEFILTER_REFRESHHINT", "(Se a primeira lista aqui está vazia, clique no link 'Adicionar outra condição' para atualizar isto.)"); //GibaPhp 3.0
       
       
  

// subforms
define("_AM_ELE_SUBFORM_FORM", "Qual formulário você quer incluir como um subformulário?");
define("_AM_ELE_SUBFORM", "Subformulário (de um formulário estruturado)");
define("_AM_ELE_SUBFORM_DESC", "Quando você exibe o formulário atual como como parte de um formulário estruturado, a interface do subformulário pode ser incluída no formulário. A interface do subformulário permite aos usuários criar e modificar entradas nesse subformulário sem sair do formulário principal. A lista aqui mostra todos os possíveis subformulários de todos os formulários estruturados dos quais este formulário faz parte.");
define("_AM_ELE_SUBFORM_NONE", "Nenhum subformulário disponível - defina um formulário estruturado primeiro");
define("_AM_ELE_SUBFORM_ELEMENTS", "Quais elementos devem ser exibidos como parte da interface do subformulário?");
define("_AM_ELE_SUBFORM_ELEMENTS_DESC", "Aproximadamente três ou quatro elementos de um subformulário podem ser exibidos confortavelmente como parte do formulário principal. Mais de quatro elementos e a interface começa a se tornar abarrotada. Você pode escolher quais elementos quer exibir ao selecioná-los nesta lista. Usuários podem sempre modificar os elementos por clicar no botão próximo a cada entrada do subformulário que for listada no formulário principal.");
define("_AM_ELE_SUBFORM_REFRESH", "Atualizar a lista de elementos para combinar o formulário selecionado");
define("_AM_ELE_SUBFORM_BLANKS", "Quantos espaços em branco devem ser indicados neste sub-formulário quando a primeira página for carregada?"); //GibaPhp 3.0

// grids
define("_AM_ELE_GRID", "Tabela de elementos existentes (coloque ANTES os elementos que ela contém)");
define("_AM_ELE_GRID_HEADING", "que texto deve aparecer com cabeçalho desta tabela?");
define("_AM_ELE_GRID_HEADING_USE_CAPTION", "A legenda digitada acima");
define("_AM_ELE_GRID_HEADING_USE_FORM", "O título deste formulário");
define("_AM_ELE_GRID_HEADING_NONE", "Nenhum cabeçalho");
define("_AM_ELE_GRID_ROW_CAPTIONS", "Entre com a legenda para as linhas desta tabela");
define("_AM_ELE_GRID_ROW_CAPTIONS_DESC", "Cada tabela é uma grade com colunas e linhas. O lado esquerdo da tabela possui uma legenda em cada célula para iniciar cada linha. Digite o texto que você quer usar para as legendas, separadas por virgula. Se sua legendas forem longas, pode funcionar visualmente melhor colocar cada legenda em uma linha separada.");
define("_AM_ELE_GRID_COL_CAPTIONS", "Entre com as legendas para as linhas desta tabela");
define("_AM_ELE_GRID_COL_CAPTIONS_DESC", "Cada tabela é uma grade com colunas e linhas. O topo da tabela possui uma legenda em cada célula como cabeçalho de cada coluna. Digite o texto que você quer usar para as legendas, separadas por vírgula. Se sua legendas forem longas, pode funcionar visualmente melhor colocar cada legenda em uma linha separada.");
define("_AM_ELE_GRID_BACKGROUND", "Sombreado de fundo");
define("_AM_ELE_GRID_BACKGROUND_HOR", "Alternar o sombreado de cada linha da tabela");
define("_AM_ELE_GRID_BACKGROUND_VER", "Alternar o sombreado de cada coluna da tabela");
define("_AM_ELE_GRID_START", "Escolha o primeiro elemento, o qual aparecerá no canto superior esquerdo da tabela");
define("_AM_ELE_GRID_START_DESC", "Cada tabela terá um número de elementos nela, igual ao número de linhas multiplicado pelo número de colunas. Isto é: se você tem três linhas e quatro colunas, terá 12 elementos em sua tabela. O primeiro elemento aparece no canto superior esquerdo, e o próximo elemento, depois deste, aparece na célula visinha a direita. Uma vez que o fim da linha tenha sido alcançado, o elemento seguinte aparece na primeira célula da linha imediatamente abaixo. Elementos são desenhados no formulário de acordo com a ordem correntemente assinalada para eles: se você tem 12 elementos em sua tabela, então os 11 elementos após o primeiro serão usados em sua tabela. Portanto, esteja seguro de que todos os elementos que você quer usar na tabela estão consecutivamente ordenados em seu formulário.");

// derived columns
define("_AM_ELE_DERIVED", "Valores derivados de outros elementos");
define("_AM_ELE_DERIVED_CAP", "Fórmula para gerar valores neste elemento");
define("_AM_ELE_DERIVED_DESC", "Selecione um elemento acima para adicioná-lo nesta fórmula. Você pode usar também números ID de elementos ou de manipuladores de formulários estruturados em sua fórmula, desde que eles estejam envolvidos por aspas duplas. A fórmula pode ter múltiplas linhas, ou passos, e você pode usar código PHP na fórmula. A última linha deve estar no formato <i>\$value = \$algumacoisa</i> onde \$algumacoisa é o número final ou fórmula que você quer usar.<br /><br />Exemplo:<br />\$value = \"Número de acertos\" / \"Total de tentativas\" * 100");
define("_AM_ELE_DERIVED_ADD", "Adicionar à formula");

define("_AM_ELE_SELECT_NONE","Nenhum elemento selecionado.");
define("_AM_ELE_CONFIRM_DELETE","Você tem certeza de que quer excluir este elemento do formulário?<br>Todos os dados associados a este elemento serão também excluídos.");

define("_AM_TITLE","Menu da administração");
define("_AM_ID","ID");
define("_AM_POS","Posiçãon");
define("_AM_POS_SHORT","Pos.");
define("_AM_INDENT","Recuo esquerdo");
define("_AM_INDENT_SHORT","Ind.");
define("_AM_ITEMNAME","Nome");
define("_AM_ITEMURL","URL");
define("_AM_STATUS","Status");
define("_AM_FUNCTION","Função");
define("_AM_ACTIVE","ativo");
define("_AM_INACTIVE","inativo");
define("_AM_BOLD","negrito");
define("_AM_NORMAL","normal");
define("_AM_MARGINBOTTOM","Margem inferior");
define("_AM_MARGIN_BOTTOMSHORT","mrg. inf.");
define("_AM_MARGINTOP","Margem superior");
define("_AM_MARGIN_TOPSHORT","mrg. sup.");
define("_AM_EDIT","Editar");
define("_AM_DELETE","Excluir");
define("_AM_ADDMENUITEM","Adicionar item de menu");
define("_AM_CHANGEMENUITEM","Modificar item de menu");
define("_AM_SITENAMET","Nome do site:");
define("_AM_URLT","URL:");
define("_AM_FONT","Fonte:");
define("_AM_STATUST","Status:");
define("_AM_MEMBERSONLY","Usuários autorizados");
define("_AM_MEMBERSONLY_SHORT","Apenas<br>regist.");
define("_AM_MEMBERS","somente membros");
define("_AM_ALL","todos os usuários");
define("_AM_ADD","Adicionar");
define("_AM_EDITMENUITEM","Editar item de menu");
define("_AM_DELETEMENUITEM","Excluir item de menu");
define("_AM_SAVECHANG","Salvar alterações");
define("_AM_WANTDEL","Você realmente quer excluir este item do menu?");
define("_AM_YES","Sim");
define("_AM_NO","Não");
define("_AM_formulizeMENUSTYLE","MyMenu-Style"); //GibaPhp 3.0 - verificar nos testes ????
define("_AM_MAINMENUSTYLE","MainMenu-Style"); //GibaPhp 3.0- verificar nos testes????

define("_AM_VERSION","1.0");
define("_AM_REORD","Nova classificação");
define("_AM_SAVE_CHANGES","Salvar alterações");

define("_formulize_CAPTION_MATCH", "A legenda que você indicou já está em uso. Um '2' foi adicionado a ela.");
define("_formulize_CAPTION_QUOTES", "Legendas não poder conter aspas. Elas foram removidas.");
define("_formulize_CAPTION_SLASH", "Legendas não podem conter barras invertidas. Elas foram removidas.");
define("_formulize_CAPTION_LT", "Legendas não podem conter o sinal '<'. Eles foram removidos.");
define("_formulize_CAPTION_GT", "Legendas não podem conter o sinal '>'. Eles foram removidos.");

define("_AM_VIEW_FORM", "Ver o formulário");
define("_AM_GOTO_PARAMS", "Editar os ajustes do formulário");
define("_AM_PARAMS_EXTRA", "(Especifique quais elementos aparecem<br>na página <i>Ver Entradas</i>)");
define("_AM_GOTO_MAIN", "Retornar à página principal");
define("_AM_GOTO_MODFRAME", "Retornar à primeira página<br>do Formulário Estruturado");

define("_AM_CLEAR_DEFAULT", "Valores padrão");

define("_AM_SAVING_CHANGES", "Salvar mudanças");
define("_AM_EDIT_ELEMENTS", "Editar os elementos do formulário");

define("_AM_CONFIRM_DELCAT", "Você está prestes a excluir uma Categoria de menu! Por favor, confirme.");
define("_AM_MENUCATEGORIES", "Categorias de menu");
define("_AM_MENUCATNAME", "Nome:");
define("_AM_MENUSAVEADD", "Adicionar/Salvar");
define("_AM_MENUNOCATS", "Nenhuma categoria");
define("_AM_MENUEDIT", "Editar");
define("_AM_MENUDEL", "Excluir");
define("_AM_MENUCATLIST", "Categorias:");
define("_AM_CATSHORT", "Categoria");
define("_AM_CATGENERAL", "Formulários gerais");

define("_AM_CANCEL", "Cancelar");

define("_AM_CONVERT", "Converter");
define("_AM_CONVERT_HELP", "Converte esta caixa de texto de simples para multilinhas (ou vice-versa)");
define("_AM_ELE_CANNOT_CONVERT", "Não há opções de conversão para este tipo de elemento");
define("_AM_CONVERT_CONFIRM", "Você quer converter esta caixa de texto de simples para multilinhas (ou vice-versa)?");
define("_AM_ELE_CONVERTED_TO_TEXTBOX", "Esta caixa de texto multilinhas foi convertida para uma caixa de texto de linha única.");
define("_AM_ELE_CONVERTED_TO_TEXTAREA", "Esta caixa de texto de linha única foi convertida para uma caixa de texto multilinhas.");


// added - start - August 25 2005 - jpc
define("_AM_FORM_DISPLAY_MULTIPLE","Personalizar");
// added - end - August 25 2005 - jpc
define("_AM_FORM_DISPLAY_EXTRA", "Use esta lista para exibir certos elementos em um formulário para apenas certos grupos. Isto é para situações onde usuários em diferentes grupos veriam diferentes partes do mesmo formulário. Normalmente, você pode deixar isto para 'Todos os grupos'.");
define("_AM_FORM_DISPLAY_ALLGROUPS", "Todos os grupos com permissão para este formulário");
define("_AM_FORM_DISPLAY_NOGROUPS", "Nenhum grupo");
define("_AM_FORM_FORCEHIDDEN", "Incluir como um elemento oculto para usuários que não podem vê-lo");
define("_AM_FORM_FORCEHIDDEN_DESC", "Atualmente, somente afeta botões de rádio e caixas de texto. Esta opção cria um elemento oculto ao invés da série de botões de rádio ou da caixa de texto, e o valor desse elemento oculto será o valor padrão especificado acima. Útil quando um valor padrão precisa ser definido sempre, em cada entrada do formulário, mas nem todos os grupos vêem normalmente este elemento.");
define("_AM_FORM_FORCEHIDDEN_DESC", "Atualmente, somente afeta botões e caixas. Esta opção irá causar uma forma oculta do elemento a ser criado em vez do botão de rádio ou campos numéricos, e o valor do elemento será ocultado o valor padrão especificado acima. Sempre será útil quando você precisar definir um valor padrão em todos os formulários de entrada de dados, mas nem todos os grupos normalmente poderão ver este elemento.");

define("_AM_ELE_DISABLED", "Desativar este elemento para quaisquer grupos?"); //GibaPhp 3.0
define("_AM_FORM_DISABLED_EXTRA", "Use essa opção para tornar este elemento inativo para determinados grupos. O elemento ainda serão mostrados aos usuários de acordo com a opção mostrada acima, mas você pode usar essa opção para desabilitar o elemento para que os usuários não possam alterar o seu valor. Esta opção atualmente funciona apenas para as caixas de texto e nas áreas de texto."); //GibaPhp 3.0
define("_AM_FORM_DISABLED_ALLGROUPS", "Desabilitar para todos os grupos"); //GibaPhp 3.0
define("_AM_FORM_DISABLED_NOGROUPS", "Nenhum grupo Desabilitado"); //GibaPhp 3.0


define("_AM_ELE_OTHER", 'Para a opção "Outro", coloque {OTHER|*número*} em uma das caixas de texto. Isto é: {OTHER|30} gera uma caixa de texto com 30 caracteres de largura');

define("_AM_FORM_PRIVATE", "A informação colocada pelos usuários neste elemento é privada");
define("_AM_FORM_PRIVATE_DESC", "Se esta caixa é marcada, a informação colocada pelos usuários neste elemento será visível somente a outros usuários que possuam permissão para ver elementos privados. Esta opção é útil para tornar informações sensíveis disponíveis somente para gestores apropriados.");

//added by félix <INBOX International> for sedonde (colorpicker feature)
define("_AM_ELE_COLORPICK","Selecionador de cor");

// SCREENS...including multipage

define("_AM_FORMULIZE_SCREEN_TYPE", "Tipo: ");

define("_AM_FORMULIZE_DEFINED_SCREENS", "Telas definidas para este formulário");
define("_AM_FORMULIZE_DELETE_SCREEN", "Excluir");
define("_AM_FORMULIZE_ADD_NEW_SCREEN_OF_TYPE", "Adicionar uma nova tela deste tipo:");
define("_AM_FORMULIZE_SCREENTYPE_MULTIPAGE", "Versão multipáginas do formulário");
define("_AM_FORMULIZE_SCREENTYPE_LISTOFENTRIES", "Lista de Entradas deste fomulário");
define("_AM_FORMULIZE_ADD_SCREEN_NOW", "Adicioná-la agora!");
define("_AM_FORMULIZE_SCREEN_FORM", "Criar ou modificar um tela");
define("_AM_FORMULIZE_SCREEN_TITLE", "Título desta tela");
define("_AM_FORMULIZE_USE_NO_FRAMEWORK", "Usar somente este formulário, não a Estrutura");
define("_AM_FORMULIZE_SELECT_FRAMEWORK", "Estrutura para usar nesta tela, se alguma");
define("_AM_FORMULIZE_SCREEN_SECURITY", "Use the security token on this screen?"); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_SECURITY_DESC", "The security token is a defense against cross-site scripting attacks.  However, it can cause problems if you are using an advanced Ajax-based UI in a List of Entries screen, and possibly other screen types."); //GibaPhp 3.0


define("_AM_FORMULIZE_SCREEN_PARAENTRYFORM", "Devem as respostas de uma entrada anterior serem apresentadas como parte deste formulário? Em caso afirmativo, escolher um formulário."); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_PARAENTRYFORM_FALSE", "Não, não mostrar respostas anteriores."); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_PARAENTRYRELATIONSHIP", "Se as respostas anteriores são mostradas, qual é a relação deste formulário para o outro e as suas entradas anteriores?"); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_PARAENTRYREL_BYGROUP", "Entradas pertencem ao mesmo grupo"); //GibaPhp 3.0

define("_AM_FORMULIZE_SCREEN_INTRO", "Texto introdutório para a primeira página deste formulário");
define("_AM_FORMULIZE_SCREEN_THANKS", "Texto de agradecimento para a última página deste formulário");
define("_AM_FORMULIZE_SCREEN_DONEDEST", "O endereço URL do link que os usuários encontram ao terminar de preencher o formulário");
define("_AM_FORMULIZE_SCREEN_BUTTONTEXT", "O texto do link que os usuários encontram ao terminar de preencher o formulário");
define("_AM_FORMULIZE_SCREEN_PRINTALL", "Disponibilizar o link 'Versão para impressão - todas as páginas' ao fim do formulário"); //nmc 2007.03.24
define("_AM_FORMULIZE_SCREEN_PRINTALL_Y", "Sim"); //nmc 2007.03.24
define("_AM_FORMULIZE_SCREEN_PRINTALL_N", "Não"); //nmc 2007.03.24
define("_AM_FORMULIZE_DELETE_THIS_PAGE", "Excluir esta página");
define("_AM_FORMULIZE_CONFIRM_SCREEN_DELETE", "Você tem certeza de que quer excluir esta tela? Por favor, confirme!");
define("_AM_FORMULIZE_CONFIRM_SCREEN_DELETE_PAGE", "Você tem certeza de que quer excluir esta página? Por favor, confirme!");
define("_AM_FORMULIZE_SCREEN_A_PAGE", "Elementos do formulário para exibir na página");
define("_AM_FORMULIZE_SCREEN_ADDPAGE", "Adicionar outra página");
define("_AM_FORMULIZE_SCREEN_INSERTPAGE", "Inserir uma nova página aqui");
define("_AM_FORMULIZE_SCREEN_SAVE", "Salvar esta tela");
define("_AM_FORMULIZE_SCREEN_SAVED", "Os detalhes para esta tela foram salvos na Base de Dados");
define("_AM_FORMULIZE_SCREEN_PAGETITLE", "Título para o número de página");
define("_AM_FORMULIZE_SCREEN_CONS_PAGE", "Condições em que a página será exibida"); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_CONS_NONE", "Sempre exibir esta página"); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_CONS_YES", "Exibir apenas quando as seguintes condições forem verdadeiras:"); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_CONS_ADDCON", "Incluir uma outra condição"); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_CONS_HELP", "Condições são úteis se uma página deve ser exibida somente com base em respostas a perguntas em uma página anterior. Selecione a partir da página anterior perguntas e respostas que devem especificar o resultado a ser exibido nesta página."); //GibaPhp 3.0

// LIST OF ENTRIES SCREEN
define("_AM_FORMULIZE_SCREEN_LOE_BUTTONINTRO", "Especifique quais botões você quer incluir nesta tela:");
define("_AM_FORMULIZE_SCREEN_LOE_BUTTON1", "Qual texto deve estar no botão '");
define("_AM_FORMULIZE_SCREEN_LOE_BUTTON2", "'?");
define("_AM_FORMULIZE_SCREEN_LOE_CONFIGINTRO", "Especifique quais opções de configuração você quer usar:");
define("_AM_FORMULIZE_SCREEN_LOE_CURRENTVIEWLIST", "Qual texto deve introduzir a lista 'Visão Atual'?");
define("_AM_FORMULIZE_SCREEN_LOE_DEFAULTVIEW", "Qual das visões publicadas será usada como visão padrão?");
define("_AM_FORMULIZE_SCREEN_LOE_BLANK_DEFAULTVIEW", "Use a blank default view (ie: display no entries)");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_DEFAULTVIEW", "Se você está personalizando um modelo de lista, a visão padrão ainda continuará a ser usada para controlar quais entradas serão inicialmente incluídas na lista.");
define("_AM_FORMULIZE_SCREEN_LOE_LIMITVIEWS", "Se a lista 'Visão Atual' está em uso, inclua estas visões:");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_LIMITVIEWS", "Se você incluir as visões básicas (\"Entradas por...\"), então a visão selecionada mudará para uma visão básica quando o usuário fizer uma alteração, tal como classificação ou busca rápida.");
define("_AM_FORMULIZE_SCREEN_LOE_DEFAULTVIEWLIMIT", "Incluir todas as visões");
define("_AM_FORMULIZE_SCREEN_LOE_VIEW_ONLY_IN_FRAME", "somente disp. na estrutura: ");
define("_AM_FORMULIZE_SCREEN_LOE_VIEW_ONLY_NO_FRAME", "somente disp. sem estrutura");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK", "Deixe em branco para desativar este botão");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK_LIST", "Deixe em branco para desativar a lista");
define("_AM_FORMULIZE_SCREEN_LOE_NOPUBDVIEWS", "Não há visões publicadas para este formulário");
define("_AM_FORMULIZE_SCREEN_LOE_NOVIEWSAVAIL", "Não há visões disponíveis");
define("_AM_FORMULIZE_SCREEN_LOE_USEWORKING", "A mensagem 'Trabalhando' deve aparecer quando a página esta recarregando?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_USEWORKING", "Se o usuário comumente já clica o botão 'Voltar' de sua interface, desativar esta mensagem pode aumentar a usabilidade.");
define("_AM_FORMULIZE_SCREEN_LOE_USESCROLLBOX", "A lista de entradas deve estar contida dentro de uma caixa de seleção rolante?");
define("_AM_FORMULIZE_SCREEN_LOE_USESEARCHCALCMSGS", "As mensagens de status da 'Busca Avançada' ou dos 'Cálculos' devem aparecer no topo da lista?");
define("_AM_FORMULIZE_SCREEN_LOE_USCM_NEITHER", "nenhuma<br>");
define("_AM_FORMULIZE_SCREEN_LOE_USCM_BOTH", "ambas<br>");
define("_AM_FORMULIZE_SCREEN_LOE_USCM_SEARCH", "somente o status da 'Busca Avançada'<br>");
define("_AM_FORMULIZE_SCREEN_LOE_USCM_CALC", "somente o status dos 'Cálculos'");
define("_AM_FORMULIZE_SCREEN_LOE_USEHEADINGS", "Os cabeçalhos devem ser exibidos no alto de cada coluna?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_USEHEADINGS", "Sem cabeçalhos nas colunas, não será possível classificar as entradas na visão.");
define("_AM_FORMULIZE_SCREEN_LOE_REPEATHEADERS", "Se você está usando cabeçalhos, com que freqüência eles devem ser repetidos na lista de entradas?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_REPEATHEADERS", "Repetir os cabeçalhos torna mais fácil para os usuários saber qual coluna estão olhando quando rolam a lista. Deixe em '0' para ter cabeçalhos somente no topo da lista");
define("_AM_FORMULIZE_SCREEN_LOE_ENTRIESPERPAGE", "Quantas entradas devem aparecer em cada página de uma lista?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_ENTRIESPERPAGE", "Deixe em '0' para ter todas as entradas aparecendo em uma única página.");
define("_AM_FORMULIZE_SCREEN_LOE_VIEWENTRYSCREEN", "Qual tela deve ser utilizada para exibir entradas individuais quando os usuários clicam nelas?"); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_LOE_VIEWENTRYSCREEN_DEFAULT", "Use a versão padrão deste formulário"); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_LOE_COLUMNWIDTH", "Quantos pixels de largura cada coluna deve ter?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_COLUMNWIDTH", "Deixe em '0' para ter as colunas expandidas a sua largura natural.");
define("_AM_FORMULIZE_SCREEN_LOE_TEXTWIDTH", "Quantos caracteres de texto devem ser exibidos em uma célula?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_TEXTWIDTH", "Deixe em '0' para não ter limitação.");
define("_AM_FORMULIZE_SCREEN_LOE_USESEARCH", "As caixa de 'Busca Rápida' devem aparecer no alto de cada coluna?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_USESEARCH", "Se as caixas de 'Busca Rápida' forem desativadas, elas ainda estarão disponíveis nos modelos para o Topo e Base (veja abaixo).");
define("_AM_FORMULIZE_SCREEN_LOE_USECHECKBOXES", "As caixas de verificação devem aparecer à esquerda de cada entrada, para que as entradas possam ser selecionadas pelos usuários?");
define("_AM_FORMULIZE_SCREEN_LOE_UCHDEFAULT", "'Sim' mostra as caixas de verificação, dependendo da permissão que o usuário tenha para excluir entradas<br>");
define("_AM_FORMULIZE_SCREEN_LOE_UCHALL", "'Sim': mostra as caixas de verificação em todas as entradas<br>");
define("_AM_FORMULIZE_SCREEN_LOE_UCHNONE", "'Não': não mostra as caixas de verificação");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_USECHECKBOXES", "Se você mostrar as caixas de verificação em todas as entradas, e incluir o botão 'Excluir selecionadas' nesta tela, os usuários poderão selecionar e exluir entradas que, de outra forma, não seriam capazes de excluir!");
define("_AM_FORMULIZE_SCREEN_LOE_USEVIEWENTRYLINKS", "Devem os links 'lente de aumento' aparecerem à esquerda de cada entrada, de modo que os usuários possam clicar e ver os detalhes completos de cada entrada?");
define("_AM_FORMULIZE_SCREEN_LOE_HIDDENCOLUMNS", "Selecione quaisquer colunas cujos valores atuais de cada entrada você quer incluir na lista como elementos ocultos do formulário.");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_HIDDENCOLUMNS", "Esta opção é útil quando você necessita que algum texto na tela seja enviado de volta no <i>\$_POST</i> como parte da próxima página a ser carregada. Você pode usar <i>gatherHiddenValue('</i>handle<i>');</i> em um botão personalizado para acessar os valores recebidos. Quaisquer colunas que você selecionar continuarão a ser exibidas normalmente na lista e, em adição, terão seus valores registrados nos elementos ocultos criados.");
define("_AM_FORMULIZE_SCREEN_LOE_DECOLUMNS", "Selecione quaisquer colunas cujos dados você quer que sejam exibidos como elementos do formulário, ao invés de serem exibidos como texto:");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_DECOLUMNS", "<b>CUIDADO:</b> não marque as caixas de verificação acima se você está exibindo alguma caixa de verificação na lista!");
define("_AM_FORMULIZE_SCREEN_LOE_DESAVETEXT", "Se você selecionou alguma coluna para exibir como elemento do formulário, que texto deve ser usado em um botão 'Enviar' abaixo da lista de entradas?");
define("_AM_FORMULIZE_SCREEN_LOE_DVMINE", "Entradas do usuário atual");
define("_AM_FORMULIZE_SCREEN_LOE_DVGROUP", "Entradas do(s) grupo(s) do usuário atual");
define("_AM_FORMULIZE_SCREEN_LOE_DVALL", "Entradas de todos os grupos");
define("_AM_FORMULIZE_SCREEN_LOE_BUTTON_SECTION1", "Você pode mudar o texto dos botões abaixo. Também, se você usa modelos de Topo ou Base personalizados, esses botões estarão disponíveis neles.");
define("_AM_FORMULIZE_SCREEN_LOE_BUTTON_SECTION2", "Você pode mudar o texto dos botões abaixo. Se você usa um Modelo de Lista personalizado, esses botões não aparecem na tela por padrão, mas você pode usar modelos de Topo ou Base especificamente personalizados para incluí-los.");
define("_AM_FORMULIZE_SCREEN_LOE_CONFIG_SECTION1", "As opções de configuração abaixo terão efeito, independentemente de você usar ou não um Modelo de Lista personalisado.");
define("_AM_FORMULIZE_SCREEN_LOE_CONFIG_SECTION2", "As opções de configuração abaixo NÃO terão efeito se você usar um Modelo de Lista personalisado.");
define("_AM_FORMULIZE_SCREEN_LOE_TEMPLATEINTRO", "Especifique as opções de personalização do modelo para esta tela:");
define("_AM_FORMULIZE_SCREEN_LOE_TEMPLATEINTRO2", "<span style=\"font-weight: normal\"><p><b>Modelos de Topo e Base</b></p>\n<p>Se você especificar algum código PHP nos modelos de Topo e Base, ele será usado para controlar a aparência do espaço acima e/ou abaixo da lista de entradas.</p>\n<p><b>CUIDADO:</b> se você incluíu algum elmento caixa de verificação em seus modelos, desative as caixas de verificação que aparecem no lado esquerdo da lista!</p>\n<p>Utilize este código PHP para ajustar a disposição dos botões para a sua preferência, ou incluir instruções personalizadas, etc.</p>\n<p>Para incluir botões e controles, use estas variáveis:</p>
<table cellpadding=5 border=0>
<tr>
<td>
<ul>
<li>\$addButton</li>
<li>\$addMultiButton</li>
<li>\$addProxyButton</li>
<li>\$exportButton</li>
<li>\$importButton</li>
<li>\$notifButton</li>
<li>\$currentViewList</li>
<li>\$changeColsButton</li>
<li>\$saveButton (se alguma coluna estiver exibida como elemento do formulário)</li>
</ul>
</td><td>
<ul>
<li>\$calcButton</li>
<li>\$advSearchButton</li>
<li>\$cloneButton</li>
<li>\$deleteButton</li>
<li>\$selectAllButton</li>
<li>\$clearSelectButton</li>
<li>\$resetViewButton</li>
<li>\$saveViewButton</li>
<li>\$deleteViewButton</li>
<li>\$pageNavControls (se há mais de uma página de entreadas)</li>
</ul>
</td>
</tr>
</table>
<p>Para as caixas de Pesquisa Rápida, use \"\$quickSearch<i>Coluna</i>\" where <i>Coluna</i> ou é o número do ID do elemento, ou se estiver usando um elemento manipulado do FrameWork.</p>\n
<p>Você também pode fazer uma caixa de Filtro Rápido suspensa, utilizando \"\$quickFilter<i>Coluna</i>\".  Isto só funciona para caixas de seleção, botões e caixas de verificação.</p>\n
<p>Para os botões personalizados, utilize \"\$handle\" where <i>handle</i> é o que você especificou para lidar com esse botão.  Você pode usar \"\$messageText\" para controlar quando você clicar no botão da mensagem que aparecer na tela. Por padrão, a mensagem aparece centralizada no topo.</p>\n<p>Se a visão atual da lista está disponível, você pode determinar qual a opinião foi selecionada a partir da última lista, por verificar se <i>\$The_view_name</i> é verdadeiro ou não.  Você pode também verificar <i>\$viewX</i> onde X é um número correspondente à posição do ponto de vista na lista, de 1 para n.  Você pode usá-la para colocar if..else cláusulas em seu modelo, assim ele muda conforme o que for selecionado na visão.</p>\n<p><b>Listar Modelo</b></p>\n<p>Se você especificar qualquer código PHP para a Lista do  Modelo, este será usado para desenhar em cada linha da lista.</p>\n<p>Você não precisa criar um laço foreach loop ou qualquer outra estrutura neste modelo.  O código PHP vai precisar ser executado dentro de um circuito fechado que é executado uma vez para cada entrada.</p>\n<p>Você tem pleno acesso a XOOPS/ImpressCMS e objetos do Formulize, funções, variáveis e constantes neste template (modelo), incluindo <i>\$fid</i> para o ID do formulário.  Usar \$entry para remeter à entrada atual na lista.  Por examplo:</p>\n<p style=\"font-family: courier\">&nbsp;&nbsp;&nbsp;display(\$entry, \"phonenumber\");</p>\n<p>Este código exibirá o número de telefone gravado nesta entrada (supondo \"phonenumber\" é um elemento válido do manipulador).</p><p>Você pode usar \"\$selectionCheckbox\" para exibir uma caixa especial usada para selecionar uma entrada.</p><p>Você pode usar uma função especial chamando \"viewEntryLink\" para criar um link para a entrada de modo que os usuários podem editá-lo.  Essa função tem um parâmetro, que é o texto que será clicável.  Examplos:</p><p style=\"font-family: courier\">&nbsp;&nbsp;&nbsp;print viewEntryLink(\"Clique para ver esta entrada\");<br>&nbsp;&nbsp;&nbsp;print viewEntryLink(display(\$entry, \"taskname\"));<br>&nbsp;&nbsp;&nbsp;print viewEntryLink(\"&lt;img src='\" . XOOPS_ROOT_PATH . \"/images/button.jpg'&gt;\");</p></span>\n"); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_LOE_TOPTEMPLATE", "Modelo para a porção superior da página, acima da lista:");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_TOPTEMPLATE", "Se você destivar as barras de rolagem e não usar os botões de exportação, então o resultado do código que você digitar aqui (e nos Modelos de Lista e de Botões) será exibido na tela consecutivamente. Isto significa que voc pode começar uma tabela no Modelo de Topo, especificar as tags &lt;tr&gt; no Modelo de Lista e fechar a tabela no Modelo de Base. Essencialmente, esses três modelos dão a você controle sobre o layout da página toda.");
define("_AM_FORMULIZE_SCREEN_LOE_BOTTOMTEMPLATE", "Modelo para a porção inferior da página, abaixo da lista:");
define("_AM_FORMULIZE_SCREEN_LOE_LISTTEMPLATE", "Modelo para cada entrada, na porção da página que contém a lista:");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_LISTTEMPLATE", "Se você especificar um Modelo de Lista, certos botões e opções de configuração mencionados acima se tornarão indisponíveis.");
define("_AM_FORMULIZE_SCREEN_LOE_LISTTEMPLATE_HELPINTRO_FRAMEWORK", "Abaixo se encontra uma lista dos manipuladores de todos os elementos de formulário deste Formulário Estruturado. Use-os com a função <i>display</i>.");
define("_AM_FORMULIZE_SCREEN_LOE_LISTTEMPLATE_HELPINTRO_FORM", "Abaixo se encontra uma lista dos IDs de todos os elementos deste formulário. Use-os com a função <i>display</i>.");
// CUSTOM BUTTONS
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTONINTRO", "Especifique botões personalizados para esta tela:");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTONINTRO2", "Botões personalizados podem ser adicionados acima, abaixo ou dentro da lista, usando os modelos (veja abaixo). Você deve especificar que efeito cada botão personalizado deve ter. Por exemplo: um botão 'Cancelar Assinatura' pode atualizar um elemento de formulário chamado 'Data fim assinatura', e usar a data de hoje como valor para ele.");
define("_AM_FORMULIZE_SCREEN_LOE_ADDCUSTOMBUTTON", "Adicionar um novo botão personalizado");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON", "Botão personalizado");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_HANDLE", "Qual manipulador será usado para referir-se a este botão?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_NEW", "Novo botão personalizado");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_BUTTONTEXT", "Que texto deve aparecer neste botão?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_MESSAGETEXT", "Que texto deve aparecer no alto da tela após este botão ser clicado?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_INLINE", "Este botão deve aparer uma vez em cada linha da lista de entradas?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_INLINE_DESC", "Se 'não', então o botão estará disponível nos modelos de Topo e de Base do formulário. Se 'sim', o botão aparecerá na lista ou estará disponível no Modelo de Lista, se houver um.");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO", "Quais entradas serão modificadas, quando este botão for clicado?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_INLINE", "Somente a entrada na linha onde o botão se encontra (somente funciona se o botão aparece em cada linha)");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_SELECTED", "Somente as entradas selecionadas (somente funciona se as caixas de verificação forem habilitadas acima)");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_ALL", "Todas as entradas neste formulário");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_CUSTOM_CODE", "Nenhum.  Executar código PHP personalizado quando este botão é clicado."); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_CUSTOM_HTML", "Nenhum.  Use PHP para retornar alguns HTMLs onde seria exibido neste botão."); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEW", "O botão deve criar uma nova entrada neste formulário");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEWPERSELECTED", "O botão deve criar uma nova entrada neste formulário para cada caixa de verificação que estiver marcada");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEW_OTHER", "O botão deve criar uma nova entrada no formulário '");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEWPERSELECTED_OTHER", "' para cada caixa de verificação que estiver marcada");
define("_AM_FORMULIZE_SCREEN_LOE_ADDCUSTOMBUTTON_EFFECT", "Adicionar um efeito para este botão");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_DELETE", "Excluir este botão");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT", "Efeito número");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_DESC", "Especifique o elemento que deve ser afetado, a ação que deve ser executada no elemento, e o valor a ser usado. O valor pode conter código PHP, incluindo <i>gatherHiddenValue('</i>manipulador<i>');</i> para recuperar o valor de um campo específico de uma entrada selecionada. Use os elementos ocultos abaixo para enviar esses valores. Para usar código PHP, a última linha do valor deve ser <i>\$value = \$algumacoisa;</i>");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_CUSTOM_CODE_DESC", "Introduza o código PHP que deverá ser executado quando este botão for clicado. Você pode usar a variável global \$formulize_thisEntryId para acessar o número do ID da entrada da linha em que o botão foi clicado, se o botão não aparecer em cada linha do código PHP nesta lista vai ser executado uma vez para cada opção que for verificada, e \$formulize_thisEntryId irá conter o ID de uma opção diferente para cada vez.  Se o botão não é por linha e nenhuma caixa de seleção foi marcada, em seguida, o código será executado uma vez e \$formulize_thisEntryId ficarão em branco.  Você pode usar <i>gatherHiddenValue('</i>handle<i>');</i> para recuperar o valor de um campo específico a partir de uma entrada selecionada.  Use elementos escondidos para enviar esses valores."); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_CUSTOM_HTML_DESC", "Introduza o código PHP que devem ser executadas para retornar este \"button\".  Isso é útil em conjunto com o \"appear on every line\" configuração, assim você pode inserir alguns códigos HTML em uma coluna da lista.  Use <i>display(\$entry, \$handle);</i> para incluir o valor de qualquer campo do formulário na entrada atual."); //GibaPhp 3.0
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_DELETE", "Excluir este efeito");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ELEMENT", "Afetar qual elemento?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION", "Executar qual ação?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_VALUE", "Usar qual valor?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION_REPLACE", "Substituir o valor atual pelo valor especificado");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION_REMOVE", "Remover o valor especificado do valor atual");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION_APPEND", "Acrescentar o valor especificado no final do valor atual");



?>