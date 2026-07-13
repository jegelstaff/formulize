<?php
/**
* --------------------------------------------------------------------
*      Italian Translation by Luigi Massetti alias Gigiox
*                     gigiox66 at libero dot it
*    http://www.xoopsit.net the XOOPS Official Italian Support Site
* --------------------------------------------------------------------
*/
define("_AM_SAVE","Salva");
define("_AM_COPIED","%s - copia");
define("_AM_DBUPDATED","Archivio aggiornato con successo!");
define("_AM_ELE_CREATE","Crea elementi form");
define("_AM_ELE_EDIT","Modifica elementi form: %s");
define("_AM_FORM","Form : ");
define("_AM_FORM_REQ","Risultato n� : ");
define("_AM_REQ","Risultati del modulo form : ");
define("_AM_SEPAR",'{SEPAR}');
define("_AM_ELE_FORM","Elementi Form");
define("_AM_PARA_FORM","Parametri Form");

define("_AM_ELE_CAPTION","Intestazione");
define("_AM_ELE_CAPTION_DESC","<br /></b>{SEPAR} consenti di non mostrare il nome dell'elemento");
define("_AM_ELE_DEFAULT","Valore di default");
define("_AM_ELE_DETAIL","Dettagli");
define("_AM_ELE_REQ","Riempimento obbligatorio");
define("_AM_ELE_ORDER","Ordine");
define("_AM_ELE_DISPLAY","Visualizza");

define("_AM_ELE_TEXT","Casella di testo");
define("_AM_ELE_TEXT_DESC","{UNAME} stamper� il nome utente;<br />{EMAIL} stamper� l'indirizzo di posta dell'utente");
define("_AM_ELE_TAREA","Area di testo");
define("_AM_ELE_MODIF","Area di testo non modificabile");
define("_AM_ELE_SELECT","Caselle di scelta");
define("_AM_ELE_CHECK","Caselle di controllo");
define("_AM_ELE_RADIO","Pulsanti di scelta");
define("_AM_ELE_YN","Pulsanti semplici di scelta s�/no ");
define("_AM_ELE_DATE","Data");
define("_AM_ELE_REQ_USELESS","Non usabile per caselle di scelta, caselle di controllo n� pulsanti di scelta");
define("_AM_ELE_SEP","Linea di separazione");
define("_AM_ELE_NOM_SEP","Nome di separazione");
define("_AM_ELE_UPLOAD","Unisci file");
define("_AM_ELE_CLR","Con colore");

define("_AM_ELE_SIZE","Dimensione");
define("_AM_ELE_MAX_LENGTH","Lunghezza massima");
define("_AM_ELE_ROWS","Righe");
define("_AM_ELE_COLS","Colonne");
define("_AM_ELE_OPT","Opzioni");
define("_AM_ELE_OPT_DESC","Spunta le caselle di controllo per la scelta di valori di default");
define("_AM_ELE_OPT_DESC1","<br />Se la scelta multipla non � permessa solo il primo campo spuntato � utilizzato");
define("_AM_ELE_OPT_DESC2","Scegli il valore di default spuntando con i pulsanti di scelta");
define("_AM_ELE_ADD_OPT","Aggiungi %s opzioni");
define("_AM_ELE_ADD_OPT_SUBMIT","Aggiungi");
define("_AM_ELE_SELECTED","Scelto");
define("_AM_ELE_CHECKED","Controllato");
define("_AM_ELE_MULTIPLE","Permesse pi� scelte");
define("_AM_ELE_TYPE","Mostra l'interruzione");
define("_AM_ELE_GRAS","Grassetto");
define("_AM_ELE_RGE","Rosso");
define("_AM_ELE_CTRE","Centrato");
define("_AM_ELE_ITALIQ","Italico");
define("_AM_ELE_SOUL","Sottolineato");
define("_AM_ELE_BLEU","Blu");
define("_AM_ELE_FICH",'File');
define("_AM_ELE_TAILLEFICH","Dimensione massima del file");
define("_AM_ELE_PDS","punti");

define("_AM_ELE_SELECT_NONE","Nessun elemento scelto.");
define("_AM_ELE_CONFIRM_DELETE","Sei sicuro di volere cancellare questo elemento del form?");

