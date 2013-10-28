<?php
/**
* --------------------------------------------------------------------
*      Portuguese Translation by Paulo Sergio de Araujo alias Gopala
*                     gopala at harekrishna dot org dot br
*    http://www.x-trad.org the XOOPS Official Brazilian Translate Site            
* Translation version 3.0 by GibaPhp - http://br.impresscms.org   
* -------------------------------------------------------------------- 
*/
// Module main
define("_formulize_FORM_TITLE", "Formulários");
define("_AM_CATGENERAL", "Formulários Gerais");
define("_AM_NOFORMS_AVAIL", "Não há formulários atualmente disponíveis.");
//define("_formulize_MSG_SUBJECT", $xoopsConfig['sitename'].' - Formulário de Contato');
define("_formulize_MSG_SUBJECT", '['.$xoopsConfig['sitename'].'] -');
define("_formulize_MSG_FORM", ' Formulário: ');
//next two added by jwe 7/23/04
define("_formulize_INFO_RECEIVED", "Sua informação foi recebida.");
define("_formulize_NO_PERMISSION", "Você não tem permissão para ver este formulário.");
define("_formulize_NO_PERM", "Você não tem permissão para visualizar este formulário.");
define("_NO_PERM", "Você não tem permissão para visualizar essa parte do site.");
define("_formulize_MSG_SENT", "Sua informação foi enviada.");
define("_formulize_MSG_THANK", "<br />Obrigado.");
define("_formulize_MSG_SUP","<br />Os dados foram excluídos");
define("_formulize_MSG_BIG","O arquivo indicado é muito grande para ser enviado.");
define("_formulize_MSG_UNSENT","Por favor, envie um arquivo com tamanho menor que ");
define("_formulize_MSG_UNTYPE","Você não pode enviar este tipo de arquivo.<br>Os tipo autorizados são: ");

define("_formulize_NEWFORMADDED","Novo formulário adicionado com sucesso!");
define("_formulize_FORMMOD","Título do formulário modificado com sucesso!");
define("_formulize_FORMDEL","Formulário excluído com sucesso!");
define("_formulize_FORMCHARG","Carregando formulário");
define("_formulize_FORMSHOW","Resultados do Formulário: ");
define("_formulize_FORMTITRE","Parâmetros de envio do formulário foram modificados com sucesso!");
define("_formulize_NOTSHOW","Formulário: ");
define("_formulize_FORMCREA","Formulário criado com sucesso!");

define("_MD_ERRORTITLE","Erro! Você não indicou o título do formulário!!!!");
define("_MD_ERROREMAIL","Erro! Você não indicou um endereço de e-mail válido!!!!");
define("_MD_ERRORMAIL","Erro! Você não indicou o recipiente do formulário!!!!");

define("_FORM_ANON_USER","Alguém na Internet");

define("_FORM_ACT","Ações");
define("_FORM_CREAT","Criar um formulário");
define("_FORM_RENOM","Renomear um formulário");
define("_FORM_RENOM_IMG","<img src='../images/attach.png'>");
define("_FORM_SUP","Excluir um formulário");
define("_FORM_ADD","Modificar os ajusutes do formulário");
define("_FORM_SHOW","Consultar os resultados");
define("_FORM_TITLE","Título do formulário:");
define("_FORM_EMAIL","E-mail: ");
define("_FORM_ADMIN","Enviar somente para o administrador:");
define("_FORM_EXPE","Receber o formulário preenchido:");
define("_FORM_GROUP","Enviar para um grupo:");
define("_FORM_MODIF","Modificar um formulário");
define("_FORM_DELTITLE","Título do formulário que será excluído:");
define("_FORM_NEW","Novo formulário");
define("_FORM_TABLE_CREAT","Criar uma referência a uma tabela de dados"); //GibaPhp 3.0
define("_FORM_TABLE_NEW","Criar uma referência a uma tabela de dados"); //GibaPhp 3.0
define("_FORM_NOM","Entre com o nome novo do arquivo");
define("_FORM_OPT","Opções");
define("_FORM_MENU","Modificar entradas no bloco Menu de Formulários");
define("_FORM_PREF","Modificar as preferências");

define("_FORM_TABLE_CONNECTION", "Qual é o nome da tabela que deseja fazer uma referência no \"form\"?<br><br>Ela deve fazer parte da base de dados deste CMS.  Incluir o prefixo, ou seja: escreva o nome tabela inteira."); //GibaPhp 3.0

//next section added by jwe 7/25/07
define("_FORM_SINGLEENTRY","Este formulário permite somente uma entrada por usuário (preenche novamente o formulário atualiza a mesma entrada):");
define("_FORM_SINGLETYPE", "Quantas entradas são permitidas para este formulário?");
define("_FORM_SINGLE_GROUP", "Uma por grupo");
define("_FORM_SINGLE_ON", "Uma por usuário");
define("_FORM_SINGLE_MULTI", "Mais que uma por usuário");
define("_FORM_GROUPSCOPE","Entradas neste formulário são compartilhadas e visíveis a todos os usuários no mesmo grupo (e não apenas ao usuário que as criou):");
define("_FORM_HEADERLIST","Elementos do formulário exibidos na página 'Ver Entradas':");
define("_FORM_SHOWVIEWENTRIES","Usuários podem ver entradas feitas anteriormente neste formulário:");
define("_FORM_MAXENTRIES","Após um usuário ter prenchido o formulário estas tantas vezes, ele não mais poderá acessar o formulário novamente (0 significa sem limite):");
define("_FORM_DEFAULTADMIN","Grupos que têm direitos neste formulário:");

define("_FORM_COLOREVEN","Primeira cor alternada para escrita na página de relatório (as cores alternadas sobrescrevem as cores padrão para ajudar a distinguir um formulário de outro):");
define("_FORM_COLORODD","Segunda cor alternada para escrita na página de relatório:");


define("_FORM_MODIF","Modificar as questões do formulário");
define("_AM_FORM","Formulário: ");
define("_FORM_EXPORT","Exportar no formato CSV");
define("_FORM_ALT_EXPORT","Exportar");
define("_FORM_DROIT","Grupos autorizados a consultar o formulário");
define("_FORM_MODPERM","Modificar as permissões de acesso ao formulário");
define("_FORM_PERM","Permissões");

define("_FORM_MODCLONE", "Clonar este formulário");
define("_FORM_MODCLONEDATA", "Clonar este formulário e seus dados");
define("_FORM_MODCLONED_FORM", "Formulário clonado");

define("_FORM_MODPERMLINKS","Modificar o escopo das caixas de seleção linkadas (Depreciado -- agora, edite as propriedades em cada caixa de seleção)");
define("_FORM_PERMLINKS","Escopos das caixas de seleção linkadas");

define("_FORM_MODFRAME","Criar ou modificar um Framework de formulários");
define("_FORM_FRAME", "Frameworks");


// commented the line below since it's a duplicate of a line above --jwe 7/25/04
//define("_AM_FORM","Form : ");
define("_AM_FORM_SELECT","Selecione um formulário");
define("_MD_FILEERROR","Erro ao enviar o arquivo");
define("_AM_FORMUL","Formulários");

