<?php
// Chaînes de l'interface de l'Assistant IA

// Auth
define('_MD_FORMULIZE_MUST_BE_LOGGED_IN',        'Vous devez être connecté pour utiliser l\'Assistant IA.');

// En-tête / panneau de paramètres
define('_MD_FORMULIZE_AI_PAGE_TITLE',             'Gwynian - L\'Assistant IA Formulize');
define('_MD_FORMULIZE_AI_TOGGLE_SETTINGS_TITLE',  'Afficher/masquer les paramètres');
define('_MD_FORMULIZE_AI_SETTINGS_CLOSE',         'fermer ✕');
define('_MD_FORMULIZE_AI_PROVIDER_LABEL',         'Fournisseur :');
define('_MD_FORMULIZE_AI_PROVIDER_CLAUDE',        'Claude (Anthropic)');
define('_MD_FORMULIZE_AI_PROVIDER_GEMINI',        'Gemini (Google)');
define('_MD_FORMULIZE_AI_PROVIDER_OLLAMA',        'Ollama (Local)');
define('_MD_FORMULIZE_AI_MODEL_LABEL',            'Modèle :');
define('_MD_FORMULIZE_AI_API_KEY_LABEL',          'Clé API :');
define('_MD_FORMULIZE_AI_API_KEY_PLACEHOLDER',    'Entrez votre clé API');
define('_MD_FORMULIZE_AI_API_KEY_OLLAMA',         'Aucune clé requise');
define('_MD_FORMULIZE_AI_SAVE_SETTINGS_BTN',      'Enregistrer');
define('_MD_FORMULIZE_AI_ACTIVE_TOOLS_LABEL',     'Outils actifs :');
define('_MD_FORMULIZE_AI_TOOLS_ALL_BTN',          'Tous');
define('_MD_FORMULIZE_AI_TOOLS_NONE_BTN',         'Aucun');
define('_MD_FORMULIZE_AI_TOOLS_READ_DATA',        'Lire les données');
define('_MD_FORMULIZE_AI_TOOLS_WRITE_DATA',       'Lire et écrire');
define('_MD_FORMULIZE_AI_TOOLS_MANAGE_FORMS',     'Gérer les formulaires');

// Zone de saisie
define('_MD_FORMULIZE_AI_CHAT_PLACEHOLDER',       'Posez-moi une question sur Formulize...');
define('_MD_FORMULIZE_AI_SEND_BTN',               'Envoyer');

// Panneau d'activité
define('_MD_FORMULIZE_AI_ACTIVITY_TOGGLE_TITLE',  'Afficher/masquer le panneau de contexte');
define('_MD_FORMULIZE_AI_ACTIVITY_LABEL',         'Contexte d\'activité');
define('_MD_FORMULIZE_AI_ACTIVITY_SHOW',          '▶ afficher ce que l\'IA voit');
define('_MD_FORMULIZE_AI_ACTIVITY_HIDE',          '▼ masquer');
define('_MD_FORMULIZE_AI_ACTIVITY_FOOTER',        'Mis à jour en direct depuis tous les onglets · 30 dernières min · Ajouté à chaque message');

// Statut / MCP
define('_MD_FORMULIZE_AI_INITIALIZING',           'Initialisation...');
define('_MD_FORMULIZE_AI_FETCHING_TOOLS',         'MCP : Récupération des outils...');
define('_MD_FORMULIZE_AI_NO_TOOLS_FOUND',         'MCP : Aucun outil trouvé');
define('_MD_FORMULIZE_AI_MCP_ERROR',              'MCP : Erreur');

// Gabarits de statut — les jetons {placeholder} sont remplacés en JS
define('_MD_FORMULIZE_AI_SETTINGS_SAVED',         'Paramètres enregistrés. Fournisseur : {provider}, Modèle : {model}');
define('_MD_FORMULIZE_AI_ACTIVE_TOOLS_STATUS',    'Outils actifs : {active}/{total}  ·  Modèle : {model}');
define('_MD_FORMULIZE_AI_MODEL_STATUS',           'Modèle : {model}');

// Messages d'erreur
define('_MD_FORMULIZE_AI_SAVE_FIRST',             'Veuillez d\'abord enregistrer vos paramètres.');
define('_MD_FORMULIZE_AI_GEMINI_SAVE_FIRST',      'Veuillez d\'abord enregistrer vos paramètres pour initialiser Gemini.');
define('_MD_FORMULIZE_AI_FAILED_INIT',            'Échec de l\'initialisation : ');
define('_MD_FORMULIZE_AI_ERROR_OCCURRED',         'Une erreur s\'est produite : ');

