<?php
/**
 * iRemoteWP class
 *
 */
class iRemoteWPSweep {
    /**
     * Limit the number of items to show for sweep details
     *
     *
     * @access public
     * @var int
     */
    public $limit_details = 500;

    /**
     * Static instance
     *
     *
     * @access private
     * @var $instance
     */
    private static $instance;

    /**
     * Constructor method
     *
     * @access public
     */
    public function __construct() {

    }

    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Count the number of total items belonging to each sweep
     *
     *
     * @access public
     * @param string $name
     * @return int Number of items belonging to each sweep
     */
    public function total_count( $name ) {
        global $wpdb;

        $count = 0;

        switch( $name ) {
            case 'posts':
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts" );
                break;
            case 'postmeta':
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->postmeta" );
                break;
            case 'comments':
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->comments" );
                break;
            case 'commentmeta':
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->commentmeta" );
                break;
            case 'users':
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users" );
                break;
            case 'usermeta':
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->usermeta" );
                break;
            case 'term_relationships':
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->term_relationships" );
                break;
            case 'term_taxonomy':
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->term_taxonomy" );
                break;
            case 'terms':
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->terms" );
                break;
            case 'termmeta':
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->termmeta" );
                break;
            case 'options':
                $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->options" );
                break;
            case 'tables':
                $count = sizeof( $wpdb->get_col( 'SHOW TABLES' ) );
                break;
        }

        return apply_filters( 'iremotewp_sweep_total_count', $count, $name );
    }

    /**
     * Count the number of items belonging to each sweep
     *
     *
     * @access public
     * @param string $name
     * @return int Number of items belonging to each sweep
     */
    public function countsweep( $name ) {
        global $wpdb;

        $count = 0;

        switch( $name ) {
            case 'revisions':
                $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_type = %s", 'revision' ) );
                break;
            case 'auto_drafts':
                $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status = %s", 'auto-draft' ) );
                break;
            case 'deleted_posts':
                $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status = %s", 'trash' ) );
                break;
            case 'unapproved_comments':
                $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_approved = %s", '0' ) );
                break;
            case 'spam_comments':
                $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_approved = %s", 'spam' ) );
                break;
            case 'deleted_comments':
                $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE (comment_approved = %s OR comment_approved = %s)", 'trash', 'post-trashed' ) );
                break;
            case 'transient_options':
                $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(option_id) FROM $wpdb->options WHERE option_name LIKE(%s)", '%_transient_%' ) );
                break;
            case 'orphan_postmeta':
                $count = $wpdb->get_var( "SELECT COUNT(meta_id) FROM $wpdb->postmeta WHERE post_id NOT IN (SELECT ID FROM $wpdb->posts)" );
                break;
            case 'orphan_commentmeta':
                $count = $wpdb->get_var( "SELECT COUNT(meta_id) FROM $wpdb->commentmeta WHERE comment_id NOT IN (SELECT comment_ID FROM $wpdb->comments)" );
                break;
            case 'orphan_usermeta':
                $count = $wpdb->get_var( "SELECT COUNT(umeta_id) FROM $wpdb->usermeta WHERE user_id NOT IN (SELECT ID FROM $wpdb->users)" );
                break;
            case 'orphan_termmeta':
                $count = $wpdb->get_var( "SELECT COUNT(meta_id) FROM $wpdb->termmeta WHERE term_id NOT IN (SELECT term_id FROM $wpdb->terms)" );
                break;
            case 'orphan_term_relationships':
                $count = $wpdb->get_var( "SELECT COUNT(object_id) FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy != 'link_category' AND tr.object_id NOT IN (SELECT ID FROM $wpdb->posts)" );
                break;
            case 'unused_terms':
                $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(t.term_id) FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.count = %d AND t.term_id NOT IN (" . implode( ',', $this->get_excluded_termids() ) . ")", 0 ) );
                break;
            case 'duplicated_postmeta':
                $query = $wpdb->get_col( $wpdb->prepare( "SELECT COUNT(meta_id) AS count FROM $wpdb->postmeta GROUP BY post_id, meta_key, meta_value HAVING count > %d", 1 ) );
                if( is_array( $query ) ) {
                    $count = array_sum( array_map( 'intval', $query ) );
                }
                break;
            case 'duplicated_commentmeta':
                $query = $wpdb->get_col( $wpdb->prepare( "SELECT COUNT(meta_id) AS count FROM $wpdb->commentmeta GROUP BY comment_id, meta_key, meta_value HAVING count > %d", 1 ) );
                if( is_array( $query ) ) {
                    $count = array_sum( array_map( 'intval', $query ) );
                }
                break;
            case 'duplicated_usermeta':
                $query = $wpdb->get_col( $wpdb->prepare( "SELECT COUNT(umeta_id) AS count FROM $wpdb->usermeta GROUP BY user_id, meta_key, meta_value HAVING count > %d", 1 ) );
                if( is_array( $query ) ) {
                    $count = array_sum( array_map( 'intval', $query ) );
                }
                break;
            case 'duplicated_termmeta':
                $query = $wpdb->get_col( $wpdb->prepare( "SELECT COUNT(meta_id) AS count FROM $wpdb->termmeta GROUP BY term_id, meta_key, meta_value HAVING count > %d", 1 ) );
                if( is_array( $query ) ) {
                    $count = array_sum( array_map( 'intval', $query ) );
                }
                break;
            case 'optimize_database':
                $count = sizeof( $wpdb->get_col( 'SHOW TABLES' ) );
                break;
            case 'oembed_postmeta':
                $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(meta_id) FROM $wpdb->postmeta WHERE meta_key LIKE(%s)", '%_oembed_%' ) );
                break;
        }

        return apply_filters( 'iremotewp_sweep_count', $count, $name );
    }

