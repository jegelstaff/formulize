<?php
define("_AM_CATGENERAL", "General Forms");
define("_AM_NOFORMS_AVAIL", "There are no forms currently available.");
define("_formulize_FORM_TITLE","Contactez nous en remplissant ce formulaire.");
//define("_formulize_MSG_SUBJECT",$xoopsConfig['sitename'].' - Formulaire de contact');
define("_formulize_MSG_SUBJECT", '['.$xoopsConfig['sitename'].'] -');
define("_formulize_MSG_FORM", ' formulaire : ');
//next two added by jwe 7/23/04
define("_formulize_INFO_RECEIVED", "Nous avons bien reçu vos informations.");
define("_formulize_NO_PERMISSION", "Vous ne disposez pas des autorisations vous permettant de pouvoir afficher ce formulaire.");
define("_formulize_MSG_SENT","Votre message a été envoyé.");
define("_formulize_MSG_THANK","<br />Merci pour vos commentaires.");
define("_formulize_MSG_SUP","<br /> Attention les enregistrements de ce formulaire ont été supprimés");
define("_formulize_MSG_BIG","LE fichier joint est trop volumineux pour être téléchargé.");
define("_formulize_MSG_UNSENT","Veuillez joindre un fichier dont la taille est inférieure à ");
define("_formulize_MSG_UNTYPE","Vous ne pouvez pas joindre ce type de fichier.<br>Les types autorisés sont : ");

define("_formulize_NEWFORMADDED","Nouveau formulaire ajouté avec succès!");
define("_formulize_FORMMOD","Titre de formulaire modifié avec succès!");
define("_formulize_FORMDEL","formulaire effacé avec succès!");
define("_formulize_FORMCHARG","Chargement du formulaire");
define("_formulize_FORMSHOW","Résultats de formulaire : ");
define("_formulize_FORMTITRE","Paramètres d'envoi du formulaire modifiés avec succès");
define("_formulize_NOTSHOW","Le formulaire ");
define("_formulize_NOTSHOW2"," ne contient pas de requêtes enregistrées.");
define("_formulize_FORMCREA","Formulaire créé avec succès!");

define("_MD_ERRORTITLE","Erreur ! Vous n'avez pas saisie le titre du formulaire !!!!");
define("_MD_ERROREMAIL","Erreur ! votre adresse e-mail n'est pas valide !!!!");
define("_MD_ERRORMAIL","Erreur ! Vous n'avez pas saisie de destinataire pour le formulaire !!!!");

define("_FORM_ACT","Action");
define("_FORM_CREAT","Créer un nouveau formulaire");
define("_FORM_RENOM","Renommer");
define("_FORM_RENOM_IMG","<img src='../images/attach.png'>");
define("_FORM_SUP", "Supprimer");
define("_FORM_ADD","Paramètres d'envoi");
define("_FORM_SHOW","Consultation des résultats");
define("_FORM_TITLE","Titre du formulaire : ");
define("_FORM_EMAIL","Adresse E-mail :");
define("_FORM_ADMIN","Envoyer uniquement à l'admin :");
define("_FORM_EXPE","Recevoir le formulaire rempli :");
define("_FORM_GROUP","Envoyer au groupe :");
define("_FORM_MODIF","Modifier");
define("_FORM_DELTITLE","Titre du formulaire à effacer :");
define("_FORM_NEW","Nouveau formulaire");
define("_FORM_NOM","Entrer le nouveau nom du formulaire");
define("_FORM_OPT","Options");
define("_FORM_MENU","Consulter le menu");
define("_FORM_PREF","Consulter les préférences");

//next section added by jwe 7/25/07
define("_FORM_SINGLEENTRY","Ce formulaire n'alloue qu'une entrée par utilisateur (remplir une nouvelle fois le formulaire mettra à jour la même entrée):");
define("_FORM_GROUPSCOPE","Les entrées de ce formulaire sont partagées et vue par tous les membre d'un même groupe (pas seulement par l'utilisateur qui l'a rempli):");
define("_FORM_HEADERLIST","Eléments de formulaire montré dans la page 'Visualisation des entrées' :");
define("_FORM_SHOWVIEWENTRIES","Les utilisateurs peuvent visualiser les entrées antérieurement saisies dans ce formulaire :");
define("_FORM_MAXENTRIES","Après un temps suffisament long les utilisateurs ayant saisie le formulaire, ne pourront plus y avoir accès de nouveau (0 afin de ne pas mettre de limite de temps):");
define("_FORM_DEFAULTADMIN","Groups disposant de droits sur ce formulaire :");

