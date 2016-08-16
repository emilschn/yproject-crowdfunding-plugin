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
        self::add_action("send_project_mail");
    }

    /**
     * Ajoute une action WordPress à exécuter en Post/get
     * @param string $action_name
     */
    public static function add_action($action_name) {
        add_action('admin_post_' . $action_name, array(WDGPostActions::$class_name, $action_name));
        add_action('admin_post_nopriv_' . $action_name, array(WDGPostActions::$class_name, $action_name));
    }

    public static function send_project_mail(){
        global $wpdb;
        $campaign_id = sanitize_text_field(filter_input(INPUT_POST,'campaign_id'));
        $mail_title = sanitize_text_field(filter_input(INPUT_POST,'mail_title'));
        $mail_content = filter_input(INPUT_POST,'mail_content');
        $mail_recipients = (json_decode("[".filter_input(INPUT_POST,'mail_recipients')."]"));

        NotificationsEmails::project_mail($campaign_id, $mail_title, $mail_content, $mail_recipients);

        wp_safe_redirect( wp_get_referer() );
        die();
    }
}