    /**
     * Return more details about a sweep
     *
     * @since 1.0.3
     *
     * @access public
     * @param string $name
     * @return int Number of items belonging to each sweep
     */
    public function details( $name ) {
        global $wpdb;

        $details = array();

        switch( $name ) {
            case 'revisions':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE post_type = %s LIMIT %d", 'revision', $this->limit_details ) );
                break;
            case 'auto_drafts':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE post_status = %s LIMIT %d", 'auto-draft', $this->limit_details ) );
                break;
            case 'deleted_posts':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE post_status = %s LIMIT %d", 'trash', $this->limit_details ) );
                break;
            case 'unapproved_comments':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT comment_author FROM $wpdb->comments WHERE comment_approved = %s LIMIT %d", '0', $this->limit_details ) );
                break;
            case 'spam_comments':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT comment_author FROM $wpdb->comments WHERE comment_approved = %s LIMIT %d", 'spam', $this->limit_details ) );
                break;
            case 'deleted_comments':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT comment_author FROM $wpdb->comments WHERE (comment_approved = %s OR comment_approved = %s) LIMIT %d", 'trash', 'post-trashed', $this->limit_details ) );
                break;
            case 'transient_options':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE(%s) LIMIT %d", '%_transient_%', $this->limit_details ) );
                break;
            case 'orphan_postmeta':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM $wpdb->postmeta WHERE post_id NOT IN (SELECT ID FROM $wpdb->posts) LIMIT %d", $this->limit_details ) );
                break;
            case 'orphan_commentmeta':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM $wpdb->commentmeta WHERE comment_id NOT IN (SELECT comment_ID FROM $wpdb->comments) LIMIT %d", $this->limit_details ) );
                break;
            case 'orphan_usermeta':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM $wpdb->usermeta WHERE user_id NOT IN (SELECT ID FROM $wpdb->users) LIMIT %d", $this->limit_details ) );
                break;
            case 'orphan_termmeta':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM $wpdb->termmeta WHERE term_id NOT IN (SELECT term_id FROM $wpdb->terms) LIMIT %d", $this->limit_details ) );
                break;
            case 'orphan_term_relationships':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT tt.taxonomy FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy != 'link_category' AND tr.object_id NOT IN (SELECT ID FROM $wpdb->posts) LIMIT %d", $this->limit_details ) );
                break;
            case 'unused_terms':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT t.name FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.count = %d AND t.term_id NOT IN (" . implode( ',', $this->get_excluded_termids() ) . ") LIMIT %d", 0, $this->limit_details ) );
                break;
            case 'duplicated_postmeta':
                $query = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(meta_id) AS count, meta_key FROM $wpdb->postmeta GROUP BY post_id, meta_key, meta_value HAVING count > %d LIMIT %d", 1, $this->limit_details ) );
                $details = array();
                if( $query ) {
                    foreach( $query as $meta ) {
                        $details[] = $meta->meta_key;
                    }
                }
                break;
            case 'duplicated_commentmeta':
                $query = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(meta_id) AS count, meta_key FROM $wpdb->commentmeta GROUP BY comment_id, meta_key, meta_value HAVING count > %d LIMIT %d", 1, $this->limit_details ) );
                $details = array();
                if( $query ) {
                    foreach( $query as $meta ) {
                        $details[] = $meta->meta_key;
                    }
                }
                break;
            case 'duplicated_usermeta':
                $query = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(umeta_id) AS count, meta_key FROM $wpdb->usermeta GROUP BY user_id, meta_key, meta_value HAVING count > %d LIMIT %d", 1, $this->limit_details ) );
                $details = array();
                if( $query ) {
                    foreach( $query as $meta ) {
                        $details[] = $meta->meta_key;
                    }
                }
                break;
            case 'duplicated_termmeta':
                $query = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(meta_id) AS count, meta_key FROM $wpdb->termmeta GROUP BY term_id, meta_key, meta_value HAVING count > %d LIMIT %d", 1, $this->limit_details ) );
                $details = array();
                if( $query ) {
                    foreach( $query as $meta ) {
                        $details[] = $meta->meta_key;
                    }
                }
                break;
            case 'optimize_database':
                $details = $wpdb->get_col( 'SHOW TABLES' );
                break;
            case 'oembed_postmeta':
                $details = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM $wpdb->postmeta WHERE meta_key LIKE(%s) LIMIT %d", '%_oembed_%', $this->limit_details ) );
                break;
        }

        return apply_filters( 'wp_sweep_details', $details, $name );
    }