//added by jwe - 7/28/04
define("_AM_FORM_TITLE", "Permissões de acesso ao formulário"); // not used
define("_AM_FORM_CURPERM", "Permissões atuais:"); 
define("_AM_FORM_CURPERMLINKS", "Caixa de seleção linkada atual:"); 
define("_AM_FORM_PERMVIEW", "Ver");
define("_AM_FORM_PERMADD", "Adicionar/atualizar");
define("_AM_FORM_PERMADMIN", "Administrador");
define("_AM_FORM_SUBMITBUTTON", "Exibir nova permissão"); // not used

define("_AM_FORMLINK_PICK", "Escolha uma permissão");
define("_AM_CONFIRM_DEL", "Você está prestes a excluir este formulário! Todos o dados neste formulário serão excluídos também. Por favor, confirme.");

define("_AM_FRAME_NEW", "Criar um novo Framework:");
define("_AM_FRAME_NEWBUTTON", "Criar agora!");
define("_AM_FRAME_EDIT", "Modificar um framework existente:");
define("_AM_FRAME_NONE", "Não exitem frameworks");
define("_AM_FRAME_CHOOSE", "Escolha um Framework");
define("_AM_FRAME_TYPENEWNAME", "Digite o novo nome aqui");
define("_AM_CONFIRM_DEL_FF_FORM", "Você está prestes a remover este conjunto de formulários do framework! Por favor, confirme.");
define("_AM_CONFIRM_DEL_FF_FRAME", "Você está prestes a excluir este framework! Por favor, confirme.");
define("_AM_FRAME_NAMEOF", "Nome do framework:");
define("_AM_FRAME_ADDFORM", "Adicionar um par de formulários a este framework:");
define("_AM_FRAME_FORMSIN", "Formulários neste framework: (clique no nome do formulário para editar seus detalhes)");
define("_AM_FRAME_DELFORM", "Remover");
define("_AM_FRAME_EDITFORM", "Detalhes do:");
define("_AM_FRAME_DONEBUTTON", "Feito");
define("_AM_FRAME_NOFORMS", "Não há formulários neste framework");
define("_AM_FRAME_AVAILFORMS1", "Formulário um:");
define("_AM_FRAME_AVAILFORMS2", "Formulário dois:");
define("_AM_FRAME_DELETE", "Excluir um framework existente:");
define("_AM_FRAME_SUBFORM_OF", "Torná-lo um subformulário de:");
define("_AM_FRAME_NOPARENTS", "Nenhum formulário no Framework"); 
define("_AM_FRAME_TYPENEWFORMNAME", "Digite um nome curto aqui");
define("_AM_FRAME_NEWFORMBUTTON", "Adicionar formulário!");
define("_AM_FRAME_NOKEY", "Nenhum especificado!");
define("_AM_FRAME_FORMNAMEPROMPT", "Nome para este formulário neste framework:");
define("_AM_FRAME_RELATIONSHIP", "Relacionamento:");
define("_AM_FRAME_ONETOONE", "Um para um");
define("_AM_FRAME_ONETOMANY", "Um para muitos");
define("_AM_FRAME_MANYTOONE", "Muitos para um");
define("_AM_FRAME_LINKAGE", "Ligação entre estes formulários:");
define("_AM_FRAME_DISPLAY", "Exibir estes formulários como se fossem um único?");
define("_AM_FRAME_UIDLINK", "User ID da pessoa que preencheu-os");
define("_AM_FRAME_UPDATEBUTTON", "Atualize este framework com estas definições");
define("_AM_FRAME_UPDATEFORMBUTTON", "Atualize este formulário com estes manipuladores");
define("_AM_FRAME_UPDATEANDGO", "Atualize e retorne à página anterior");

//common value language constants added July 19 2006 -- jwe
define("_AM_FRAME_COMMONLINK", "Valor comum em dois elementos [selecione os elementos]");
define("_AM_FRAME_WHICH_ELEMENTS", "Choose the two elements that are meant to have common values");
define("_AM_FRAME_SELECT_COMMON", "Elemento Comum para ");
define("_AM_FRAME_COMMON_VALUES", "Valores comuns em: ");
define("_AM_FRAME_COMMON_WARNING", "<b>NOTA IMPORTANTE:</b> Frameworks que usam 'valores comuns' são ainda experimentais.  Nem todos os fatores dos frameworks são suportados em frameworks que utilizam este ajuste. Atualmente, este ajuste só é reconhecido pela função getData, de modo que você pode pegar (get) resultados de consultas neste framework. Portanto, exibição unificada de formulários, subformulários, e uso de displayForm para apresentar todas as colunas no framework inteiro, não são atualmente suportados.");

define("_AM_FRAME_FORMHANDLE", "Manipulador para este formulário:");
define("_AM_FRAME_FORMELEMENTS", "Elementos neste formulário");
define("_AM_FRAME_ELEMENT_CAPTIONS", "Etiquetas");
define("_AM_FRAME_ELEMENT_HANDLES", "Manipuladores");
define("_AM_FRAME_HANDLESHELP", "Use esta página para especificar <i>Manipuladores</i> para este formulário e seus elementos. Manipuladores são nomes curtos que podem ser usados para referenciar este formulário e seus elementos de fora do módulo Formulize.");

define("_AM_SELECT_PROXY", "Esta informação enviada é parte de algo mais?");

define("_FORM_EXP_CREE","O arquivo foi exportado com sucesso");