define("_FORM_COLOREVEN","Premier couleur alternative pour la page de rédaction de rapport  (la couleur alternative remplacera la couleur par défaut et permettra de distinguer un formulaire d'un autre):");
define("_FORM_COLORODD","Seconde couleur alternative pour la page de rédaction de rapport:");


define("_FORM_MODIF","Modification des questions du formulaire");
define("_AM_FORM","Formulaire : ");
define("_FORM_EXPORT","Export au format CSV");
define("_FORM_ALT_EXPORT","Exporter");
define("_FORM_DROIT","Groupes autorisés à utiliser ce formulaire");
define("_FORM_MODPERM","Modifier les permissions d'accès aux formulaires");
define("_FORM_PERM","Permissions");

define("_FORM_MODPERMLINKS","Modifier le périmètres des boîtes de sélection liées");
define("_FORM_PERMLINKS","Périmètres des boîtes de sélection liées");

define("_FORM_MODFRAME","Créer ou modifier un framework de formulaire");
define("_FORM_FRAME", "Frameworks");


// commented the line below since it's a duplicate of a line above --jwe 7/25/04
//define("_AM_FORM","Form : ");
define("_AM_FORM_SELECT","Sélection du formulaire");
define("_MD_FILEERROR","Erreur chargement de fichier");
define("_AM_FORMUL","Formulaires");

//added by jwe - 7/28/04
define("_AM_FORM_TITLE", "Autorisations d'acces au formulaire"); // not used
define("_AM_FORM_CURPERM", "Permissions courantes :"); 
define("_AM_FORM_CURPERMLINKS", "Boîtes de sélections liées courantes :"); 
define("_AM_FORM_PERMVIEW", "Visualiser");
define("_AM_FORM_PERMADD", "Ajout/Mise à jour");
define("_AM_FORM_PERMADMIN", "Admin");
define("_AM_FORM_SUBMITBUTTON", "Montrer les nouvelles permissions"); // not used

define("_AM_FORMLINK_PICK", "Choisir une option");
define("_AM_CONFIRM_DEL", "Etes vous certain de désirer effacer ce formulaire !  Veuillez confirmer.");

define("_AM_FRAME_NEW", "Création d'un nouveau Framework:");
define("_AM_FRAME_NEWBUTTON", "Créer maintenant !");
define("_AM_FRAME_EDIT", "Modification d'un Framework existant :");
define("_AM_FRAME_NONE", "Aucun Framework n'existe");
define("_AM_FRAME_CHOOSE", "Choix d'un Framework");
define("_AM_FRAME_TYPENEWNAME", "Saisissez le nouveau nom ici");
define("_AM_CONFIRM_DEL_FF_FORM", "Etes vous certain de désirer effacer ce set de formulaire dans le framework!  Veuillez confirmer.");
define("_AM_CONFIRM_DEL_FF_FRAME", "Etes vous certain de désirer effacer ce framework! Veuillez confirmer.");
define("_AM_FRAME_NAMEOF", "Nom du Framework :");
define("_AM_FRAME_ADDFORM", "Ajouter une paire de formulaire à ce Framework:");
define("_AM_FRAME_FORMSIN", "Formulaires dans ce Framework: (cliquez sur son nom afin d'en visualiser les détails)");
define("_AM_FRAME_DELFORM", "Enlever");
define("_AM_FRAME_EDITFORM", "Details pour:");
define("_AM_FRAME_DONEBUTTON", "Effectué");
define("_AM_FRAME_NOFORMS", "Il n'y a aucun formulaires dans ce Framework");
define("_AM_FRAME_AVAILFORMS1", "Formulaire un :");
define("_AM_FRAME_AVAILFORMS2", "Formulaire deux :");
define("_AM_FRAME_DELETE", "Effacer un Framework existant :");
define("_AM_FRAME_SUBFORM_OF", "En faire un sous formulaire de :");
define("_AM_FRAME_NOPARENTS", "Aucun formulaire dans le Framework"); 
define("_AM_FRAME_TYPENEWFORMNAME", "Saisissez un nom cour ici");
define("_AM_FRAME_NEWFORMBUTTON", "Ajout de formulaire !");
define("_AM_FRAME_NOKEY", "aucun n'est spécifié!");
define("_AM_FRAME_FORMNAMEPROMPT", "Nom pour ce formulaire dans ce framework:");
define("_AM_FRAME_RELATIONSHIP", "Relations :");
define("_AM_FRAME_ONETOONE", "un pour un");
define("_AM_FRAME_ONETOMANY", "un pour beaucoup");
define("_AM_FRAME_MANYTOONE", "beaucoup pour un");
define("_AM_FRAME_LINKAGE", "Lien entre ces formulaires :");
define("_AM_FRAME_DISPLAY", "Montrer ces formulaires comme un seul ?");
define("_AM_FRAME_UIDLINK", "N° d'utilisateur de la personne l'ayant rempli");
define("_AM_FRAME_UPDATEBUTTON", "Mise à jour de ce Framework avec ces paramètres");
define("_AM_FRAME_UPDATEFORMBUTTON", "Mise à jour de ce formulaire avec ces Handles");
define("_AM_FRAME_UPDATEANDGO", "Mise à jour, et retour à la page précédente");

