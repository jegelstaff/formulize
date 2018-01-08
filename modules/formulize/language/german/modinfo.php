<?php
// Module Info

// The name of this module
define("_MI_formulize_NAME","Formulare");

// A brief description of this module
define("_MI_formulize_DESC","Formular für Beschaffung und Datenanalyse");

// admin/menu.php
define("_MI_formulize_ADMENU0","Formularverwaltung");
define("_MI_formulize_ADMENU1","Menü");

// notifications
define("_MI_formulize_NOTIFY_FORM", "Formularbenachrichtigungen");
define("_MI_formulize_NOTIFY_FORM_DESC", "Benachrichtigungen, welche das aktuelle Formular betreffen");
define("_MI_formulize_NOTIFY_NEWEintrag", "neuer Eintrag in einem Formular");
define("_MI_formulize_NOTIFY_NEWEintrag_CAP", "Benachrichtigen Sie mich, wenn jemand einen neuen Eintrag in diesem Formular macht");
define("_MI_formulize_NOTIFY_NEWEintrag_DESC", "Eine Benachrichtigungsoption, welche Benutzer alarmiert, wenn neue Einträge in einem Formular gemacht wurden");
define("_MI_formulize_NOTIFY_NEWEintrag_MAILSUB", "neue Einträge in einem Formular");

define("_MI_formulize_NOTIFY_UPEintrag", "aktualisierter Eintrag in einem Formular");
define("_MI_formulize_NOTIFY_UPENTRY_CAP", "benachrichtigen Sie mich, wenn jemand einen Eintrag aktualisiert in diesem Formular");
define("_MI_formulize_NOTIFY_UPENTRY_DESC", "Eine Benachrichtigungsoption, welche Benutzer alarmiert, wenn Einträge in einem Formular aktualisiert wurden");
define("_MI_formulize_NOTIFY_UPENTRY_MAILSUB", "aktualisierter Eintrag in einem Formular");

define("_MI_formulize_NOTIFY_DELEintrag", "gelöschter Eintrag in einem Formular");
define("_MI_formulize_NOTIFY_DELEintrag_CAP", "benachrichtigen Sie mich,, wenn jemand einen Eintrag in diesem Formular gelöscht hat");
define("_MI_formulize_NOTIFY_DELEintrag_DESC", "Eine Benachrichtigungsoption, welche Benutzer alarmiert, wenn Einträge in einem Formular gelöscht wurden");
define("_MI_formulize_NOTIFY_DELEintrag_MAILSUB", "gelöschter Eintrag in einem Formular");


//	preferences
define("_MI_formulize_TEXT_WIDTH","Vorgabebreite von Textfeldern");
define("_MI_formulize_TEXT_MAX","vorgegebene Maximallänge von Textfeldern");
define("_MI_formulize_TAREA_ROWS","vorgegebene Zeilen des Textbereichs");
define("_MI_formulize_TAREA_COLS","vorgegebene Spalten des Textbereichs");
define("_MI_formulize_DELIMETER","vorgegebenes Trennzeichen für Checkboxen und Radiobuttons");
define("_MI_formulize_DELIMETER_SPACE","Leerzeichen");
define("_MI_formulize_DELIMETER_BR","Zeilenumbruch");
define("_MI_formulize_SEND_METHOD","Sendeauswahl");
define("_MI_formulize_SEND_METHOD_DESC","Beachten Sie: eingereichte Formulare unbekannter Benutzer können nicht durch Benutzung von PN's versendet werden.");
define("_MI_formulize_SEND_METHOD_MAIL","Mail");
define("_MI_formulize_SEND_METHOD_PM","private Nachricht");
define("_MI_formulize_SEND_GROUP","sende zur Gruppe");
define("_MI_formulize_SEND_ADMIN","sende nur zur Adminseite");
define("_MI_formulize_SEND_ADMIN_DESC","Einstellungen der \"Sende zur Gruppe\" wird ignoriert");
define("_MI_formulize_PROFILEFORM","welches Formular soll benutzt werden als Teil des Registrierungsprozesses, wenn Konten angeschaut und geändert werden? (verwendet das Registrierungscodemodul)");

define("_MI_formulize_ALL_DONE_SINGLES","Soll der 'alles erledigt' Button am Ende des Formular erscheinen, wenn ein Eintrag bearbeitet wird, und einen neuen Eintrag in einem 'Ein-Eintrag-pro-Benutzer' Formular erstellen?");
define("_MI_formulize_SINGLESDESC","Der 'alles erledigt' Button wird verwendet, um ein Formular zu verlassen, ohne die Speicherung der Daten im Formular. Wenn Sie Änderungen gemacht haben in den Daten eines Formulars und Sie anschließend 'alles erledigt' anklicken, ohne vorher zuerst 'sichern' zu klicken, erhalten Sie eine Warnmeldung, das Ihre Daten nicht gesichert wurden. Der 'speichern' Button und der 'alles erledigt' Button arbeiten Hand in Hand, normalerweise gibt es keinen Weg, auf einmal Daten zu speichern und das Formular zu verlassen.  Das irritiert einige Benutzer.  Setzen Sie diese Option auf 'JA' um den 'alles erledigt' Button zu entfernen die Verhaltensweise des 'Speichern' Buttons auf 'sichern-und-verlassen-des-Formulars-alles-auf-einmal' zu setzen. Diese Option hat keine Bewandnis, wo der Benutzer mehrfache Einträge in ein Formular hinzufügt (so dass das Formular ins Leere läd, solange Sie auf 'speichern' drücken).");

define("_MI_formulize_LOE_limit", "Was ist die maximale Anzahl von Einträgen, welche in einer Liste von Einträgen angezeigt werden sollen, ohne vom Benutzer bestätigt werden zu müssen?");
define("_MI_formulize_LOE_limit_DESC", "Wenn ein Datensatz sehr lang ist, kann das Anzeige einer Liste mit Einträgen ziemlich lang dauern, durchaus einige Minuten. Nutzen Sie diese Vorgabe, um die maximale Anzahl von Einträgen zu definieren, welches Ihr System auf einmal anzuzeigen versuchen soll. Wenn ein Datensatz mehr Einträge als das Limit enthält, wird der Benutzer gefragt, ob die Datensatzeinträge geladen werden oder nicht.");
       
define("_MI_formulize_USETOKEN", "Verwenden Sie den Systemsicherheitsschlüssel, um eine Formulareinreichung zu bestätigen?");
define("_MI_formulize_USETOKENDESC", "standardmäßig werden keine Daten gespeichert, wenn ein Formular eingereicht wird, bis Formulize mittels gültigem Schlüssel überprüfen kann, welcher mit dem Formular eingereicht wurde. Dies ist ein teilweise Abwehr gegenüber 'Crosssitescripting-Attacken', um sicher zu stellen, das nur Personen, welche Ihre Webseite besuchen, Formulare einreichen können.  Unter Umständen, bei Verwendung einer Firewall oder anderen Gegebenheiten, kann der Schlüssel nicht überprüft werden wie er soll. Wenn Ihnen dies passieren sollte, können Sie den Systemschlüssel für Formulize hier deaktivieren.");
       

// The name of this module
define("_MI_formulizeMENU_NAME","Mein Menü");

// A brief description of this module
define("_MI_formulizeMENU_DESC","zeigt ein individuelles, konfigurierbares Menü in einem Block an");

// Names of blocks for this module (Not all module has blocks)
define("_MI_formulizeMENU_BNAME","Formularmenü");

// Version
define("_MI_VERSION","2.0b");
?>