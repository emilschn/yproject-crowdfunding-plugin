<?php

/**
 * Classe de gestion des appels Post et Get
 */
class WDGPostActions {
    private static $class_name = 'WDGPostActions';

    /**
     * Initialise la liste des actions post
     */
    public static function init_actions() {

    }

    /**
     * Ajoute une action WordPress à exécuter en Post/get
     * @param string $action_name
     */
    public static function add_action($action_name) {
        add_action('admin_post_' . $action_name, array(WDGPostActions::$class_name, $action_name));
        add_action('admin_post_nopriv_' . $action_name, array(WDGPostActions::$class_name, $action_name));
    }
}