define("_AM_FRAME_FORMHANDLE", "Handle pour ce formulaire :");
define("_AM_FRAME_FORMELEMENTS", "Elements dans ce formulaire");
define("_AM_FRAME_ELEMENT_CAPTIONS", "Légendes");
define("_AM_FRAME_ELEMENT_HANDLES", "Handles");
define("_AM_FRAME_HANDLESHELP", "Utilisez cette page pour spécifier les <i>Handles</i> pour ce formulaire et ses éléments.  Handles sont des noms courts qui peuvent être utilisé en récéfence à ce formulaire ou à ces éléments en dehors du cadre de ce module.");

define("_FORM_EXP_CREE","Le fichier a été exporté avec succès");

//template constants added by jwe 7/24/04
define("_formulize_TEMP_ADDENTRY", "Ajout d'une entrée");
define("_formulize_TEMP_VIEWENTRIES", "Visualisation des entrées");
define("_formulize_TEMP_ADDINGENTRY", "Ajouter une entrée");
define("_formulize_TEMP_VIEWINGENTRIES", "Visualiser les entrées");
define("_formulize_TEMP_SELENTTITLE", "Votre entrée dans '");
define("_formulize_TEMP_SELENTTITLE_GS", "Toutes les entrées dans '");
define("_formulize_TEMP_SELENTTITLE_RP", "Recherche de résultats pour '");
define("_formulize_TEMP_SELENTTITLE2_RP", "Calcul de résultats pour '");
define("_formulize_TEMP_VIEWTHISENTRY", "Visualiser cette entrée");
define("_formulize_TEMP_EDITINGENTRY", "Editer cette entrée");
define("_formulize_TEMP_NOENTRIES", "Aucune entrée.");
define("_formulize_TEMP_ENTEREDBY", "Saisie par : ");
define("_formulize_TEMP_ENTEREDBYSINGLE", "Saisie ");
define("_formulize_TEMP_ON", "Actif");
define("_formulize_TEMP_QYES", "Oui");
define("_formulize_TEMP_QNO", "Non");
define("_formulize_REPORT_ON", "Activer le mode rapport");
define("_formulize_REPORT_OFF", "Désactiver le mode rapport");
define("_formulize_VIEWAVAILREPORTS", "Visualisation de Rapport:");
define("_formulize_NOREPORTSAVAIL", "Vu par défaut");
define("_formulize_CHOOSEREPORT", "Vue par défaut");
define("_formulize_REPORTING_OPTION", "Options de journalisation");
define("_formulize_SUBMITTEXT", "Appliquer");
define("_formulize_RESETBUTTON", "Netoyer");
define("_formulize_QUERYCONTROLS", "Contrôles des requêtes");
define("_formulize_SEARCH_TERMS", "Termes de recherche :");
define("_formulize_STERMS", "Termes:");
define("_formulize_AND", "ET");
define("_formulize_OR", "OU");
define("_formulize_SEARCH_OPERATOR", "Opérateur:");
define("_formulize_NOT", "NON");
define("_formulize_LIKE", "COMME");
define("_formulize_NOTLIKE", "DIFFERENT DE");
define("_formulize_CALCULATIONS", "Calcul :");
define("_formulize_SUM", "Somme");
define("_formulize_SUM_TEXT", "Total des toutes les valeurs dans la colonne :");
define("_formulize_AVERAGE", "Moyenne");
define("_formulize_AVERAGE_INCLBLANKS", "Valeur moyenne dans la colonne:");
define("_formulize_AVERAGE_EXCLBLANKS", "Valeur moyenne exclue des blancs et des zéro:");
define("_formulize_MINIMUM", "Minimum");
define("_formulize_MINIMUM_INCLBLANKS", "Valeur minimum dans la colonne :");
define("_formulize_MINIMUM_EXCLBLANKS", "Valeur minimum exclue des blancs et des zéros:");
define("_formulize_MAXIMUM", "Maximum");
define("_formulize_MAXIMUM_TEXT", "Valeur maximum dans la colonne :");
define("_formulize_COUNT", "Comptage");
define("_formulize_COUNT_INCLBLANKS", "Nombre de valeurs totales dans la colonne :");
define("_formulize_COUNT_ENTRIES", "Nombre total d'entrées dans la colonne :");
define("_formulize_COUNT_NONBLANKS", "Total d'entrées non-blanc, non-zéro dans la colonne :");
define("_formulize_COUNT_EXCLBLANKS", "Total de valeurs non-blanc, non-zéro dans la colonne :");
define("_formulize_COUNT_PERCENTBLANKS", "Percentage de valeur non-blanc, non-zéro :");
define("_formulize_COUNT_UNIQUES", "total de valeur unique dans la colonne :");
define("_formulize_COUNT_UNIQUEUSERS", "Nombre d'utilisateurs ayant effectués des entrées dans la colonne :");
define("_formulize_PERCENTAGES", "Percentages");
define("_formulize_PERCENTAGES_VALUE", "Valeur :");
define("_formulize_PERCENTAGES_COUNT", "Comptage :");
define("_formulize_PERCENTAGES_PERCENT", "Total de % :");
define("_formulize_PERCENTAGES_PERCENTEXCL", "% excl. blancs:");
define("_formulize_SORTING_ORDER", "Ordre de tri :");
define("_formulize_SORT_PRIORITY", "Ordre de priorité :");
define("_formulize_NONE", "Aucun");
define("_formulize_CHANGE_COLUMNS", "Changer pour visualiser des colonnes différentes :");
define("_formulize_CHANGE", "Changer");
define("_formulize_SEARCH_HELP", "Si vous spécifiez les termes de recherche dans plus d'une colonne, les paramètres interchamps ET/OU détermine si rechercher les entrées qui s'assortissent dans toutes les colonnes (ET), ou en fait à une colonne (OU).<br><br>L'option ET/OU détermine si les boites de termes s'associe aux entrées de tous les termes (ET), ou à un des terme (OU).<br><br>Utilisez une virgule pour séparer les termes.  Utilisez [,] entre chaque terme.");
define("_formulize_SORT_HELP", "Vous pouvez opérer un tri par n'importe quel élément, excepté celui qui accepte les entrées multiples, comme les cases à cocher.");
define("_formulize_REPORTSCOPE", "Sélection du périmètre du rapport :");
define("_formulize_SELECTSCOPEBUTTON", "Sélection");
define("_formulize_GROUPSCOPE", "Groupe : ");
define("_formulize_USERSCOPE", "Utilisateur : ");
define("_formulize_GOREPORT", "Go");
define("_formulize_REPORTSAVING", "Sauvegarder cette requête comme l'une de votre rapport :");
define("_formulize_SAVEREPORTBUTTON", "Sauvegarder");
define("_formulize_REPORTNAME", "Nom de rapport:");
define("_formulize_ANDORTITLE", "Paramètre d'interchamps ET/OU :");