//template constants added by jwe 7/24/04
define("_formulize_TEMP_ADDENTRY", "ADICIONAR UMA ENTRADA");
define("_formulize_TEMP_VIEWENTRIES", "VER ENTRADAS");
define("_formulize_TEMP_ADDINGENTRY", "ADICIONANDO UMA ENTRADA");
define("_formulize_TEMP_VIEWINGENTRIES", "VENDO ENTRADAS");
define("_formulize_TEMP_SELENTTITLE", "Suas entradas em '");
define("_formulize_TEMP_SELENTTITLE_GS", "Todas as entradas em '");
define("_formulize_TEMP_SELENTTITLE_RP", "Buscar resultados para '");
define("_formulize_TEMP_SELENTTITLE2_RP", "Calcular resultados para '");
define("_formulize_TEMP_VIEWTHISENTRY", "Ver esta entrada");
define("_formulize_TEMP_EDITINGENTRY", "EDITANDO UMA ENTRADA");
define("_formulize_TEMP_NOENTRIES", "Nenhuma entrada.");
define("_formulize_TEMP_ENTEREDBY", "Entrada por: ");
define("_formulize_TEMP_ENTEREDBYSINGLE", "Entrada ");
define("_formulize_TEMP_ON", "em");
define("_formulize_TEMP_AT", "em"); //GibaPhp 3.0
define("_formulize_TEMP_QYES", "Sim");
define("_formulize_TEMP_QNO", "Não");
define("_formulize_REPORT_ON", "Ativar Modo de Escrita de Relatório");
define("_formulize_REPORT_OFF", "Desativar Modo de escrita de Relatório");
define("_formulize_VIEWAVAILREPORTS", "Ver Relatório:");
define("_formulize_NOREPORTSAVAIL", "Visão Padrão");
define("_formulize_CHOOSEREPORT", "Visão Padrão");
define("_formulize_REPORTING_OPTION", "Opções de relatório");
define("_formulize_SUBMITTEXT", "Aplicar");
define("_formulize_RESETBUTTON", "LIMPAR"); //GibaPhp 3.0
define("_formulize_QUERYCONTROLS", "Controles de Consulta");
define("_formulize_SEARCH_TERMS", "Termos para pesquisar:");
define("_formulize_STERMS", "Termos:");
define("_formulize_AND", "E");
define("_formulize_OR", "OU");
define("_formulize_SEARCH_OPERATOR", "Operador:");
define("_formulize_NOT", "NÂO");
define("_formulize_LIKE", "COMO");
define("_formulize_NOTLIKE", "NÃO COMO");
define("_formulize_CALCULATIONS", "Cálculos:");
define("_formulize_SUM", "Soma");
define("_formulize_SUM_TEXT", "Total de todos os valores na coluna:");
define("_formulize_AVERAGE", "Média");
define("_formulize_AVERAGE_INCLBLANKS", "Valor médio na coluna:");
define("_formulize_AVERAGE_EXCLBLANKS", "Valor médio excluindo brancos e zeros:");
define("_formulize_MINIMUM", "Mínimo");
define("_formulize_MINIMUM_INCLBLANKS", "Valor mínimo na coluna:");
define("_formulize_MINIMUM_EXCLBLANKS", "Valor mínimo excluindo brancos e zeros:");
define("_formulize_MAXIMUM", "Máximo");
define("_formulize_MAXIMUM_TEXT", "Valor máximo na coluna:");
define("_formulize_COUNT", "Contagem");
define("_formulize_COUNT_INCLBLANKS", "Quantidade de valores na coluna:");
define("_formulize_COUNT_ENTRIES", "Quantidade de entradas na coluna:");
define("_formulize_COUNT_NONBLANKS", "Quantidade de entradas não-branco, não-zero na coluna:");
define("_formulize_COUNT_EXCLBLANKS", "Quantidade de valores não-branco, não-zero na coluna:");
define("_formulize_COUNT_PERCENTBLANKS", "Percentual de valores não-branco, não-zero:");
define("_formulize_COUNT_UNIQUES", "Quantidade de valores únicos na coluna:");
define("_formulize_COUNT_UNIQUEUSERS", "Número de usuários que fizeram entradas na coluna:");
define("_formulize_PERCENTAGES", "Percentuais");
define("_formulize_PERCENTAGES_VALUE", "Valor:");
define("_formulize_PERCENTAGES_COUNT", "Contagem:");
define("_formulize_PERCENTAGES_PERCENT", "% do total:");
define("_formulize_PERCENTAGES_PERCENTEXCL", "% excluindo os brancos:");
define("_formulize_SORTING_ORDER", "Ordem de classificação:");
define("_formulize_SORT_PRIORITY", "Prioridade de classificação:");
define("_formulize_NONE", "Nenhuma");
define("_formulize_CHANGE_COLUMNS", "Mudar a visualização de diferentes colunas:");
define("_formulize_CHANGE", "Mudar");
define("_formulize_SEARCH_HELP", "Se você especificar termos de busca em mais de uma coluna, o ajuste E/OU entre os campos determina se deve ser feita a busca por entradas coincidentes em todas as colunas (E), ou ao menos em uma coluna (OU).<br><br>A opção E/OU abaixo das caixas de termos determina se deve ser feita a busca por entradas que contenham todos os termos (E), ou ao menos um dos termos (OU).<br><br>Use vírgulas para separar os termos. Use [,] para especificar uma vírgula dentro de um termo.");
define("_formulize_SORT_HELP", "Você pode classificar por qualquer elemento, exceto aqueles que aceitam múltiplas opções, tais como caixas de verificação.");
define("_formulize_REPORTSCOPE", "Selecione o escopo do relatório:");
define("_formulize_SELECTSCOPEBUTTON", "Selecionar");
define("_formulize_GROUPSCOPE", "Grupo: ");
define("_formulize_USERSCOPE", "Usuário: ");
define("_formulize_GOREPORT", "Vai");
define("_formulize_REPORTSAVING", "Salvar esta consulta como um de seus relatórios:");
define("_formulize_SAVEREPORTBUTTON", "Salvar");
define("_formulize_REPORTNAME", "Nome do relatório:");
define("_formulize_ANDORTITLE", "Ajuste E/OU intercampos:");

define("_formulize_SHOWCALCONLY", "Mostrar somente os cálculos (não uma lista de entradas)");

define("_formulize_PUBLISHINGOPTIONS", "Opções de publicação:");
define("_formulize_PUBLISHREPORT", "Publicar este relatório para outros usuários.");
define("_formulize_PUBLISHNOVE", "Remover do relatório os links 'Ver esta Entrada' (assim, os usuários não poderão ver os detalhes completos de cada entrada).");
define("_formulize_PUBLISHCALCONLY", "Remover inteiramente a lista de entradas, e mostrar somente os cálculos agregados.");


define("_formulize_LOCKSCOPE", "<b>Salvar relatório com escopo atual travado</b> (de forma que os vizualizadores dele estarão limitados a seu escopo padrão).");
define("_formulize_REPORTPUBGROUPS", "Selecione os grupos para publicação:");
define("_formulize_REPORTDELETE", "Excluir o relatório atualmente selecionado:");
define("_formulize_DELETE", "Excluir");
define("_formulize_DELETECONFIRM", "Marque esta caixa e pressione o botão para excluir ");

define("_formulize_REPORTEXPORTING", "Exporte esta consulta como um arquivo de planilha eletrônica:");
define("_formulize_EXPORTREPORTBUTTON", "Exportar");
define("_formulize_EXPORTEXPLANATION", "Clique no botão <b>Exportar</b> para baixar um arquivo que pode ser lido como uma planilha eletrônica, contendo os resultados da consulta atual. Note que você pode especificar uma delimitador para separar os campos. Se o caractere delimitador que você escolher estiver presente em seus resultados, então a planilha não abrirá corretamente. Tente, então, exportar com um delimitador diferente.");
define("_formulize_FILEDELTITLE", "Delimitador de campo:");
define("_formulize_FDCOMMA", "Vírgula");
define("_formulize_FDTAB", "Tabulação");
define("_formulize_FDCUSTOM", "Personalizado");
define("_formulize_exfile", "exported_data_");
define("_formulize_DLTEXT", "<b>Clique com o botão direito no link abaixo e selecione <i>Salvar</i>.</b> (Ctrl-click em um MAC.)  Uma vez o arquivo em seu computador, você será capaz de abrí-lo em um programa de planilha eletrônica. Se os campos não alinharem corretamente quando você abrir o arquivo, tente exportar com um delimitador diferente.");
define("_formulize_DLHEADER", "Seu arquivo está pronto para download.");

define("_formulize_PICKAPROXY", "Nenhum servidor Proxy selecionado");
define("_formulize_PROXYFLAG", "(Proxy)");

define("_formulize_DELBUTTON", "Excluir");
define("_formulize_DELCONF", "Você está prestes a excluir uma entrada! Por favor, confirme.");

