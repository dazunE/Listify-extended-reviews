<?php
/**
 * Listify extended child theme with extended reviews system
 *
 */

define( 'EXTENDED_REVIEW_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

require_once EXTENDED_REVIEW_PLUGIN_PATH . '/include/class-extended-comments-review.php';

function listify_child_styles() {

	wp_enqueue_style( 'listify-child', get_stylesheet_uri() );
	// Javascript.
	wp_enqueue_script( 'listify-extended-reviews-js', get_stylesheet_directory_uri() . '/js/listify-extended-reviews.js', array( 'jquery' ) );

}

add_action( 'wp_enqueue_scripts', 'listify_child_styles', 999 );


function helpful_data() {

	$comment_id = explode( '_', $_POST['commentId'] )[1];

	$user_id = get_current_user_id();

	$comment_helpful_users = get_comment_meta( $comment_id, 'review_helpful', true );

	if ( ! in_array( $user_id, $comment_helpful_users ) ) {


		if ( null == $comment_helpful_users ) {

			$comment_helpful_users = array( $user_id );

		} else {

			array_push( $comment_helpful_users, $user_id );

		}

		$helpful_user_count = sizeof( $comment_helpful_users );


		update_comment_meta( $comment_id, 'review_helpful', $comment_helpful_users );
		update_comment_meta( $comment_id, 'helpful_count', $helpful_user_count );

		echo json_encode( array( 'data' => $helpful_user_count, 'status' => true ) );

	} else {

		echo json_encode( array( 'data' => 'You have already marked this review as helpful', 'status' => false ) );

	}

	wp_die();

}

add_action( 'wp_ajax_helpful_data', 'helpful_data' );
add_action( 'wp_ajax_nopriv_helpful_data', 'helpful_data' );

function add_helpful_button( $content, $comment, $args ) {


	$comment_post_author = get_post( $comment->comment_post_ID )->post_author;
	$comment_author_id   = $comment->user_id;

	if ( 0 !== intval( $comment->comment_parent ) || ! is_user_logged_in() || absint( $comment_author_id ) === absint( get_current_user_id() ) || absint( $comment_post_author ) === absint( get_current_user_id() ) || is_admin() ) {

		return $content;

	}

	if ( null == get_comment_meta( $comment->comment_ID, 'review_helpful', true ) || '' === get_comment_meta( $comment->comment_ID, 'review_helpful', true ) ) {

		add_comment_meta( $comment->comment_ID, 'review_helpful', null, true );

	}

	if ( null == get_comment_meta( $comment->comment_ID, 'helpful_count', true ) || '' === get_comment_meta( $comment->comment_ID, 'helpful_count', true ) ) {

		add_comment_meta( $comment->comment_ID, 'helpful_count', 0, true );

	}


	$comment_id = $comment->comment_ID;

	return $content . get_helpful_button( $comment_id );
}

add_filter( 'get_comment_text', 'add_helpful_button', 12, 3 );

function get_helpful_button( $comment_id ) {

	$comment_helpful_users = get_comment_meta( $comment_id, 'review_helpful', true );

	ob_start();
	?>
    <div class='wpjmr-list-reviews'>
        <button class="helpful-button" id="<?php echo 'helpful_' . $comment_id; ?>">Helpful (
            <span><?php echo sizeof( $comment_helpful_users ) ?></span> )
        </button>
    </div>
	<?php
	return ob_get_clean();
}

function extended_reviews_comments( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;

	$post = get_post();


	?>

<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">

    <article id="comment-<?php comment_ID(); ?>" class="comment row">
        <header class="comment-author vcard col-md-2 col-sm-3 col-xs-12">
			<?php echo get_avatar( $comment, 100 ); ?>
        </header><!-- .comment-meta -->

        <section class="comment-content comment col-md-10 col-sm-9 col-xs-12">

            <cite>
                <b class="fn"><?php echo esc_html( get_comment_author() ); ?></b>

				<?php if ( is_singular() && ( $comment->user_id === $post->post_author && $post->post_author > 0 ) ) : ?>
                    <span class="listing-owner"><?php esc_html_e( 'Listing Owner', 'listify' ); ?></span>
				<?php endif; ?>
            </cite>

            <div class="comment-meta">

				<?php do_action( 'listify_comment_meta_before', $comment ); ?>

				<?php edit_comment_link( __( '<span class="ion-edit"></span>', 'listify' ) ); ?>

				<?php do_action( 'listify_comment_meta_after', $comment ); ?>


            </div>

			<?php if ( '0' === $comment->comment_approved ) : ?>
                <p class="comment-awaiting-moderation"><?php esc_html_e( 'Your comment is awaiting moderation.', 'listify' ); ?></p>
			<?php endif; ?>

			<?php do_action( 'listify_comment_before', $comment ); ?>

			<?php comment_text(); ?>

            <div>
				<?php

				if ( get_current_user_id() === (int) $post->post_author && get_current_user_id() != $comment->user_id ) {

					$arguments         = array(
						'parent'    => $comment->comment_ID,
						'post_type' => 'job_listing'
					);
					$childern_comments = get_comments( $arguments );

					if ( null == $childern_comments ) {
						comment_reply_link(
							wp_parse_args(
								array(
									'reply_text' => '<i class="ion-reply"> Reply </i>',
									'before'     => '',
									'after'      => '',
									'depth'      => $depth,
									'max_depth'  => $args['max_depth'],
								), $args
							)
						);
					}


				}


				?>
            </div>

			<?php do_action( 'listify_comment_after', $comment ); ?>


			<?php
			printf(
				'<a href="%1$s" class="comment-ago"><time datetime="%2$s">%3$s</time></a>',
				esc_url( get_comment_link( $comment->comment_ID ) ),
				get_comment_time( 'c' ),
				// Translators: %s Time difference.
				esc_attr( sprintf( __( '%s ago', 'listify' ), human_time_diff( get_comment_time( 'U' ), current_time( 'timestamp' ) ) ) )
			);
			?>


        </section><!-- .comment-content -->

    </article><!-- #comment-## -->

	<?php
}