    /**
     * Does the sweeping/cleaning up
     *
     * @since 1.0.0
     *
     * @access public
     * @param string $name
     * @return string Processed message
     */
    public function sweep( $name ) {
        global $wpdb;

        $message = '';

        switch( $name ) {
            case 'revisions':
                $query = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", 'revision' ) );
                if( $query ) {
                    foreach ( $query as $id ) {
                        wp_delete_post_revision( intval( $id ) );
                    }

                    $message = sprintf( __( '%s Revisions Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'auto_drafts':
                $query = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_status = %s", 'auto-draft' ) );
                if( $query ) {
                    foreach ( $query as $id ) {
                        wp_delete_post( intval( $id ), true );
                    }

                    $message = sprintf( __( '%s Auto Drafts Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'deleted_posts':
                $query = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_status = %s", 'trash' ) );
                if( $query ) {
                    foreach ( $query as $id ) {
                        wp_delete_post( $id, true );
                    }

                    $message = sprintf( __( '%s Deleted Posts Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'unapproved_comments':
                $query = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM $wpdb->comments WHERE comment_approved = %s", '0' ) );
                if( $query ) {
                    foreach ( $query as $id ) {
                        wp_delete_comment( intval( $id ), true );
                    }

                    $message = sprintf( __( '%s Unapproved Comments Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'spam_comments':
                $query = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM $wpdb->comments WHERE comment_approved = %s", 'spam' ) );
                if( $query ) {
                    foreach ( $query as $id ) {
                        wp_delete_comment( intval( $id ), true );
                    }

                    $message = sprintf( __( '%s Spam Comments Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'deleted_comments':
                $query = $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM $wpdb->comments WHERE (comment_approved = %s OR comment_approved = %s)", 'trash', 'post-trashed' ) );
                if( $query ) {
                    foreach ( $query as $id ) {
                        wp_delete_comment( intval( $id ), true );
                    }

                    $message = sprintf( __( '%s Trash Comments Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'transient_options':
                $query = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE(%s)", '%_transient_%' ) );
                if( $query ) {
                    foreach ( $query as $option_name ) {
                        if( strpos( $option_name, '_site_transient_' ) !== false ) {
                            delete_site_transient( str_replace( '_site_transient_', '', $option_name ) );
                        } else {
                            delete_transient( str_replace( '_transient_', '', $option_name ) );
                        }
                    }

                    $message = sprintf( __( '%s Transient Options Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'orphan_postmeta':
                $query = $wpdb->get_results( "SELECT post_id, meta_key FROM $wpdb->postmeta WHERE post_id NOT IN (SELECT ID FROM $wpdb->posts)" );
                if( $query ) {
                    foreach ( $query as $meta ) {
                        $post_id = intval( $meta->post_id );
                        if( $post_id === 0 ) {
                            $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s", $post_id, $meta->meta_key ) );
                        } else {
                            delete_post_meta( $post_id, $meta->meta_key );
                        }
                    }

                    $message = sprintf( __( '%s Orphaned Post Meta Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'orphan_commentmeta':
                $query = $wpdb->get_results( "SELECT comment_id, meta_key FROM $wpdb->commentmeta WHERE comment_id NOT IN (SELECT comment_ID FROM $wpdb->comments)" );
                if( $query ) {
                    foreach ( $query as $meta ) {
                        $comment_id = intval( $meta->comment_id );
                        if( $comment_id === 0 ) {
                            $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->commentmeta WHERE comment_id = %d AND meta_key = %s", $comment_id, $meta->meta_key ) );
                        } else {
                            delete_comment_meta( $comment_id, $meta->meta_key );
                        }
                    }

                    $message = sprintf( __( '%s Orphaned Comment Meta Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'orphan_usermeta':
                $query = $wpdb->get_results( "SELECT user_id, meta_key FROM $wpdb->usermeta WHERE user_id NOT IN (SELECT ID FROM $wpdb->users)" );
                if( $query ) {
                    foreach ( $query as $meta ) {
                        $user_id = intval( $meta->user_id );
                        if( $user_id === 0 ) {
                            $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE user_id = %d AND meta_key = %s", $user_id, $meta->meta_key ) );
                        } else {
                            delete_user_meta( $user_id, $meta->meta_key );
                        }
                    }

                    $message = sprintf( __( '%s Orphaned User Meta Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'orphan_termmeta':
                $query = $wpdb->get_results( "SELECT term_id, meta_key FROM $wpdb->termmeta WHERE term_id NOT IN (SELECT term_id FROM $wpdb->terms)" );
                if( $query ) {
                    foreach ( $query as $meta ) {
                        $term_id = intval( $meta->term_id );
                        if( $term_id === 0 ) {
                            $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->termmeta WHERE term_id = %d AND meta_key = %s", $term_id, $meta->meta_key ) );
                        } else {
                            delete_term_meta( $term_id, $meta->meta_key );
                        }
                    }

                    $message = sprintf( __( '%s Orphaned Term Meta Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'orphan_term_relationships':
                $query = $wpdb->get_results( "SELECT tr.object_id, tt.term_id, tt.taxonomy FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy != 'link_category' AND tr.object_id NOT IN (SELECT ID FROM $wpdb->posts)" );
                if( $query ) {
                    foreach ( $query as $tax ) {
                        wp_remove_object_terms( intval( $tax->object_id ), intval( $tax->term_id ), $tax->taxonomy );
                    }

                    $message = sprintf( __( '%s Orphaned Term Relationships Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'unused_terms':
                $query = $wpdb->get_results( $wpdb->prepare( "SELECT tt.term_taxonomy_id, t.term_id, tt.taxonomy FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.count = %d AND t.term_id NOT IN (" . implode( ',', $this->get_excluded_termids() ) . ")", 0 ) );
                if( $query ) {
                    $check_wp_terms = false;
                    foreach ( $query as $tax ) {
                        if( taxonomy_exists( $tax->taxonomy ) ) {
                            wp_delete_term( intval( $tax->term_id ), $tax->taxonomy );
                        } else {
                            $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", intval( $tax->term_taxonomy_id ) ) );
                            $check_wp_terms = true;
                        }
                    }
                    // We need this for invalid taxonomies
                    if( $check_wp_terms ) {
                        $wpdb->get_results( "DELETE FROM $wpdb->terms WHERE term_id NOT IN (SELECT term_id FROM $wpdb->term_taxonomy)" );
                    }

                    $message = sprintf( __( '%s Unused Terms Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'duplicated_postmeta':
                $query = $wpdb->get_results( $wpdb->prepare( "SELECT GROUP_CONCAT(meta_id ORDER BY meta_id DESC) AS ids, post_id, COUNT(*) AS count FROM $wpdb->postmeta GROUP BY post_id, meta_key, meta_value HAVING count > %d", 1 ) );
                if( $query ) {
                    foreach ( $query as $meta ) {
                        $ids = array_map( 'intval', explode( ',', $meta->ids ) );
                        array_pop( $ids );
                        $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_id IN (" . implode( ',', $ids ) . ") AND post_id = %d", intval( $meta->post_id ) ) );
                    }

                    $message = sprintf( __( '%s Duplicated Post Meta Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'duplicated_commentmeta':
                $query = $wpdb->get_results( $wpdb->prepare( "SELECT GROUP_CONCAT(meta_id ORDER BY meta_id DESC) AS ids, comment_id, COUNT(*) AS count FROM $wpdb->commentmeta GROUP BY comment_id, meta_key, meta_value HAVING count > %d", 1 ) );
                if( $query ) {
                    foreach ( $query as $meta ) {
                        $ids = array_map( 'intval', explode( ',', $meta->ids ) );
                        array_pop( $ids );
                        $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->commentmeta WHERE meta_id IN (" . implode( ',', $ids ) . ") AND comment_id = %d", intval( $meta->comment_id ) ) );
                    }

                    $message = sprintf( __( '%s Duplicated Comment Meta Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'duplicated_usermeta':
                $query = $wpdb->get_results( $wpdb->prepare( "SELECT GROUP_CONCAT(umeta_id ORDER BY umeta_id DESC) AS ids, user_id, COUNT(*) AS count FROM $wpdb->usermeta GROUP BY user_id, meta_key, meta_value HAVING count > %d", 1 ) );
                if( $query ) {
                    foreach ( $query as $meta ) {
                        $ids = array_map( 'intval', explode( ',', $meta->ids ) );
                        array_pop( $ids );
                        $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE umeta_id IN (" . implode( ',', $ids ) . ") AND user_id = %d", intval( $meta->user_id ) ) );
                    }

                    $message = sprintf( __( '%s Duplicated User Meta Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'duplicated_termmeta':
                $query = $wpdb->get_results( $wpdb->prepare( "SELECT GROUP_CONCAT(meta_id ORDER BY meta_id DESC) AS ids, term_id, COUNT(*) AS count FROM $wpdb->termmeta GROUP BY term_id, meta_key, meta_value HAVING count > %d", 1 ) );
                if( $query ) {
                    foreach ( $query as $meta ) {
                        $ids = array_map( 'intval', explode( ',', $meta->ids ) );
                        array_pop( $ids );
                        $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->termmeta WHERE meta_id IN (" . implode( ',', $ids ) . ") AND term_id = %d", intval( $meta->term_id ) ) );
                    }

                    $message = sprintf( __( '%s Duplicated Term Meta Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'optimize_database':
                $query = $wpdb->get_col( 'SHOW TABLES' );
                if( $query ) {
                    $tables = implode( ',', $query );
                    $wpdb->query( "OPTIMIZE TABLE $tables" );
                    $message = sprintf( __( '%s Tables Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
            case 'oembed_postmeta':
                $query = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_key FROM $wpdb->postmeta WHERE meta_key LIKE(%s)", '%_oembed_%' ) );
                if( $query ) {
                    foreach ( $query as $meta ) {
                        $post_id = intval( $meta->post_id );
                        if( $post_id === 0 ) {
                            $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s", $post_id, $meta->meta_key ) );
                        } else {
                            delete_post_meta( $post_id, $meta->meta_key );
                        }
                    }

                    $message = sprintf( __( '%s oEmbed Caches In Post Meta Processed', 'iremotewp' ), number_format_i18n( sizeof( $query ) ) );
                }
                break;
        }

        return apply_filters( 'iremotewp_sweep_sweep', $message, $name );
    }

    /**
     * Format number to percentage, taking care of division by 0.
     * Props @barisunver https://github.com/barisunver
     *
     *
     * @access public
     * @param int $current
     * @param int $total
     * @return string Number in percentage
     */
    public function format_percentage( $current, $total ) {
        return ( $total > 0 ? round( ( $current / $total ) * 100, 2 ) : 0 ) . '%';
    }

    /*
     * Get excluded term IDs
     *
     *
     * @access private
     * @return array Excluded term IDs
     */
    private function get_excluded_termids() {
        $default_term_ids = $this->get_default_taxonomy_termids();
        if( ! is_array( $default_term_ids ) ) {
            $default_term_ids = array();
        }
        $parent_term_ids = $this->get_parent_termids();
        if( ! is_array( $parent_term_ids ) ) {
            $parent_term_ids = array();
        }
        return array_merge( $default_term_ids, $parent_term_ids );
    }

    /*
     * Get all default taxonomy term IDs
     *
     *
     * @access private
     * @return array Default taxonomy term IDs
     */
    private function get_default_taxonomy_termids() {
        $taxonomies = get_taxonomies();
        $default_term_ids = array();
        if( $taxonomies ) {
            $tax = array_keys( $taxonomies );
            if( $tax ) {
                foreach( $tax as $t ) {
                    $term_id = intval( get_option( 'default_' . $t ) );
                    if( $term_id > 0 ) {
                        $default_term_ids[] = $term_id;
                    }
                }
            }
        }
        return $default_term_ids;
    }

    /*
     * Get terms that has a parent term
     *
     *
     * @access private
     * @return array Parent term IDs
     */
    private function get_parent_termids() {
        global $wpdb;
        return $wpdb->get_col( $wpdb->prepare( "SELECT tt.parent FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.parent > %d", 0 ) );
    }




}