define("_CONTINUE", "Continue na próxima parte");
define("_SAVE_AND_GO_BACK", "Envie e retorne ao formulário principal");
define("_formulize_RELENTRIES", "Entradas relacionadas:");
define("_formulize_SUBFORM_MESSAGE", "Continuando na próxima parte do formulário...");
define("_formulize_SUBFORM_RETURN", "Retornando ao formulário principal...");
define("_formulize_ADDNEW_SUBFORM", "Adicionando uma nova entrada");

define("_formulize_GROUPS", "Grupos");

define("_AM_MODIFY_MULTI","Permissões para múltiplos grupos");
define("_AM_MULTI_PERMISSIONS","Modificar permissões para estes grupos");
define("_AM_MULTI_CREATION_ORDER","Ordem de criação");
define("_AM_MULTI_ALPHABETICAL_ORDER","Ordem alfabética");
define("_AM_MULTI_GROUP_LISTS","Listar grupos salvos");
define("_AM_MULTI_GROUP_LISTS_NOSELECT","Nenhuma lista de grupos selecionada");
define("_AM_MULTI_SAVE_LIST","Salvar lista");
define("_AM_MULTI_DELETE_LIST","Excluir lista");
define("_AM_MULTI_MODIFICATION","Tipo de modificação");
define("_AM_MULTI_ADD_PERMISSIONS","Adicionar permissões");
define("_AM_MULTI_REMOVE_PERMISSIONS","Remover permissões");
define("_AM_MULTI_GROUP_LIST_NAME","Entre com um nome de lista de grupos");
define("_AM_MULTI_GROUP_LIST_DELETE","Você tem certeza de que quer excluir o item?");

define("_formulize_FORM_LIST", "Modificar permissões para estes formulários");
define("_formulize_SHOW_PERMS", "Mostrar essas permissões");
define("_formulize_SAME_PERMS", "Definir as mesmas permissões para todos os grupos selecionados?");
define("_formulize_SAME_PERMS_TEXT", "Escolha as permissões que você quer aplicar a todos esses grupos.<br>Cuidado! Quaisquer permissões já existentes serão substituidas pelas que você escolher aqui!<br>Clique no botão abaixo 'Retornar à página principal' para cancelar esta operação.<br><br>Os grupos que você selecionou:");
define("_formulize_MODFORM_TITLE", "Escolha os grupos e formulários para os quais você quer mudar as permissões:");
define("_formulize_MODPERM_TITLE", "Modificar as permissões:");

define("_formulize_FD_ABOUT", "Sobre esta entrada:");
define("_formulize_FD_CREATED", "Criada por: ");
define("_formulize_FD_MODIFIED", "Modificada por: ");
define("_formulize_FD_NEWENTRY", "Esta é uma nova entrada que ainda não foi salva.");

define("_formulize_ADD", "Adicionar");
define("_formulize_ADD_ONE", "Adicionar um");
define("_formulize_ADD_ENTRIES", "entradas");
define("_formulize_DELETE_CHECKED", "Excluir itens marcados");
define("_formulize_ADD_HELP", "Clique no botão <i>Adicionar</i> para adicionar uma entrada a esta seção.");
define("_formulize_ADD_HELP2", "Clique no botão <i>Ver</i> para visualizar uma entrada completa.");
define("_formulize_ADD_HELP3", "Atualize uma entrada mudando os valores a direita.");
define("_formulize_ADD_HELP4", "Para excluir entradas, marque as caixas correspondentes e clique no botão abaixo.");
define("_formulize_SUBFORM_VIEW", "Ver");
define("_formulize_SAVE", "Salvar");
define("_formulize_DONE", "Tudo pronto");
define("_formulize_CONFIRMNOSAVE", "Você não salvou suas alterações! Isso está certo? Clique em 'Cancelar' para retornar ao formulário e clique em 'Salvar' para salvar suas alterações.");

define("_formulize_INFO_SAVED", "Sua informação foi salva.");
define("_formulize_INFO_DONE1", "Clique no botão <i>");
define("_formulize_INFO_DONE2", "</i> se você já terminou.");
define("_formulize_INFO_CONTINUE1", "Você pode atualizar sua informação abaixo.");
define("_formulize_INFO_CONTINUE2", "Você pode criar uma nova entrada preenchendo novamente o formulário.");
define("_formulize_INFO_SAVEBUTTON", "Clique no botão <i>" . _formulize_SAVE . "</i> para salvar sual alterações.");
define("_formulize_INFO_SAVE1", "Clique no botão <i>");
define("_formulize_INFO_SAVE2", "</i> para salvar suas alterações.");
define("_formulize_INFO_NOSAVE", "Você pode rever esta entrada, mas <i>não pode salvar alterações</i>.");
define("_formulize_INFO_MAKENEW", "Você pode criar uma nova entrada preenchendo o formulário abaixo.");

define("_formulize_NOSUBNAME", "Entrada: ");

define("_formulize_DEL_ENTRIES", "Você está prestes a excluir as entradas selecionadas! Por favor, confirme.");

define("_formulize_PRINTVIEW", "Visão imprimível");
define("_formulize_PRINTALLVIEW", "Visão imprimível - todas as páginas"); // nmc 2007.03.24 - added

// constants related to the new display entries functions...