define("_formulize_PUBLISHINGOPTIONS", "Options de publication :");
define("_formulize_PUBLISHREPORT", "Publier ce rapport à l'intention des autres utilisateurs.");
define("_formulize_PUBLISHNOVE", "enlever 'Visualiser cette entrée' des liens du rapport (aussi les utilisateurs ne pourront plus voir les détails de chaque entrée).");
define("_formulize_PUBLISHCALCONLY", "enlever en totalité la liste des entrées, et n'exposer que les calculs globaux.");


define("_formulize_LOCKSCOPE", "<b>Sauvegarder le rapport courant avec son périmêtre vérouillé</b> (autrement des navigateurs sont limités à leur périmètre par défaut).");
define("_formulize_REPORTPUBGROUPS", "Sélection du groupe pour lequel publier :");
define("_formulize_REPORTDELETE", "Effacer le rapport sélectionné courant:");
define("_formulize_DELETE", "Effacer");
define("_formulize_DELETECONFIRM", "Cochez cette boîte ainsi que le bouton effacer");

define("_formulize_REPORTEXPORTING", "Exporter cette requête comme un fichier de tableur:");
define("_formulize_EXPORTREPORTBUTTON", "Exporter");
define("_formulize_EXPORTEXPLANATION", "Cliquez sur le bouton <b>Exporter</b> pour télécharger un fichier au format de tableur lisible contenant les résultats de la requête courante.  Note : vous pouvez utiliser les délimitateurs utiliser entre les champs.  Si le caractère de délimitation que vous choisissez est présent dans vos résultats, le fichier de tableur ne s'ouvrira pas correctement, aussi veillez à essayer avec plusieurs délimitateurs.");
define("_formulize_FILEDELTITLE", "Délimitateur de champs :");
define("_formulize_FDCOMMA", "Virgugle");
define("_formulize_FDTAB", "Tabuslation");
define("_formulize_FDCUSTOM", "Customisé");
define("_formulize_exfile", "donnees_exportees_");
define("_formulize_DLTEXT", "<b>Cliquez droit sur le lien suivant et sélectionnez <i>Sauvegarde</i>.</b> (Ctrl-click sur Mac.)  Une fois le fichier sauvegardé sur votre ordinateur, vous pourrez l'ouvrir avec un logiciel tableur. Si les champs ne s'alignent pas proprement lorque vous ouvre le fichier, tentez d'exporter avec un délimitateur différent.");
define("_formulize_DLHEADER", "Votre fichier est pret à être téléchargé.");

