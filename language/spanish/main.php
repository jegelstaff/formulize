<?php
define("_formulize_FORM_TITLE", "Contacta con nosotros rellenando este formulario.");
//define("_formulize_MSG_SUBJECT", $xoopsConfig['sitename'].' - Formulario para Contartarnos');
define("_formulize_MSG_SUBJECT", '['.$xoopsConfig['sitename'].'] -');
define("_formulize_MSG_FORM", ' Formulario: ');
//next two added by jwe 7/23/04
define("_formulize_INFO_RECEIVED", "Su informacion ha sido recibida.");
define("_formulize_NO_PERMISSION", "No tiene permiso para ver este formulario.");
define("_formulize_MSG_SENT", "Su mensaje ha sido enviado.");
define("_formulize_MSG_THANK", "<br />Gracias por sus comentarios.");
define("_formulize_MSG_SUP","<br /> Ciudado, los datos han sido borrados");
define("_formulize_MSG_BIG","El fichero adjunto es demasiado grande para subir.");
define("_formulize_MSG_UNSENT","Por favor, adjunte un fichero con un tamaño inferior a ");
define("_formulize_MSG_UNTYPE","No puede adjuntar ese tipo de ficher.<br>Los tipos admitidos son : ");

define("_formulize_NEWFORMADDED","Nuevo formulario añadido satisfactoriamente!");
define("_formulize_FORMMOD","Titulo de formulario modificado satisfactoriamente!");
define("_formulize_FORMDEL","Formulario borrado satisfactoriamente!");
define("_formulize_FORMCHARG","Cargando Formulario");
define("_formulize_FORMSHOW","Resultados de Formulario: ");
define("_formulize_FORMTITRE","Los parametros enviados del formulario han sido modificados con exito");
define("_formulize_NOTSHOW","Formulario: ");
define("_formulize_NOTSHOW2"," no contiene ningun registro.");
define("_formulize_FORMCREA","Formulario creado con exito!");

define("_MD_ERRORTITLE","Error ! No puso ningun titulo al Formulario !!!!");
define("_MD_ERROREMAIL","Error ! No puso una direccion de correo valida !!!!");
define("_MD_ERRORMAIL","Error ! You did not put the form recipient !!!!");

define("_FORM_ACT","Accion");
define("_FORM_CREAT","Crear formulario");
define("_FORM_RENOM","Renombrar formulario");
define("_FORM_RENOM_IMG","<img src='../images/attach.png'>");
define("_FORM_SUP","Borrar formulario");
define("_FORM_ADD","Parametros enviados");
define("_FORM_SHOW","Ver resultados");
define("_FORM_TITLE","Titulo de formulario:");
define("_FORM_EMAIL","E-mail: ");
define("_FORM_ADMIN","Enviar al administrador unicamente:");
define("_FORM_EXPE","Recibir el formulario relleno:");
define("_FORM_GROUP","Enviar a grupo:");
define("_FORM_MODIF","Modificar formulario");
define("_FORM_DELTITLE","Titulo de formulario a borrar:");
define("_FORM_NEW","Nuevo Formulario");
define("_FORM_NOM","Introduzca el nuevo nombre de fichero");
define("_FORM_OPT","Opciones");
define("_FORM_MENU","Consultar el menu");
define("_FORM_PREF","Consultas las preferencias");

//next section added by jwe 7/25/07
define("_FORM_SINGLEENTRY","Este formulario permite de cada usuario solo una entrada (rellenar el formulario otra vez actualiza la misma entrada):");
define("_FORM_GROUPSCOPE","Las entradas en este formulario se comparten y son visibles para todos los usuarios en los mismos grupos (no solo el usuario que lo relleno):");
define("_FORM_HEADERLIST","Los elementos de formulario se ven en la pagina 'Ver Entradas':");
define("_FORM_SHOWVIEWENTRIES","Los usuarios pueden ver entradas anteriores de este formulario:");
define("_FORM_MAXENTRIES","Despues de que un usuario rellena el formulario muchas veces, el no puede acceder al formulario otra vez (0 significa sin limite):");
define("_FORM_DEFAULTADMIN","Grupos que tienen privilegios para este formulario:");

define("_FORM_COLOREVEN","Primer color alternativo para la pagina de informes (los colores alternativos solapan los colores por defecto para distinguir un formulario de otro):");
define("_FORM_COLORODD","Segundo color alternativo para la pagina de informes:");


define("_FORM_MODIF","Modificar");
define("_AM_FORM","Formulario: ");
define("_FORM_EXPORT","Exportar en formato CSV");
define("_FORM_ALT_EXPORT","Exportar");
define("_FORM_DROIT","Grupo autorizado para consultar el formulario");
define("_FORM_MODPERM","Modificar permisos de acceso");
define("_FORM_PERM","Permisos");