define("_formulize_DE_CURRENT_VIEW", "Visão corrente: ");
define("_formulize_DE_FILLINFORM", "Preencher este formulário: ");
define("_formulize_DE_ACTIONS", "Ações: ");
define("_formulize_DE_NODATAFOUND", "Não foram encontradas entradas na visão atual coincidentes com os atuais termos de busca (se indicados).");
define("_formulize_DE_STANDARD_VIEWS", "VISÕES PADRONIZADAS:"); //revisão
define("_formulize_DE_NO_STANDARD_VIEWS", "Não há visões padrão disponíveis");
define("_formulize_DE_SAVED_VIEWS", "SUAS VISÕES SALVAS:");
define("_formulize_DE_PUB_VIEWS", "VISÕES PUBLICADAS:");
define("_formulize_DE_SEARCH_HELP", "Digite aqui os termos de busca");
define("_formulize_DE_WARNLOCK", "<p>A visão que você selecionou está ajustada para <i>controles travados</i>. Isto significa que você não pode mudar as colunas, fazer cálculos, buscas avançadas ou exportar dados.</p><p>Você pode classificar e fazer buscas simples usando os controles no topo de cada coluna.</p>");
define("_formulize_DE_MINE", "Minhas entradas");
define("_formulize_DE_GROUP", "Entradas de todos os usuários em meu grupo(s)");
define("_formulize_DE_ALL", "Entradas de todos os usuários em todos os grupos");
define("_formulize_DE_GO", "Aplicar termos de busca");
define("_formulize_DE_CHANGECOLS", "Mudar colunas");
define("_formulize_DE_PICKNEWCOLS", "Pegar colunas diferentes para ver");
define("_formulize_DE_AVAILCOLS", "Colunas disponíveis:");
define("_formulize_DE_LASTMOD", "Última modificação por");
define("_formulize_DE_CREATED", "Criado por");
define("_formulize_DE_ON", "em");
define("_formulize_DE_VIEWDETAILS", "Clique para ver os detalhes desta entrada.");
define("_formulize_DE_RESETVIEW", "Restaurar a visão atual");
define("_formulize_DE_CALCS", "Cálculos");
define("_formulize_DE_ADVCALCS", "Procedures");
define("_formulize_DE_EXPORT", "Exportar entradas");
define("_formulize_DE_EXPORT_CALCS", "Exportar cálculos");
define("_formulize_DE_SAVE", "Salvar a visão atual");
define("_formulize_DE_DELETE", "Excluir a visão atual");
define("_formulize_DE_ADDENTRY", "Adicionar uma entrada");
define("_formulize_DE_ADD_MULTIPLE_ENTRY", "Adicionar múltiplas entradas");
define("_formulize_DE_PROXYENTRY", "Fazer uma entrada proxy");
define("_formulize_DE_UPDATEENTRY", "Atualizar sua entrada");
define("_formulize_DE_DELETESEL", "Excluir a selecionada");
define("_formulize_DE_CLONESEL", "Clonar a selecionada");
define("_formulize_DE_CLONE_PROMPT", "Quantas cópias das entradas selecionadas você quer fazer? (use números, não palavras)");
define("_formulize_DE_SELALL", "Selecionar todas as entradas");
define("_formulize_DE_CLEARALL", "Limpar seleção");
define("_formulize_DE_CONFIRMDEL", "Você está prestes a excluir as entradas selecionadas. Por favor, confirme!");
define("_formulize_DE_DELBOXDESC", "Clique nesta caixa para selecionar/não selecionar esta entrada.");
define("_formulize_DE_CHOOSE_EXPORT", "Escolha o formato de exportação que você quer");
define("_formulize_DE_EXPORT_INST", "Escolha o formato no qual você quer exportar seus dados. Separação por vírgula deve funcionar com todos os dados. Entretanto, se você tem combinações especialmente complexas de marcas de quota e vírgulas dentro dos próprios dados, e seus arquivos exportados não estão formatando corretamente, você pode tentar um delimitador alternativo.");
define("_formulize_DE_XCOMMA", "Separado por vírgula");
define("_formulize_DE_XTAB", "Separado por marca de tabulação");
define("_formulize_DE_XCUST", "Personalizado:");
define("_formulize_DE_XF", "exported_");
define("_formulize_DE_EXPORTCALC_TITLE", "Resultados dos cálculos para: ");
define("_formulize_DE_CLICKSAVE", "Clique com o botão direito para baixar uma cópia de seus dados.");
define("_formulize_DE_CANCELCALCS", "Cancelar cálculos");
define("_formulize_DE_SHOWLIST", "Alternar para entradas");
define("_formulize_DE_HIDELIST", "Alternar para cálculos");
define("_formulize_DE_SORTTHISCOL", "Clique para classificar as entradas por esta coluna");
define("_formulize_DE_MOREINFO", "Clique para obter mais informações sobre esta coluna"); //GibaPhp 3.0
define("_formulize_DE_MOREINFO_TITLE", "Mais informação sobre esta o elemento deste formulário"); //GibaPhp 3.0
define("_formulize_DE_MOREINFO_QUESTION", "Texto para esta pergunta como ele aparece no formulário:"); //GibaPhp 3.0
define("_formulize_DE_MOREINFO_OPTIONS", "Opções para responder a esta pergunta:"); //GibaPhp 3.0

define("_formulize_DE_DELETE_ALERT", "Você não está autorizado a excluir esta visão da lista.");
define("_formulize_DE_CONF_DELVIEW", "Você está prestes a excluir esta visão! Por favor, confirme.");

//calculations
define("_formulize_DE_PICKCALCS", "Escolha os cálculos que você quer");
define("_formulize_DE_MODCALCS", "Modificar cálculos");
define("_formulize_DE_CALC_COL", "Coluna(s) para usar nos cálculos:");
define("_formulize_DE_CALCSUB", "Adicione o(s) cálculo(s) à lista");
define("_formulize_DE_CALC_CALCS", "Cálculos a efetuar na(s) coluna(s):");
define("_formulize_DE_CALCGO", "Executar os cálculos solicitados");
define("_formulize_DE_REQDCALCS", "Solicitar cálculos:");
define("_formulize_DE_CALCALL", "Incluir brancos/zeros"); 
define("_formulize_DE_CALCNOBLANKS", "Excluir brancos/zeros");
define("_formulize_DE_CALCONLYBLANKS", "Incluir somente brancos/zeros");
define("_formulize_DE_CALCJUSTNOBLANKS", "Excluir brancos"); //GibaPhp 3.0
define("_formulize_DE_CALCJUSTNOZEROS", "Excluir zeros"); //GibaPhp 3.0
define("_formulize_DE_CALCCUSTOM", "Excluir lista personalizada:"); //GibaPhp 3.0
define("_formulize_DE_CALC_GROUPING", "Agrupar resultados por...");
define("_formulize_DE_NOGROUPING", "Não agrupe os resultados");
define("_formulize_DE_GROUPBYCREATOR", "Agrupar por: usuários que fizeram entradas");
define("_formulize_DE_GROUPBYCREATEDATE", "Agrupar por: data da criação");
define("_formulize_DE_GROUPBYMODIFIER", "Agrupar por: usuários que modificaram por último as entradas");
define("_formulize_DE_GROUPBYMODDATE", "Agrupar por: data da última modificação");
define("_formulize_DE_GROUPBYCREATOREMAIL", "Agrupar por: endereço de e-mail do criador");
define("_formulize_DE_CALC_LISTDISPLAY", "Exibir somente os cálculos<br>(ocultar a lista de entradas)");
define("_formulize_DE_CALC_CREATOR", "Usuários que fizeram entradas");
define("_formulize_DE_CALC_CREATEDATE", "Data da criação");
define("_formulize_DE_CALC_MODIFIER", "Usuário que modificou a entrada por último");
define("_formulize_DE_CALC_MODDATE", "Data da última modificação");
define("_formulize_DE_CALC_CREATOR_EMAIL", "Endereço de e-mail do criador");
define("_formulize_DE_REMOVECALC", "Remover este cálculo da lista");
define("_formulize_DE_CALC_BTEXT", "Quais entradas?");
define("_formulize_DE_CALC_GTEXT", "Agrupar resultados?");
define("_formulize_DE_CALC_GTEXT2", "Agrupamento de 2º nível?");
define("_formulize_DE_CALCHEAD", "Resultados dos cálculos");
define("_formulize_DE_CALC_SUM", "Soma Total");
define("_formulize_DE_CALC_AVG", "Média");
define("_formulize_DE_CALC_MIN", "Valor mínimo");
define("_formulize_DE_CALC_MAX", "Valor máximo");
define("_formulize_DE_CALC_COUNT", "Contagem de entradas");
define("_formulize_DE_CALC_PER", "Repartição percentual");
define("_formulize_DE_EXCLBLANKS", "Excluir brancos/zeros");
define("_formulize_DE_INCLBLANKS", "Incluir brancos/zeros");
define("_formulize_DE_INCLONLYBLANKS", "Incluir <i>somente</i> brancos/zeros");
define("_formulize_DE_EXCLONLYBLANKS", "Exclui os espaços em branco"); //GibaPhp 3.0
define("_formulize_DE_EXCLONLYZEROS", "Exclui zeros"); //GibaPhp 3.0
define("_formulize_DE_EXCLCUSTOM", "Excluir esses itens: "); //GibaPhp 3.0
define("_formulize_DE_CALC_MEAN", "Tendência");
define("_formulize_DE_CALC_STD", "Desvio Padrão"); //GibaPhp 3.0
define("_formulize_DE_CALC_MEDIAN", "Mediana");
define("_formulize_DE_CALC_MEDIAN25", "25 '%' Porcento"); //GibaPhp 3.0
define("_formulize_DE_CALC_MEDIAN75", "75 '%' Porcento"); //GibaPhp 3.0
define("_formulize_DE_CALC_MODE", "Moda");
define("_formulize_DE_CALC_NO25OR75", "Não o suficiente para calcular esses valores"); //GibaPhp 3.0
define("_formulize_DE_CALC_NUMENTRIES", "Número de entradas");
define("_formulize_DE_CALC_NUMUNIQUE", "Número de valores únicos");
define("_formulize_DE_PER_ITEM", "Ítem");
define("_formulize_DE_PER_COUNT", "Contagem");
define("_formulize_DE_PER_PERCENT", "Percentual");
define("_formulize_DE_PER_PERCENTRESPONSES", "Como % de respostas");
define("_formulize_DE_PER_PERCENTENTRIES", "Como % de entradas");
define("_formulize_DE_PER_TOTAL", "TOTAL");
define("_formulize_DE_PER_TOTALRESPONSES", "respostas");
define("_formulize_DE_PER_TOTALENTRIES", "entradas");
define("_formulize_DE_PER_RESPONSESPERENTRY", "resposta(s) / entrada");
define("_formulize_DE_DATAHEADING", "Lista de entradas");