define("_formulize_PICKAPROXY", "Aucun utilisateur proxy sélectionné");
define("_formulize_PROXYFLAG", "(Proxy)");

define("_AM_SELECT_PROXY", "Est-ce que cette information concerne un autre utilisateur?");

define("_formulize_DELBUTTON", "Effacer");
define("_formulize_DELCONF", "Etes-vous certain de vouloir effacer cette information !  Veuillez confirmer.");
define("_formulize_DONE", "Tout Fini");
define("_formulize_SAVE", "Enregistrer");
define("_formulize_TEMP_ON", "sur"); 
define("_FORM_ANON_USER","Quelqu'un sur l'Internet");
define("_formulize_FD_ABOUT", "Détails sur cette information:");
define("_formulize_FD_CREATED", "Créé par: ");
define("_formulize_FD_MODIFIED", "Modifiée par: ");
define("_formulize_FD_NEWENTRY", "Ceci est une nouvelle information qui n'a pas déjà été enregistrée.");
define("_formulize_INFO_SAVED", "Votre information a été enregistrée.");
define("_formulize_INFO_DONE1", "Si vous avez fini, cliquez le bouton <i>");
define("_formulize_INFO_DONE2", "</i>.");
define("_formulize_INFO_CONTINUE1", "Vous pouvez mettre à jour votre information ci-dessous.");
define("_formulize_INFO_CONTINUE2", "Vous pouvez enregistrer toute autre information en complétant le formulaire de nouveau.");
define("_formulize_INFO_SAVEBUTTON", "Pour enregistrer vos changements, cliquez le bouton <i>" . _formulize_SAVE . "</i>.");
define("_formulize_INFO_SAVE1", "Pour enregistrer vos changements, cliquez le bouton <i>");
define("_formulize_INFO_SAVE2", "</i>.");
define("_formulize_INFO_NOSAVE", "You can review this entry, but you <i>cannot save changes</i>.");
define("_formulize_INFO_MAKENEW", "Vous pouvez enregistrer votre nouvelle information en complétant le formulaire ci-dessous.");