define("_AM_TITLE","Menu amministrazione");
define("_AM_ID","ID");
define("_AM_POS","Posizione");
define("_AM_POS_SHORT","Pos.");
define("_AM_INDENT","Rientranza a sinistra");
define("_AM_INDENT_SHORT","Rientr.");
define("_AM_ITEMNAME","Nome");
define("_AM_ITEMURL","URL");
define("_AM_STATUS","Stato");
define("_AM_FUNCTION","Operazione");
define("_AM_ACTIVE","attivo");
define("_AM_INACTIVE","non attivo");
define("_AM_BOLD","grassetto");
define("_AM_NORMAL","normale");
define("_AM_MARGINBOTTOM","Margine inferiore");
define("_AM_MARGIN_BOTTOMSHORT","mrg. inf.");
define("_AM_MARGINTOP","Margine superiore");
define("_AM_MARGIN_TOPSHORT","mrg. sup.");
define("_AM_EDIT","Modifica");
define("_AM_DELETE","Cancella");
define("_AM_ADDMENUITEM","Aggiungi voce nel menu");
define("_AM_CHANGEMENUITEM","Modifica/cancella voce del menu");
define("_AM_SITENAMET","Nome sito:");
define("_AM_URLT","URL:");
define("_AM_FONT","Carattere");
define("_AM_STATUST","Stato:");
define("_AM_MEMBERSONLY","Utenti autorizzati");
define("_AM_MEMBERSONLY_SHORT","solo<br>autorizzati");
define("_AM_MEMBERS","solo membri");
define("_AM_ALL","tutti gli utenti");
define("_AM_ADD","Aggiungi");
define("_AM_EDITMENUITEM","Modifica voci di menu");
define("_AM_DELETEMENUITEM","Cancella voci di menu");
define("_AM_SAVECHANG","Salva modifiche");
define("_AM_WANTDEL","Vuoi realmente cancellare questa voce di menu?");
define("_AM_YES","S�");
define("_AM_NO","No");
define("_AM_formulizeMENUSTYLE","Stile-Menu personale");
define("_AM_MAINMENUSTYLE","Stile-Menu principale");

define("_AM_VERSION","2.8");
define("_AM_REORD","Nuovo ordinamento");
//added by f�lix <INBOX International> for sedonde (colorpicker feature)
define("_AM_ELE_COLORPICK","Colore");

// derived columns
define("_AM_ELE_DERIVED", "Valore derivato da altri elementi");
define("_AM_ELE_DERIVED_CAP", "Formula per generare valori in questo elemento");
define("_AM_ELE_DERIVED_DESC", "Esempi:<br />\$value = \$profile_first_name . ' ' . \$profile_last_name; // per un Nome Completo<br>\$value = \$inventory_value * \$inventory_count; // per un Valore Totale<br><br>La formula è scritta in codice PHP e l'elemento utilizzerà il valore assegnato a <b>\$value</b> nel codice. Come codice PHP, puoi usare tutto ciò che trovi in <a href='https://formulize.org/developers/API/' target='_blank'>l'API Formulize</a>, ma per operazioni complesse, le <b>Procedure Prima del Salvataggio</b> e <b>Dopo il Salvataggio</b> sono consigliate al posto dei valori derivati.<br><br>Le variabili disponibili sono:<br><b>\$form_id</b> - il numero di identificazione del modulo principale nel set di dati corrente, solitamente il modulo a cui appartiene la schermata attiva<br><b>\$entry_id</b> - l'ID della voce del record nel database a cui questo valore appartiene in questo particolare modulo<br><b>\$relationship_id</b> - il numero di identificazione della relazione attiva, se presente. Per impostazione predefinita, la Relazione Principale è la relazione attiva, a meno che la schermata attiva non sia specificamente impostata per utilizzare qualcos'altro.<br><b>\$entry</b> - la voce a cui questo valore appartiene, come raccolto dalla funzione gatherDataset dal set di dati attivo. Adatto per essere passato alla funzione di visualizzazione, ma è consigliato fare riferimento agli elementi tramite le loro variabili di handle:<br><br>Puoi raccogliere qualsiasi valore dal set di dati attivo facendo riferimento agli handle degli elementi come variabili, ad esempio: \$activities_name. Questo ti fornirà il valore leggibile di quell'elemento nel set di dati attualmente attivo. Nota che lo stesso handle di elemento può restituirti cose diverse a seconda di come questo modulo è incluso nel set di dati corrente quando la voce viene salvata. Ad esempio, potresti fare riferimento a un elemento in un altro modulo, e quel modulo è il modulo principale del set di dati in alcuni contesti, ma altre volte quel modulo viene utilizzato come un sottomodulo. Il valore derivato riceverà dati diversi in momenti diversi, se calcolato in circostanze diverse. Quando ci sono più voci da un modulo nel set di dati, gli handle degli elementi su quel modulo restituiranno un array di valori da tutte le voci.");
define("_AM_ELE_DERIVED_ADD", "Aggiungi alla formula");
define("_AM_ELE_DERIVED_DONE","Aggiornamento dei valori completato!");
define("_AM_ELE_DERIVED_UPDATE", "Aggiorna valori derivati");
define("_AM_ELE_DERIVED_UPDATE_CAP", "Calcola i valori per questo elemento");
define("_AM_ELE_DERIVED_UPDATE_DESC", "Potrebbe richiedere un po' a seconda di quanti record sono contenuti nel tuo modulo.");

// fallback to English for any missing constants
include_once XOOPS_ROOT_PATH.'/modules/formulize/language/english/admin.php';
?>