//ADVANCED SEARCH:
define("_formulize_DE_BUILDQUERY", "Construa sua consulta");
define("_formulize_DE_AS_FIELD", "Buscar neste(s) campo(s):");
define("_formulize_DE_AS_MULTI_AND", "use 'E' entre múltiplos campos");
define("_formulize_DE_AS_MULTI_OR", "use 'OU' entre múltiplos campos");
define("_formulize_DE_AS_OPTERM", "Use este operador e termo:");
define("_formulize_DE_AS_ADD", "Adicione esta busca à consulta");
define("_formulize_DE_AS_ADDOTHER", "Outros itens que você pode adicionar:");
define("_formulize_DE_AS_REMOVE", "Remover o último item da consulta");
define("_formulize_DE_ADVSEARCH", "Busca avançada");
define("_formulize_DE_ADVSEARCH_ERROR", "Havia um \"parse error\" na busca avançada que você especificou. Frequentemente isso é causado por não ter um 'E' ou um 'OU' entre dois termos de busca. Outra causa comum são os parenteses '()' arranjados incorretamente ou número desigual de abre-parenteses '(' e fecha-parenteses ')'.");
define("_formulize_DE_SEARCHGO", "Executar a consulta solicitada");
define("_formulize_DE_AS_QUERYSOFAR", "Consulta solicitada muito longa:");
define("_formulize_DE_CANCELASEARCH", "Cancelar esta busca");
define("_formulize_DE_MOD_ADVSEARCH", "Modificar busca");

//CHANGE SCOPE:
define("_formulize_DE_ADVSCOPE", "Escopo avançado");
define("_formulize_DE_PICKASCOPE", "Escolha os grupos para usar neste escopo");
define("_formulize_DE_AVAILGROUPS", "Grupos disponíveis:");
define("_formulize_DE_USETHISSCOPE", "Use estes grupos por escopo");
define("_formulize_DE_AS_ENTRIESBY", "Entradas por: ");
define("_formulize_DE_AS_PICKGROUPS", "Entradas por todos os usuários em...[escolha os grupos]");
define("_formulize_DE_PICKDIFFGROUP", "Escolha diferentes grupos");
define("_formulize_DE_NOGROUPSPICKED", "Por favor, clique em um ou mais grupos da lista abaixo Use Ctrl-clique para selecionar mais que um grupo.");


//SAVE VIEW:
define("_formulize_DE_SAVEVIEW", "Opções para salvar esta visão");
define("_formulize_DE_SAVE_UPDATE", "Atualizar: ");
define("_formulize_DE_SAVE_REPLACE", "Substituir: ");
define("_formulize_DE_SAVE_LASTLOADED", "visão carregada mais recentemente");
define("_formulize_DE_SAVE_AS", "[Salvar uma nova visão]");
define("_formulize_DE_SAVE_USECURRENT", "Use os ajustes atuais da visão para...");
define("_formulize_DE_SAVE_SCOPE", "Quando esta visão for selecionada, mostrar somente as entradas feitas por...");
// this help line not used at the moment
define("_formulize_DE_SAVE_SCOPE_HELP", "Para selecionar grupos específicos, feche esta janela/aba e mude a opção (escolha os grupos) para a Visão Atual. Então clique novamente no botão <i>Salvar</i>.");
define("_formulize_DE_SAVE_SCOPE1", "A pessoa vendo ele, e mais ninguém");
define("_formulize_DE_SAVE_SCOPE2", "Todos nos grupos com permissão para visualização");
define("_formulize_DE_SAVE_SCOPE3", "Todos em todos os grupos (sem limites)");
define("_formulize_DE_SAVE_SCOPE4", "Todos em: ");
define("_formulize_DE_SAVE_SCOPE1_SELF", "Eu");
define("_formulize_DE_SAVE_SCOPE2_SELF", "Todos em meus grupos");
define("_formulize_DE_SAVE_SCOPE3_SELF", "Todos em todos os grupos (sem limites)");
define("_formulize_DE_SAVE_SCOPE4_SELF", "Todos em: ");
define("_formulize_DE_SAVE_NOSPECIFICS", "[nenhum grupo específico selecionado]");
define("_formulize_DE_SAVE_PUBGROUPS", "Publicar esta visão para estes grupos");
define("_formulize_DE_SAVE_NOPUB", "[Não publicar esta visão]");
define("_formulize_DE_SAVE_LOCKCONTROLS", "Travar os controles?");
define("_formulize_DE_SAVE_LOCKCONTROLS_HELP1", "<span style=\"font-weight: bold;\">Sobre o travamento de controles:</span>");
define("_formulize_DE_SAVE_LOCKCONTROLS_HELP2", "<span style=\"font-weight: normal;\">Certas ações, tais como buscas avançadas, cálculos e mudanças de colunas, podem revelar mais informação para o visualizador do que o apresentado por padrão. Quando os controles estão travados, e esta visão é selecionada por usuários que, de outra forma, não tem acesso a estas entradas, então todas as ações que podem revelar mais informação são desativadas. Travar os controles não tem efeito em usuários que podem, normalmente, por eles mesmos, ver todos os detalhes.</span>");
define("_formulize_DE_SAVE_BUTTON", "Salve os ajustes para a visão atual com estas opções");
define("_formulize_DE_SAVE_NEWPROMPT", "Por favor, digite um nome para esta nova visão:");

//IMPORT
define("_formulize_DE_IMPORTDATA", "Importar Entradas");

// CALENDAR
define("_formulize_CAL_ADD_ITEM", "Clique para adicionar um novo item neste dia.");
define("_formulize_CAL_RETURNFROMMULTI", "Retorne ao Calendário");