// Message de bienvenue (première visite uniquement)
define('_MD_FORMULIZE_AI_WELCOME_ALERT',          "Bienvenue ! Je suis Gwynian, l'Assistant IA Formulize !\n\nPour commencer, sélectionnez un fournisseur d'IA, entrez votre clé API dans le panneau de paramètres, puis cliquez sur Enregistrer.");
define('_MD_FORMULIZE_AI_WELCOME_MSG',            "Je suis Gwynian, votre Assistant IA Formulize ! Sélectionnez un fournisseur, entrez votre clé API et cliquez sur Enregistrer pour démarrer. Une fois connecté, je peux vous aider à explorer votre système Formulize, créer des formulaires, saisir des données, et bien plus encore.");

// Étiquettes des expéditeurs
define('_MD_FORMULIZE_AI_SENDER_YOU',             'Vous');
define('_MD_FORMULIZE_AI_SENDER_AI',              'IA');
define('_MD_FORMULIZE_AI_SENDER_SYSTEM',          'Système');
define('_MD_FORMULIZE_AI_SENDER_ERROR',           'Erreur');

// Animation de réflexion
define('_MD_FORMULIZE_AI_THINKING',               'Réflexion');

// Interface des appels d'outils
define('_MD_FORMULIZE_AI_TOOL_PENDING',           '⏳ Outil : ');
define('_MD_FORMULIZE_AI_TOOL_OK',                '⚙ Outil : ');
define('_MD_FORMULIZE_AI_TOOL_ERROR',             '⚠ Outil : ');
define('_MD_FORMULIZE_AI_TOOL_EXPAND',            '▶ développer');
define('_MD_FORMULIZE_AI_TOOL_COLLAPSE',          '▼ réduire');
define('_MD_FORMULIZE_AI_TOOL_PARAMS_LABEL',      'Paramètres :');
define('_MD_FORMULIZE_AI_TOOL_NO_PARAMS',         '(aucun paramètre)');
define('_MD_FORMULIZE_AI_TOOL_RESPONSE_LABEL',    'Réponse :');
define('_MD_FORMULIZE_AI_TOOL_WAITING',           'En attente de la réponse...');
define('_MD_FORMULIZE_AI_TOOL_NO_OUTPUT',         'Aucun résultat');
define('_MD_FORMULIZE_AI_TOOL_RESPONSE_ERROR',    'Erreur :');
define('_MD_FORMULIZE_AI_TOOL_NET_ERROR',         'Erreur réseau : ');
define('_MD_FORMULIZE_AI_TOOL_CALL_ERROR',        'Erreur : ');

// Descriptions des événements d'activité
define('_MD_FORMULIZE_AI_EVENT_SAVED_NEW',        'Nouvelle entrée enregistrée');
define('_MD_FORMULIZE_AI_EVENT_SAVED',            'Entrée enregistrée');
define('_MD_FORMULIZE_AI_EVENT_DELETED',          'Entrée supprimée');
define('_MD_FORMULIZE_AI_EVENT_GATHERED',         'Données collectées');
define('_MD_FORMULIZE_AI_EVENT_SEARCHING',        ' ; recherche : ');
define('_MD_FORMULIZE_AI_EVENT_SORT',             ' ; tri : ');
define('_MD_FORMULIZE_AI_EVENT_SCOPE',            ' ; portée : ');
define('_MD_FORMULIZE_AI_EVENT_ADMIN_SAVED',      'Configuration admin enregistrée');
define('_MD_FORMULIZE_AI_EVENT_ADMIN_FAILED',     ' [ÉCHEC]');
define('_MD_FORMULIZE_AI_EVENT_VIEWED',           'Consulté : ');
define('_MD_FORMULIZE_AI_EVENT_ADMIN_PAGE',       'Page admin : ');
define('_MD_FORMULIZE_AI_EVENT_SUBMITTED',        'Soumis : ');
define('_MD_FORMULIZE_AI_CONTEXT_HEADER',         '[Activité Formulize récente sur tous les onglets ouverts (30 dernières min) :');

// Invite système envoyée à l'IA
define('_MD_FORMULIZE_AI_SYSTEM_PROMPT',          'Vous êtes l\'Assistant IA Formulize. Vous aidez les utilisateurs à gérer leurs données dans Formulize. Vous avez accès à des outils permettant d\'interagir avec les données et la configuration de Formulize. Soyez concis et utile.');