define("_formulize_ADD", "Add");
define("_formulize_ADD_ONE", "Add One");
define("_formulize_ADD_ENTRIES", "entries");
define("_formulize_DELETE_CHECKED", "Delete checked items");
define("_formulize_ADD_HELP", "Add an entry in this section by clicking the <i>Add</i> button.");
define("_formulize_ADD_HELP2", "See an entire entry by clicking the <i>View</i> button.");
define("_formulize_ADD_HELP3", "Update an entry by changing the values on the right.");
define("_formulize_ADD_HELP4", "Delete an entry by checking the boxes and clicking the button below.");
define("_formulize_SUBFORM_VIEW", "View full entry");
define("_formulize_CONFIRMNOSAVE", "You have not saved your changes!  Is that OK?  Click 'Cancel' to return to the form and then click 'Save' to save your changes.");


define("_formulize_PRINTVIEW", "Version imprimable");

// account creation
define("_formulize_ACTDETAILS", "Informations sur le compte:");
define("_formulize_PERSONALDETAILS", "Information personnelle:");
define("_formulize_TYPEPASSTWICE_NEW", "(Entrez deux fois votre mot de passe.  Doit contenir au moins ");
define("_formulize_TYPEPASSTWICE_CHANGE", "(Pour modifier votre mot de passe, entrez deux fois un nouveau mot de passe.  Doit contenir au moins ");
define("_formulize_CDISPLAYMODE", "Préférences d'affichage des commentaires/inscriptions");
define("_formulize_CSORTORDER", "Préférences de classement des commentaires/inscriptions");
define("_formulize_CREATEACT", "Create My Account!");
define("_formulize_ACTCREATED", "Your account has been created and you are being logged into the site now.");
define("_formulize_USERNAME_HELP1", " (Ne doit pas contenir d'espaces.  Doit être entre ");
define("_formulize_USERNAME_HELP2", " et ");
define("_formulize_USERNAME_HELP3", " caractères)");
define("_formulize_PASSWORD_HELP1", " caractères)");

// "Other" for checkboxes and radio buttons:
define("_formulize_OPT_OTHER", "Autre: ");

// multi-page forms
define("_formulize_DMULTI_THANKS", "<h1>You're done!</h1><p>Thanks for taking the time to fill in that form.  We really appreciate it.</p>");
define("_formulize_DMULTI_NEXT", "Save and Continue >>");
define("_formulize_DMULTI_PREV", "<< Save and Go Back");
define("_formulize_DMULTI_SAVE", "Save and Finish >>");
define("_formulize_DMULTI_PAGE", "Page");
define("_formulize_DMULTI_OF", "Of");
define("_formulize_DMULTI_SKIP", "One or more pages was skipped because they don't apply");
define("_formulize_DMULTI_ALLDONE", "Leave this form and continue browsing the site");

// CALENDAR
define("_formulize_CAL_ADD_ITEM", "Click to add a new item on this day.");
define("_formulize_CAL_RETURNFROMMULTI", "Return to the Calendar");

define("_formulize_CAL_MONTH_01", "January");
define("_formulize_CAL_MONTH_02", "February");
define("_formulize_CAL_MONTH_03", "March");
define("_formulize_CAL_MONTH_04", "April");
define("_formulize_CAL_MONTH_05", "May");
define("_formulize_CAL_MONTH_06", "June");
define("_formulize_CAL_MONTH_07", "July");
define("_formulize_CAL_MONTH_08", "August");
define("_formulize_CAL_MONTH_09", "September");
define("_formulize_CAL_MONTH_10", "October");
define("_formulize_CAL_MONTH_11", "November");
define("_formulize_CAL_MONTH_12", "December");

define("_formulize_CAL_WEEK_1", "Sunday");
define("_formulize_CAL_WEEK_2", "Monday");
define("_formulize_CAL_WEEK_3", "Tuesday");
define("_formulize_CAL_WEEK_4", "Wednesday");
define("_formulize_CAL_WEEK_5", "Thursday");
define("_formulize_CAL_WEEK_6", "Friday");
define("_formulize_CAL_WEEK_7", "Saturday");
define("_formulize_CAL_WEEK_1_3ABRV", "Sun");
define("_formulize_CAL_WEEK_2_3ABRV", "Mon");
define("_formulize_CAL_WEEK_3_3ABRV", "Tue");
define("_formulize_CAL_WEEK_4_3ABRV", "Wed");
define("_formulize_CAL_WEEK_5_3ABRV", "Thu");
define("_formulize_CAL_WEEK_6_3ABRV", "Fri");
define("_formulize_CAL_WEEK_7_3ABRV", "Sat");



?>