define("_formulize_CAL_MONTH_01", "Janeiro");
define("_formulize_CAL_MONTH_02", "Fevereiro");
define("_formulize_CAL_MONTH_03", "Março");
define("_formulize_CAL_MONTH_04", "Abril");
define("_formulize_CAL_MONTH_05", "Maio");
define("_formulize_CAL_MONTH_06", "Junho");
define("_formulize_CAL_MONTH_07", "Julho");
define("_formulize_CAL_MONTH_08", "Agosto");
define("_formulize_CAL_MONTH_09", "Setembro");
define("_formulize_CAL_MONTH_10", "Outubro");
define("_formulize_CAL_MONTH_11", "Novembro");
define("_formulize_CAL_MONTH_12", "Dezembro");

define("_formulize_CAL_WEEK_1", "Domingo");
define("_formulize_CAL_WEEK_2", "Segunda");
define("_formulize_CAL_WEEK_3", "Terça");
define("_formulize_CAL_WEEK_4", "Quarta");
define("_formulize_CAL_WEEK_5", "Quinta");
define("_formulize_CAL_WEEK_6", "Sexta");
define("_formulize_CAL_WEEK_7", "Sábado");
define("_formulize_CAL_WEEK_1_3ABRV", "Dom");
define("_formulize_CAL_WEEK_2_3ABRV", "Seg");
define("_formulize_CAL_WEEK_3_3ABRV", "Ter");
define("_formulize_CAL_WEEK_4_3ABRV", "Qua");
define("_formulize_CAL_WEEK_5_3ABRV", "Qui");
define("_formulize_CAL_WEEK_6_3ABRV", "Sex");
define("_formulize_CAL_WEEK_7_3ABRV", "Sab");

// account creation
define("_formulize_ACTDETAILS", "Detalhes da Conta:");
define("_formulize_PERSONALDETAILS", "Detalhes Pessoais:");
define("_formulize_TYPEPASSTWICE_NEW", "(Digite sua senha duas vezes. Deve ter ao menos ");
define("_formulize_TYPEPASSTWICE_CHANGE", "(Para mudar sua senha, digite uma nova senha duas vezes. Deve ter ao menos ");
define("_formulize_CDISPLAYMODE", "Sua maneira padrão de exibir comentários/postagens");
define("_formulize_CSORTORDER", "Sua ordenação padrão para os comentários/postagens");
define("_formulize_CREATEACT", "Criar minha Conta!");
define("_formulize_ACTCREATED", "Sua conta foi criada e você está sendo logado no site agora.");
define("_formulize_USERNAME_HELP1", " (Não pode conter espaços. Deve ter entre ");
define("_formulize_USERNAME_HELP2", " e ");
define("_formulize_USERNAME_HELP3", " caracteres de comprimento)");
define("_formulize_PASSWORD_HELP1", " caracteres de comprimento)");

// "Other" for checkboxes and radio buttons:
define("_formulize_OPT_OTHER", "Outro: ");

// Notifications
define("_formulize_DE_NOTBUTTON", "Notificações");
define("_formulize_DE_SETNOT", "Adicione uma opção de notificação para este formulário");
define("_formulize_DE_SETNOT_WHEN", "Enviar esta notificação quando:");
define("_formulize_DE_SETNOT_TOME_WHEN", "Enviar-me esta notificação quando:");
define("_formulize_DE_SETNOT_WHEN_NEW", "uma nova entrada é criada");
define("_formulize_DE_SETNOT_WHEN_UPDATE", "uma entrada é atualizada");
define("_formulize_DE_SETNOT_WHEN_DELETE", "uma entrada é excluída");
define("_formulize_DE_SETNOT_WHO", "Enviar esta notificação para:");
define("_formulize_DE_SETNOT_WHO_ME", "mim");
define("_formulize_DE_SETNOT_WHO_CURUSER", "o usuário que simplesmente criou, atualizou, excluiu a entrada");
define("_formulize_DE_SETNOT_WHO_CREATOR", "o usuário que criou a informação"); //GibaPhp 3.0
define("_formulize_DE_SETNOT_WHO_ELEMENTUIDS", "usuário(s) selecionado em: "); //GibaPhp 3.0
define("_formulize_DE_SETNOT_NOELEMENTOPTIONS", "Nenhum usuário listado neste formulário"); //GibaPhp 3.0
define("_formulize_DE_SETNOT_WHO_LINKCREATOR", "o usuário que criou o item selecionado neste elemento: "); //GibaPhp 3.0
define("_formulize_DE_SETNOT_NOLINKCREATOROPTIONS", "Nenhum elemento ligado a este formulário"); //GibaPhp 3.0
define("_formulize_DE_SETNOT_WHO_GROUP", "usuários neste grupo: ");
define("_formulize_DE_SETNOT_FOR", "Enviar esta notificação para:");
define("_formulize_DE_SETNOT_FOR_ALL", "todas as entradas");
define("_formulize_DE_SETNOT_FOR_CON", "somente entradas que atendam estas condições:");
define("_formulize_DE_SETNOT_ADDCON", "Adicionar outra condição");
define("_formulize_DE_SETNOT_TEMP", "Usar um modelo de mensagem personalizado? Neste caso, digite o nome do arquivo aqui.");
define("_formulize_DE_SETNOT_TEMP_DESC", "Upload your custom message template file into the 'modules/formulize/english/mail_template/' folder.");
define("_formulize_DE_SETNOT_SUBJ", "Usar uma linha de assunto de mensagem personalizada?  Neste caso, digite o assunto aqui.");
define("_formulize_DE_SETNOT_SAVE", "Salvar esta notificação");
define("_formulize_DE_NOTLIST", "Notificações atuais");
define("_formulize_DE_NOT_WHENTEXT", "Quando ");
define("_formulize_DE_NOT_SENDTEXT", " enviar um notificação para ");
define("_formulize_DE_NOT_CONTEXTIF", " somente se ");
define("_formluize_DE_NOT_CONTEXTAND", ", e ");
define("_formulize_DE_NOT_TEMPTEXT", "Use este arquivo de modelo personalizado: ");
define("_formulize_DE_NOT_SUBJTEXT", "Use esta linha de assunto personalizada: ");

// multi-page forms
define("_formulize_DMULTI_THANKS", "<h1>Concluído!</h1><p>Muito obrigado por preencher este formulário. Nós realmente ficamos muito gratos.</p>");
define("_formulize_DMULTI_NEXT", "Salvar e avançar >>");
define("_formulize_DMULTI_PREV", "<< Salvar e voltar");
define("_formulize_DMULTI_SAVE", "Salvar e concluir >>");
define("_formulize_DMULTI_PAGE", "Página");
define("_formulize_DMULTI_OF", "de");
define("_formulize_DMULTI_SKIP", "Uma ou mais páginas foram saltadas porque elas não se aplicam");
define("_formulize_DMULTI_ALLDONE", "Abandonar este formulário e continuar navegando no site");
define("_formulize_DMULTI_JUMPTO", "Saltar para a página:");
define("_formulize_DMULTI_YOUAREON", "Você está na:");


