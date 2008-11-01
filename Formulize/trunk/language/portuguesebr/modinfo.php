<?php
/**
* --------------------------------------------------------------------
*      Portuguese Translation by Paulo Sergio de Araujo alias Gopala
*                     gopala at harekrishna dot org dot br
*    http://www.x-trad.org the XOOPS Official Brazilian Translate Site            
* Translation version 3.0 by GibaPhp - http://br.impresscms.org   
* -------------------------------------------------------------------- 
*/
// Module Info

// The name of this module
define("_MI_formulize_NAME","Formulários");

// A brief description of this module
define("_MI_formulize_DESC","Para geração de formulários e análise de dados");

// admin/menu.php
define("_MI_formulize_ADMENU0","Administração de formulários");
define("_MI_formulize_ADMENU1","Menu");

// notifications
define("_MI_formulize_NOTIFY_FORM", "Notificações de formulários");
define("_MI_formulize_NOTIFY_FORM_DESC", "Notificações relacionadas ao formulário atual");
define("_MI_formulize_NOTIFY_NEWENTRY", "Nova Entrada em um formulário");
define("_MI_formulize_NOTIFY_NEWENTRY_CAP", "Notificar-me quando alguém fizer uma nova entrada neste formulário");
define("_MI_formulize_NOTIFY_NEWENTRY_DESC", "Uma opção de notificação que alerta os usuários quando novas entradas são feitas em um formulário");
define("_MI_formulize_NOTIFY_NEWENTRY_MAILSUB", "Nova Entrada em um formulário");

define("_MI_formulize_NOTIFY_UPENTRY", "Atualizar Entrada em um formulário");
define("_MI_formulize_NOTIFY_UPENTRY_CAP", "Notificar-me quando alguém atualizar uma entrada neste formulário");
define("_MI_formulize_NOTIFY_UPENTRY_DESC", "Uma opção de notificação que alerta os usuários quando entradas são atualizadas em um formulário");
define("_MI_formulize_NOTIFY_UPENTRY_MAILSUB", "Entrada Atualizada em um formulário");

define("_MI_formulize_NOTIFY_DELENTRY", "Entrada Excluída de um formulário");
define("_MI_formulize_NOTIFY_DELENTRY_CAP", "Notificar-me quando alguém excluir uma entrada neste formulário");
define("_MI_formulize_NOTIFY_DELENTRY_DESC", "Uma opção de notificação que alerta os usuários quando entradas são excluídas de um formulário");
define("_MI_formulize_NOTIFY_DELENTRY_MAILSUB", "Entrada Excluída de um formulário");


//	preferences
define("_MI_formulize_TEXT_WIDTH","Largura padrão das caixas de texto");
define("_MI_formulize_TEXT_MAX","Comprimento máximo padrão das caixas de texto");
define("_MI_formulize_TAREA_ROWS","Quantidade de linhas padrão das áreas de texto");
define("_MI_formulize_TAREA_COLS","Quantidade de colunas padrão das áreas de texto");
define("_MI_formulize_DELIMETER","Delimitador padrão para caixas de verificação e botões de rádio");
define("_MI_formulize_DELIMETER_SPACE","Espaço em branco");
define("_MI_formulize_DELIMETER_BR","Quebra de linha");
define("_MI_formulize_SEND_METHOD","Método de envio");
define("_MI_formulize_SEND_METHOD_DESC","Nota: formulários preenchidos por usuários anônimos não poderão ser enviados através de mensagem privada.");
define("_MI_formulize_SEND_METHOD_MAIL","E-mail");
define("_MI_formulize_SEND_METHOD_PM","Mensagem privada");
define("_MI_formulize_SEND_GROUP","Enviar para grupo");
define("_MI_formulize_SEND_ADMIN","Enviar somente para o administrador do site");
define("_MI_formulize_SEND_ADMIN_DESC","Ajustes de \"Enviar para grupo\" serão ignorados");
define("_MI_formulize_PROFILEFORM","Qual formulário será usado como parte do processo de registro e quando visualizando e editando contas? (requer uso do módulo Códigos de Registro - Registration Codes)");

define("_MI_formulize_ALL_DONE_SINGLES","O botão 'Sair sem Salvar' deve aparecer na parte inferior do formulário ao editar uma entrada ou ao criar uma nova entrada em um formulário 'individual'?");
define("_MI_formulize_SINGLESDESC","O botão 'Sair sem Salvar' é usado para sair de um formulário sem salvar a informação já digitada nele. Se você fizer alterações nas informações constantes em um formulário e clicar no botão 'Sair sem Salvar' sem antes clicar em 'Salvar', receberá um aviso de que a informação ainda não foi salva. Devido a forma como trabalham os botões 'Salvar' e 'Sair sem Salvar', normalmente não é possível salvar as informações e sair do formulário com um só clique. Isso pode confundir alguns usuários. Ajuste esta opção para 'Não', para remover o botão 'Sair sem Salvar' e mude o texto do botão 'Salvar' para 'Salvar e sair do Formulário'. Esta opção não afeta situações onde o usuário está adicionando múltiplas entradas em um formulário (quando então o formulário reaparece em branco, a cada vez que se clica no botão 'Salvar'.");

define("_MI_formulize_LOE_limit", "What is the maximum number of entries that should be displayed in a list of entries, without confirmation from the user that they want to see all entries?"); //GibaPhp 3.0
define("_MI_formulize_LOE_limit_DESC", "If a dataset is very large, displaying a list of entries screen can take a long time, several minutes even.  Use this preference to specify the maximum number of entries that your system should try to display at once.  If a dataset contains more entries than this limit, the user will be asked if they want to load the entire dataset or not."); //GibaPhp 3.0
       
define("_MI_formulize_USETOKEN", "Use the security token system to validate form submissions?"); //GibaPhp 3.0
define("_MI_formulize_USETOKENDESC", "By default, when a form is submitted, no data is saved unless Formulize can validate a unique token that was submitted with the form.  This is a partial defence against cross site scripting attacks, meant to ensure only people actually visiting your website can submit forms.  In some circumstances, depending on firewalls or other factors, the token cannot be validated even when it should be.  If this is happening to you repeatedly, you can turn off the token system for Formulize here."); //GibaPhp 3.0
       

// The name of this module
define("_MI_formulizeMENU_NAME","Meus Formulários");

// A brief description of this module
define("_MI_formulizeMENU_DESC","Exibe, em um bloco, um menu configurável individualmente");

// Names of blocks for this module (Not all module has blocks)
define("_MI_formulizeMENU_BNAME","Formulários");

// Version
define("_MI_VERSION","2.0b");
?>