define("_FORM_MODPERMLINKS","Modificar ambito de las cajas de seleccion enlazadas");
define("_FORM_PERMLINKS","Ambito de Caja de Seleccion enlazada");



// commented the line below since it's a duplicate of a line above --jwe 7/25/04
//define("_AM_FORM","Formulario : ");
define("_AM_FORM_SELECT","Seleccione Formulario");
define("_MD_FILEERROR","Error enviando fichero");
define("_AM_FORMUL","Formularios");

//added by jwe - 7/28/04
define("_AM_FORM_TITLE", "Permisos de acceso de Formulario "); // not used
define("_AM_FORM_CURPERM", "Permiso actual:"); 
define("_AM_FORM_CURPERMLINKS", "Caja de seleccion actualmente enlazada:"); 
define("_AM_FORM_PERMVIEW", "Ver");
define("_AM_FORM_PERMADD", "Añadir/Actualizar");
define("_AM_FORM_PERMADMIN", "Admin");
define("_AM_FORM_SUBMITBUTTON", "Mostrar nuevos permisos"); // not used

define("_AM_FORMLINK_PICK", "Elija opcion");
define("_AM_CONFIRM_DEL", "Esta a punto de borrar este Formulario!  Por favor confirme.");



define("_FORM_EXP_CREE","El fichero ha sido exportado con exito");

//template constants added by jwe 7/24/04
define("_formulize_TEMP_ADDENTRY", "Añadir entrada");
define("_formulize_TEMP_VIEWENTRIES", "Ver entradas");
define("_formulize_TEMP_ADDINGENTRY", "Añadiendo entrada");
define("_formulize_TEMP_VIEWINGENTRIES", "Ver entradas");
define("_formulize_TEMP_SELENTTITLE", "Sus entradas en  '");
define("_formulize_TEMP_SELENTTITLE_GS", " Todas las entradas en '");
define("_formulize_TEMP_SELENTTITLE_RP", "Resultados de busqueda para '");
define("_formulize_TEMP_SELENTTITLE2_RP", "Resultados de calculo para '");
define("_formulize_TEMP_VIEWTHISENTRY", "Ver esta entrada");
define("_formulize_TEMP_EDITINGENTRY", "Editar entrada");
define("_formulize_TEMP_NOENTRIES", "Sin entradas.");
define("_formulize_TEMP_ENTEREDBY", "Introducido por: ");
define("_formulize_TEMP_ENTEREDBYSINGLE", "Introducido ");
define("_formulize_TEMP_ON", "Encendido");
define("_formulize_TEMP_QYES", "SI");
define("_formulize_TEMP_QNO", "NO");
define("_formulize_REPORT_ON", "Cambiar modo de escritura de informe a Encendido");
define("_formulize_REPORT_OFF", "Cambiar modo de escritura de informe a Apagado");
define("_formulize_VIEWAVAILREPORTS", "Ver informe:");
define("_formulize_NOREPORTSAVAIL", "Cista por defecto");
define("_formulize_CHOOSEREPORT", "Vista por defecto");
define("_formulize_REPORTING_OPTION", "Opciones de informe");
define("_formulize_SUBMITTEXT", "Aplicar");
define("_formulize_RESETBUTTON", "RESTABLECER");
define("_formulize_QUERYCONTROLS", "Controles de consulta");
define("_formulize_SEARCH_TERMS", "Terminos de busqueda:");
define("_formulize_STERMS", "Terminos:");
define("_formulize_AND", "Y");
define("_formulize_OR", "O");
define("_formulize_SEARCH_OPERATOR", "Operador:");
define("_formulize_NOT", "NO");
define("_formulize_LIKE", "COMO");
define("_formulize_NOTLIKE", "DISTINTO");
define("_formulize_CALCULATIONS", "Calculos:");
define("_formulize_SUM", "Suma");
define("_formulize_SUM_TEXT", "Total de todos los valores en la columna:");
define("_formulize_AVERAGE", "Media");
define("_formulize_AVERAGE_INCLBLANKS", "Media en la columna:");
define("_formulize_AVERAGE_EXCLBLANKS", "Media excluyendo ceros y en blanco:");
define("_formulize_MINIMUM", "Minimo");
define("_formulize_MINIMUM_INCLBLANKS", "Valor minimo en la columna:");
define("_formulize_MINIMUM_EXCLBLANKS", "Valor minimo excluyendo ceros y en blanco:");
define("_formulize_MAXIMUM", "Maximo");
define("_formulize_MAXIMUM_TEXT", "Valor maximo en la columna:");
define("_formulize_COUNT", "Countados");
define("_formulize_COUNT_INCLBLANKS", "Valores totales en la columna:");
define("_formulize_COUNT_ENTRIES", "Entradas totales en la columna:");
define("_formulize_COUNT_NONBLANKS", "Total no en blanco, entradas que no son cero en la columna:");
define("_formulize_COUNT_EXCLBLANKS", "Total no en blanco, no cero en la columna:");
define("_formulize_COUNT_PERCENTBLANKS", "Porcentaje de no en blanco y no cero:");
define("_formulize_COUNT_UNIQUES", "Total de valores unicos en la columna:");
define("_formulize_COUNT_UNIQUEUSERS", "Numero de usuarios que han introducido entradas en la columna:");
define("_formulize_PERCENTAGES", "Porcentajes");
define("_formulize_PERCENTAGES_VALUE", "Valor:");
define("_formulize_PERCENTAGES_COUNT", "Contado:");
define("_formulize_PERCENTAGES_PERCENT", "% de total:");
define("_formulize_PERCENTAGES_PERCENTEXCL", "% excl. en blanco:");
define("_formulize_SORTING_ORDER", "Orden de ordenacion:");
define("_formulize_SORT_PRIORITY", "Prioridad de ordenacion:");
define("_formulize_NONE", "Ninguno");
define("_formulize_CHANGE_COLUMNS", "Cambiar a vista de diferentes columnas:");
define("_formulize_CHANGE", "Cambiar");
define("_formulize_SEARCH_HELP", "Si especifica terminos de busqueda en mas de una columna, the Interfield AND/OR Setting determines whether to search for entries that match in all columns (AND), or at least one column (OR).<br><br>The AND/OR option below the terms box determines whether to search for entries that match all the terms (AND), or at least one of the terms (OR).<br><br>Use commas to separate terms.  Use [,] to specify a comma within a term.");
define("_formulize_SORT_HELP", "Puede ordenar por cualquier elemento, excepto aquellos que aceptan multiples entradas, como cajas de verificacion.");
define("_formulize_REPORTSCOPE", "Seleccione ambito del informe:");
define("_formulize_SELECTSCOPEBUTTON", "Seleccione");
define("_formulize_GROUPSCOPE", "Grupo: ");
define("_formulize_USERSCOPE", "Usuarior: ");
define("_formulize_GOREPORT", "Ir");
define("_formulize_REPORTSAVING", "Guardar esta consulta como uno de sus informes:");
define("_formulize_SAVEREPORTBUTTON", "Guardar");
define("_formulize_REPORTNAME", "Nombre del informe:");
define("_formulize_ANDORTITLE", "Interfield AND/OR Setting:");