// import
define("_formulize_DE_IMPORT", "Importar dados");
define("_formulize_DE_IMPORT_RESULTS", "Resultados...");
define("_formulize_DE_IMPORT_STEP1", "Passo 1: baixe um arquivo de modelo em branco ou um arquivo com dados");
define("_formulize_DE_IMPORT_STEP2", "Passo 2: modifique o arquivo que você baixou");
define("_formulize_DE_IMPORT_STEP3", "Passo 3: suba o arquivo modificado");
define("_formulize_DE_IMPORT_FILE", "Selecione o arquivo que você modificou e importe-o.");
define("_formulize_DE_IMPORT_GO", "Importar"); 
define("_formulize_DE_IMPORT_VALIDATEDATA", "Validar automaticamente a estrutura e os dados contidos no arquivo .csv antes de realmente fazer a importação (altamente recomendado!)"); //GibaPhp 3.0
define("_formulize_DE_IMPORT_INSTRUCTIONS", "<p><b>Abra o arquivo que você baixou em um programa de planilha eletrônica tal como o Excel, e modifique-o</b> de forma que ele contenha os dados que você quer importar. Você não tem que fazer isso de imediato: modifique o arquivo e, mais tarde, retorne aqui para importá-lo.</p>

<p>Quando você salvar o arquivo, <b>tenha certeza de salvá-lo no formato '.csv'.</b>  Se você salvá-lo em um formato diferente, tal como '.xls', o processo de importação não funcionará.</p>");

define("_formulize_DE_IMPORT_INSTNEWPROFILE","<p><b>Se você está criando novas entradas em um formulário de perfil de usuário,</b> inclua, para cada entrada, nome de usuário único, nome completo, senha, endereço de e-mail único e código de registro válido. Uma nova conta de usuário será criada para cada entrada, baseada nas informações fornecidas.</p>");

define("_formulize_DE_IMPORT_INSTUPDATE", "<p><b>Se você está atualizando entradas já existentes, não mude ou remova a parte '_1148849956' do nome do arquivo</b>  e <b>não adicione quaisquer outros caracteres '_'</b>. Também <b>não altere os números ID</b> em cada linha do arquivo. Todas estas informações identificam individualmente as entradas associadas com cada linha.</p>

<p><b>Cada linha na planilha (após o cabeçalho) representa uma entrada no formulário.</b> Então, se você quer importar três entradas, precisará de três linhas de dados na planilha. A ordem das linhas não tem importância. Se você está atualizando entradas e exclui linhas da planilha, <i>isto não excluirá as entradas correspondentes</i> da base de dados.</p>

<p><b>Se uma questão possui um grupo de respostas pre-determinadas,</b> a informação em sua planilha deve corresponder exatamente com as opções do formulário. Isto inclui a gramática, capitalização e espaços. Algumas questões em alguns formulários permitem a escolha de mais que uma resposta. É o caso, por exemplo, de um grupo de caixas de verificação. <b>Para incluir múltiplas respostas em sua planilha,</b> cada resposta deve estar na mesma célula, com uma quebra de linha entre elas (isto é: na mesma célula, mas cada resposta em uma linha diferente). No Excel, pressione Alt+Enter após cada resposta, para adicionar a quebra de linha.</p>");

define("_formulize_DE_IMPORT_INSTNEW", "<p><b>Se você está criando novas entradas,</b> então a coluna chamada \"" . _formulize_DE_CALC_CREATOR . "\" pode conter o nome de usuário ou o nome completo da pessoa registrada como o criador da entrada. Se deixar esta coluna em branco, então você mesmo será registrado como o criador. E se está atualizando entradas já existentes, esta coluna será ignorada.</p>");

define("_formulize_DE_IMPORT_BACK", "Voltar");
define("_formulize_DE_IMPORT_EITHEROR", "Você pode adicionar novas entradas a um formulário, ou atualizar entradas já existentes. Você <b>não pode</b> fazer as duas coisas de uma só vez.");
define("_formulize_DE_IMPORT_OR", "OU");
define("_formulize_DE_IMPORT_BLANK", "Se você quer adicionar novas entradas neste formulário...");
define("_formulize_DE_IMPORT_BLANK2", "Clique com o botão direito aqui e salve o necessário modelo.");
define("_formulize_DE_IMPORT_DATATEMP", "Se você quer atualizar entradas já existentes neste formulário...");
define("_formulize_DE_IMPORT_DATATEMP2", "Clique aqui e recarregue a janela principal.");
define("_formulize_DE_IMPORT_DATATEMP3", "Então baixe o arquivo de modelo clicando no link que aparecerá lá. Modelos sempre incluem todas as colunas, independentemente de quais colunas estejam atualmente selecionadas. Os modelos incluem somente as linhas (entradas) que estão atualmente visíveis. Para incluir todas as entradas, desative todas as buscas e outros filtros.");
define("_formulize_DE_IMPORT_USERNAME", "Nome de usuário");
define("_formulize_DE_IMPORT_FULLNAME", "Nome completo");
define("_formulize_DE_IMPORT_PASSWORD", "Senha");
define("_formulize_DE_IMPORT_EMAIL", "E-mail");
define("_formulize_DE_IMPORT_REGCODE", "Código de registro");
define("_formulize_DE_IMPORT_IDREQCOL", "Número ID (identificador) desta entrada (NÃO REMOVA ou modifique esta coluna)");
define("_formulize_DE_CLICKSAVE_TEMPLATE", "Clique com o botão direito e salve o download do seu modelo.");

define("_AM_FORMULIZE_SCREEN_LOE_TEMPLATE_ERROR", "Há um erro no código do seu modelo.  Desculpe-nos, não sabemos mais que isso. Tipologia e errros de sintaxe são os problemas mais comuns. Também, se seu modelo possui uma condição se...senão (if...else) abarcando todo o código, e essa condição nunca é satisfeita, então o modelo não será encontrado, situação que pode causar esse erro. Para evitar isso, esteja seguro de que seu modelo sempre exibe alguma saída, mesmo que seja apenas '&amp;nbsp;'");
define("_AM_FORMULIZE_LOE_FIRSTPAGE", "<< Primeira");
define("_AM_FORMULIZE_LOE_LASTPAGE", "Última >>");
define("_AM_FORMULIZE_LOE_ONPAGE", "Na página ");

define("_formulize_DE_LOE_LIMIT_REACHED1", "Lá estão"); //GibaPhp 3.0
define("_formulize_DE_LOE_LIMIT_REACHED2", "entradas na lista. Seria preciso um longo tempo para recuperá-los. Você pode usar termos de pesquisa para limitar o número de entradas, ou"); //GibaPhp 3.0
define("_formulize_DE_LOE_LIMIT_REACHED3", "você pode clicar aqui para que o sistema possa recuperar todas as entradas."); //GibaPhp 3.0
       
define("_formulize_OUTOFRANGE_DATA","Preservar valor não-padrão encontrado na base de dados: "); //GibaPhp 3.0

define("_AM_FORMULIZE_PREVIOUS_OPTION", "Use uma resposta anterior:"); //GibaPhp 3.0
define("_formulize_VALUE_WILL_BE_CALCULATED_AFTER_SAVE","Este valor será calculado após a gravação de dados"); //GibaPhp 3.0

define("_formulize_QSF_DefaultText", "Procurar por:"); //GibaPhp 3.0

?>