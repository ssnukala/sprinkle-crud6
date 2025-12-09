<?php

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/sprinkle-crud6
 * @copyright Copyright (c) 2013-2024 Alexander Weissman & Louis Charette
 * @license   https://github.com/userfrosting/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

/**
 * French message token translations for the 'crud6' sprinkle.
 *
 * @author Alexander Weissman
 */
return [
    'CRUD6' => [
        '@TRANSLATION' => 'CRUD6',
        1 => 'CRUD6',
        2 => 'CRUD6 Toutes les lignes',

        'CREATE' => [
            '@TRANSLATION'  => 'Créer {{model}}',
            0 => 'Créer {{model}}',
            'SUCCESS'       => '{{model}} créé avec succès',
            'SUCCESS_TITLE' => 'Créé !',
            'ERROR'         => 'Échec de la création de {{model}}',
            'ERROR_TITLE'   => 'Erreur de création',
        ],
        'CREATION_SUCCESSFUL' => '{{model}} <strong>{{name}}</strong> créé avec succès',
        'DELETE' => [
            '@TRANSLATION'  => 'Supprimer {{model}}',
            0 => 'Supprimer {{model}}',
            'SUCCESS'       => '{{model}} supprimé avec succès',
            'SUCCESS_TITLE' => 'Supprimé !',
            'ERROR'         => 'Échec de la suppression de {{model}}',
            'ERROR_TITLE'   => 'Erreur de suppression',
        ],
        'DELETE_CONFIRM'      => 'Êtes-vous sûr de vouloir supprimer la ligne de {{model}} ?',
        'DELETE_DEFAULT'      => "Vous ne pouvez pas supprimer le {{model}} <strong>{{id}}</strong> car c'est le {{model}} par défaut pour les nouveaux utilisateurs.",
        'DELETE_YES'          => 'Oui, supprimer {{model}}',
        'DELETION_SUCCESSFUL' => '{{model}} <strong>{{name}}</strong> supprimé avec succès',
        'EDIT' => [
            '@TRANSLATION' => 'Modifier {{model}}',
            0 => 'Modifier {{model}}',
            'SUCCESS' => '{{model}} récupéré pour modification',
            'ERROR'   => 'Échec de la récupération de {{model}}',
        ],
        'EXCEPTION'           => 'Erreur {{model}}',
        'ICON'                => 'Icône {{model}}',
        'ICON_EXPLAIN'        => 'Icône pour les membres {{model}}',
        'INFO_PAGE'           => 'Voir et modifier les détails de {{model}}.',
        'NAME'                => 'Nom {{model}}',
        'NAME_IN_USE'         => 'Un {{model}} nommé <strong>{{id}}</strong> existe déjà',
        'NAME_EXPLAIN'        => 'Veuillez entrer un nom pour le {{model}}',
        'NONE'                => 'Aucun {{model}}',
        'NOT_EMPTY'           => "Vous ne pouvez pas faire cela car il y a encore des utilisateurs associés au {{model}} <strong>{{id}}</strong>.",
        'NOT_FOUND'           => '{{model}} non trouvé',
        'PAGE'                => '{{model}}',
        'PAGE_DESCRIPTION'    => 'Une liste des {{model}} pour votre site. Fournit des outils de gestion pour modifier et supprimer {{model}}.',
        'UPDATE' => [
            '@TRANSLATION'  => 'Mettre à jour {{model}}',
            0 => 'Détails mis à jour pour {{model}} <strong>{{id}}</strong>',
            'SUCCESS'       => '{{model}} mis à jour avec succès',
            'SUCCESS_TITLE' => 'Mis à jour !',
            'ERROR'         => 'Échec de la mise à jour de {{model}}',
            'ERROR_TITLE'   => 'Erreur de mise à jour',
        ],
        'UPDATE_FIELD_SUCCESSFUL' => '{{field}} mis à jour avec succès pour {{model}}',
        'TOGGLE_CONFIRM' => 'Êtes-vous sûr de vouloir basculer <strong>{{field}}</strong> pour <strong>{{title}}</strong>?',
        'TOGGLE_SUCCESS' => 'Basculé {{field}} avec succès',
        'RELATIONSHIP' => [
            '@TRANSLATION'  => 'Relations',
            'ATTACH_SUCCESS' => '{{count}} {{relation}} attaché(s) avec succès à {{model}}',
            'DETACH_SUCCESS' => '{{count}} {{relation}} détaché(s) avec succès de {{model}}',
        ],

        // Panel/Breadcrumb translations (nested under CRUD6 to match dot notation)
        'ADMIN_PANEL' => 'Panneau d\'administration CRUD6',

        // Validation translations used in forms and modals (nested under CRUD6 for proper scoping)
        'VALIDATION' => [
            'ENTER_VALUE'         => 'Entrer une valeur',
            'CONFIRM'             => 'Confirmer',
            'CONFIRM_PLACEHOLDER' => 'Confirmer la valeur',
            'MIN_LENGTH_HINT'     => 'Minimum {{min}} caractères',
            'MATCH_HINT'          => 'Les valeurs doivent correspondre',
            'FIELDS_MUST_MATCH'   => 'Les champs doivent correspondre',
            'MIN_LENGTH'          => 'Minimum {{min}} caractères requis',
        ],

    ],

    // Action translations used in modals (backward compatibility - kept at root for legacy support)
    // Note: New code should use WARNING_CANNOT_UNDONE from UserFrosting core instead
    'ACTION' => [
        'CANNOT_UNDO' => 'Cette action ne peut pas être annulée.',
    ],

    // Validation translations (backward compatibility - duplicated at root for legacy support)
    // Note: New code should use CRUD6.VALIDATION.* keys for proper namespacing
    // IMPORTANT: This duplication is intentional to support both namespace structures:
    //   - New code: CRUD6.VALIDATION.ENTER_VALUE (preferred)
    //   - Old code: VALIDATION.ENTER_VALUE (backward compatible)
    // The translateWithFallback() helper tries CRUD6.* first, then falls back to root level
    // Keep these in sync with CRUD6.VALIDATION.* keys above
    'VALIDATION' => [
        'ENTER_VALUE'         => 'Entrer une valeur',
        'CONFIRM'             => 'Confirmer',
        'CONFIRM_PLACEHOLDER' => 'Confirmer la valeur',
        'MIN_LENGTH_HINT'     => 'Minimum {{min}} caractères',
        'MATCH_HINT'          => 'Les valeurs doivent correspondre',
        'FIELDS_MUST_MATCH'   => 'Les champs doivent correspondre',
        'MIN_LENGTH'          => 'Minimum {{min}} caractères requis',
    ],

    // Panel/Breadcrumb translations (flat keys for backward compatibility)
    'CRUD6_PANEL'               => 'Gestion CRUD6',
    'C6ADMIN_PANEL'             => 'Panneau d\'administration CRUD6',
    'CRUD6_ADMIN_PANEL'         => 'Panneau d\'administration CRUD6',  // Fallback for CRUD6.ADMIN_PANEL
];