define("_formulize_PUBLISHINGOPTIONS", "Opciones de publicacion:");
define("_formulize_PUBLISHREPORT", "Publicar este informe a otros usuarios.");
define("_formulize_PUBLISHNOVE", "Suprimir enlaces 'Ver esta entrada' del informe(para que los usuarios no puedan ver los detalles de cada entrada).");
define("_formulize_PUBLISHCALCONLY", "Remove the list of entries entirely, and show only the aggregate calculations.");


define("_formulize_LOCKSCOPE", "<b>Guardar informe con el actual ambito bloqueado</b> (de lo contrario los visitantes estan limitados al ambito por defecto).");
define("_formulize_REPORTPUBGROUPS", "Seleccionar los grupos a los que publicar:");
define("_formulize_REPORTDELETE", "Borrar el informe seleccionado actualmente:");
define("_formulize_DELETE", "Borrar");
define("_formulize_DELETECONFIRM", "Verificar esta caja y pulsa el boton para suprimir");

define("_formulize_REPORTEXPORTING", "Exportar esta consulta como fichero spreadsheet :");
define("_formulize_EXPORTREPORTBUTTON", "Exportar");
define("_formulize_EXPORTEXPLANATION", "Pulsa el boton <b>Exportar</b> para descargar un fichero spreadsheet-readable que contiene los resultados de la consulta actual.  Dese cuenta que puede especificar el delimitador usado entre los campos.  Si el caracter delimitador que elija esta presente en los resultados, el fichero spreadsheet no abrira correctamente, asi que pruebe exportar con otro delimitador.");
define("_formulize_FILEDELTITLE", "Delimitador de campo:");
define("_formulize_FDCOMMA", "Coma");
define("_formulize_FDTAB", "Tab");
define("_formulize_FDCUSTOM", "Personalizado");
define("_formulize_exfile", "datos_export_");
define("_formulize_DLTEXT", "<b>Boton derecho en el enlace de abajo y seleccione <i>Guardar</i>.</b> (Ctrl-click en Mac.)  Una vez el fichero este en su ordenador, podrá abrirlo con el programa correspondiente. Si los campos no se alinean correctamente cuando abre el fichero, pruebe a exportar con otro delimitador diferente.");
define("_formulize_DLHEADER", "Su fichero está listo para descargar.");

define("_formulize_PICKAPROXY", "Ningun Usuario Proxy seleccionado");
define("_formulize_PROXYFLAG", "(Proxy)");

define("_formulize_DELBUTTON", "Borrar");
define("_formulize_DELCONF", "Esta a punto de borrar una entrada!  Por favor, confirme.");


?>
