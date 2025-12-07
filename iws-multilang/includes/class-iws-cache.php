<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IWS_ML_Cache {

    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'iws_translations';
    }

    public function get_options() {
        $opts = get_option( 'iws_ml_options' );
        return is_array( $opts ) ? $opts : array();
    }

    public function get( $post_id, $lang, $field ) {
        global $wpdb;

        $opts          = $this->get_options();
        $cache_enabled = isset( $opts['cache_enabled'] ) ? (int) $opts['cache_enabled'] : 1;
        $cache_days    = isset( $opts['cache_days'] ) ? (int) $opts['cache_days'] : 30;

        if ( ! $cache_enabled ) {
            return false;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT translated_text, updated_at FROM {$this->table} WHERE post_id = %d AND lang = %s AND field = %s",
                $post_id,
                $lang,
                $field
            )
        );

        if ( ! $row ) {
            return false;
        }

        $updated = strtotime( $row->updated_at );
        if ( ! $updated ) {
            return false;
        }

        $expire = $updated + ( $cache_days * DAY_IN_SECONDS );
        if ( time() > $expire ) {
            return false;
        }

        return $row->translated_text;
    }

    public function set( $post_id, $lang, $field, $text ) {
        global $wpdb;

        $now = current_time( 'mysql' );

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table} WHERE post_id = %d AND lang = %s AND field = %s LIMIT 1",
                $post_id,
                $lang,
                $field
            )
        );

        $data = array(
            'post_id'        => $post_id,
            'lang'           => $lang,
            'field'          => $field,
            'translated_text'=> $text,
            'updated_at'     => $now,
        );

        if ( $exists ) {
            $wpdb->update(
                $this->table,
                $data,
                array( 'id' => $exists ),
                array( '%d', '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            $wpdb->insert(
                $this->table,
                $data,
                array( '%d', '%s', '%s', '%s', '%s' )
            );
        }
    }
}
