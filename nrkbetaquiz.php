<?php
/**
 * Know2Comment plugin for WordPress.
 *
 * WordPress plugin header information:
 *
 * * Plugin Name: NRKBeta Know2Comment
 * * Plugin URI: https://nrkbeta.no/
 * * Version: 1.0.0
 * * Description: Require the user to answer a quiz to be able to post comments.
 * * Author: Henrik Lied and Eirik Backer, Norwegian Broadcasting Corporation
 * * Text Domain: nrkbetaquiz
 * * Domain Path: /languages
 *
 * @link https://developer.wordpress.org/plugins/the-basics/header-requirements/
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html
 */

define( 'NRKBCQ', 'nrkbetaquiz' );
define( 'NRKBCQ_NONCE', NRKBCQ . '-nonce' );

add_action( 'wp_enqueue_scripts', function() {
  wp_enqueue_script( NRKBCQ, plugins_url( 'nrkbetaquiz.js', __FILE__ ) );
  wp_enqueue_style( NRKBCQ, plugins_url( 'nrkbetaquiz.css', __FILE__ ) );
});

add_action( 'comment_form_top', 'nrkbetaquiz_form' );
/**
 * Prints the comment quiz atop WordPress's comment form.
 *
 * This outputs the JavaScript-hooked element and initial greeting. A
 * different function, `nrkbetaquiz_form_no_js`, prints the HTML-only
 * version of the same quiz and is used when JavaScript is disabled.
 */
function nrkbetaquiz_form() {
    if ( nrkbetaquiz_post_has_quiz( get_post() ) ) {
?>
  <div class="<?php esc_attr_e( NRKBCQ ); ?>"
    data-<?php esc_attr_e( NRKBCQ ); ?>="<?php echo esc_attr( rawurlencode( json_encode( get_post_meta( get_the_ID(), 'nrkbetaquiz' ) ) ) ); ?>"
    data-<?php esc_attr_e( NRKBCQ ); ?>-error="<?php esc_attr_e( 'You have not answered the quiz correctly. Try again.', 'nrkbetaquiz' ); ?>"
    data-<?php esc_attr_e( NRKBCQ ); ?>-correct="<?php esc_attr_e( 'You answered the quiz correctly! You may now post your comment.', 'nrkbetaquiz' ); ?>"
  >
    <h2><?php esc_html_e( 'Would you like to comment? Please answer some quiz questions from the story.', 'nrkbetaquiz' );?></h2>
    <p><?php esc_html_e( "We care about our comments.
      That's why we want to make sure that everyone who comments have actually read the story.
      Answer a short quiz about the story to post your comment.
    ", 'nrkbetaquiz' ); ?></p>
  </div>
<?php
        nrkbetaquiz_form_no_js();
    }
}

/**
 * Prints the HTML-only quiz at the top of the WordPress comment form.
 */
function nrkbetaquiz_form_no_js() {
    if ( nrkbetaquiz_post_has_quiz( get_post() ) ) {
        $quiz = get_post_meta( get_the_ID(), NRKBCQ );
        $answer_hash = hash( 'sha256', serialize( $quiz ) );
?>
    <noscript>
        <?php if ( isset( $_GET[ NRKBCQ . '_quiz_error' ] ) ) { ?>
            <p class="error"><?php esc_html_e( 'You have not answered the quiz correctly. Try again.', 'nrkbetaquiz' ); ?></p>
        <?php
        }
        // Retain the user's entered comment even if they got the quiz wrong.
        if ( isset( $_GET[ NRKBCQ . '_comment_content' ] ) ) {
            add_filter( 'comment_form_field_comment', function ( $text ) {
                    $pos = strpos( $text, '</textarea>' );
                    return substr_replace(
                        $text,
                        esc_html( rawurldecode( stripslashes_deep( $_GET[ NRKBCQ . '_comment_content' ] ) ) ) . '</textarea>',
                        $pos
                    );
                }
            );
        }

        if ( ! isset( $_COOKIE[ NRKBCQ . '_comment_quiz_' . COOKIEHASH ] ) || $answer_hash !== $_COOKIE[ NRKBCQ . '_comment_quiz_' . COOKIEHASH ] ) {
            foreach ( $quiz as $i => $question ) { ?>
            <div class="<?php esc_attr_e( NRKBCQ . '-quiz-question-' . $i ); ?>">
                <h2><?php esc_html_e( $question['text'] ); ?></h2>
                <ul>
                <?php
                // Randomize the order in which answers are shown.
                $answers = array();
                foreach ( $question[ 'answer' ] as $k => $v ) {
                    $answers[] = array( 'value' => $k, 'text' => $v );
                }
                shuffle($answers);
                foreach ( $answers as $j => $answer ) {
                ?>
                    <li class="<?php esc_attr_e( NRKBCQ ); ?>-quiz-answer-<?php esc_attr_e( $j ); ?>">
                        <label>
                            <input type="radio"
                                name="<?php esc_attr_e( NRKBCQ . $i ); ?>"
                                value="<?php esc_attr_e( $answer[ 'value' ] ); ?>"
                            />
                            <?php esc_html_e( $answer[ 'text' ] ); ?>
                        </label>
                <?php } ?>
            </ul>
        </div>
<?php
            }
        }
?>
    </noscript>
<?php
    }
}

add_action( 'pre_comment_on_post', 'nrkbetaquiz_pre_comment_on_post' );
/**
 * Tests a user's answers to the comment quiz before allowing their
 * comment to be added.
 *
 * @param int $post_id
 *
 * @link https://developer.wordpress.org/reference/hooks/pre_comment_on_post/
 */
function nrkbetaquiz_pre_comment_on_post( $post_id ) {
    if ( ! nrkbetaquiz_post_has_quiz( get_post($post_id) ) ) {
        return; // Don't do anything on a post without a quiz.
    }

    // Collect correct answers.
    $quiz = get_post_meta( $post_id, NRKBCQ );
    $correct_answers = array();
    foreach ( $quiz as $i => $questions ) {
        $correct_answers[ NRKBCQ . $i ] = $questions[ 'correct' ];
    }
    $answer_hash = hash( 'sha256', serialize( $quiz ) );

    if ( isset( $_COOKIE[ NRKBCQ . '_comment_quiz_' . COOKIEHASH ] ) && $answer_hash === $_COOKIE[ NRKBCQ . '_comment_quiz_' . COOKIEHASH ] ) {
        return; // Don't verify quiz answers if we've already answered them.
    }

    $answers = array_intersect_key( $_POST, $correct_answers );
    $permalink = get_permalink( $post_id );
    if ( ( count( $answers ) !== count( $correct_answers ) ) || array_diff( $answers, $correct_answers ) ) {
        // The user did not answer all quiz question(s) correctly.
        $redirect = $permalink;
        $redirect .= '?' . rawurlencode( NRKBCQ . '_quiz_error' ) . '=1';
        $redirect .= '&' . rawurlencode( NRKBCQ . '_comment_content' ) . '=' . rawurlencode( $_POST[ 'comment' ] );
        wp_safe_redirect( $redirect . '#respond' );
        exit();
    } else {
        // The user answered every question correctly. Proceed. :)
        $secure = ( 'https' === parse_url( home_url(), PHP_URL_SCHEME ) );
        $path = parse_url( $permalink, PHP_URL_PATH );
        $comment_cookie_lifetime = apply_filters( 'comment_cookie_lifetime', 3 * HOUR_IN_SECONDS );
        setcookie( NRKBCQ . '_comment_quiz_' . COOKIEHASH, $answer_hash, time() + $comment_cookie_lifetime, $path, COOKIE_DOMAIN, $secure, true );
        return;
    }
}

add_action( 'add_meta_boxes', 'nrkbetaquiz_add' );
/**
 * Registers the quiz's meta box.
 *
 * @uses nrkbetaquiz_edit
 *
 * @link https://developer.wordpress.org/reference/hooks/add_meta_boxes/
 */
function nrkbetaquiz_add() {
  add_meta_box( NRKBCQ, __('Comment Quiz', 'nrkbetaquiz'), 'nrkbetaquiz_edit', 'post', 'side', 'high' );
}

/**
 * Prints the quiz's Meta Box when editing a post.
 *
 * @param WP_Post $post
 *
 * @uses print_quiz_question_edit_html
 */
function nrkbetaquiz_edit( $post ) {
    nrkbetaquiz_print_quiz_question_edit_html( $post );
    $addmore = esc_html( __( 'Add question +', 'nrkbetaquiz' ) );
    echo '<button class="button hide-if-no-js" type="button" data-' . esc_attr( NRKBCQ ) . '>' . esc_html( $addmore ) . '</button>';
?><script>
    // Add another question to the quiz editing form.
    document.addEventListener('click', function(event){
      if(event.target.hasAttribute('data-<?php echo esc_js(esc_attr(NRKBCQ)); ?>')){
        var button = event.target;
        var index = [].indexOf.call(button.parentNode.children, button);
        var clone = button.previousElementSibling.cloneNode(true);
        var title = clone.querySelector('strong');

        title.textContent = title.textContent.replace(/\d+/, index + 1);
        [].forEach.call(clone.querySelectorAll('input'), function(input){
          input.name = input.name.replace(/\d+/, index);  //Update index
          if(input.type === 'text')input.value = '';      //Reset value
        });
        button.parentNode.insertBefore(clone, button);    //Insert in DOM
      }
    });
  </script>
  <?php wp_nonce_field( NRKBCQ, NRKBCQ_NONCE );
}

/**
 * Prints the HTML for a quiz question in the edit form.
 *
 * @param WP_Post $post
 */
function nrkbetaquiz_print_quiz_question_edit_html ( $post ) {
    $quiz = get_post_meta( $post->ID, NRKBCQ );
    $questions = array_pad( $quiz, count($quiz) + 1, array() );

    $answer = esc_attr( __( 'Answer', 'nrkbetaquiz' ) );
    $ask = esc_attr( __( 'Question', 'nrkbetaquiz' ) );
    $correct = esc_html( __( 'Correct', 'nrkbetaquiz' ) );

    foreach( $questions as $index => $question ) {
        $title = __( 'Question', 'nrkbetaquiz' ) . ' ' . ( $index + 1 );
        $text = esc_attr( empty( $question[ 'text' ] ) ? '' : $question[ 'text' ] );
        $name = NRKBCQ . '[' . $index . ']';

        echo '<div style="margin-bottom:1em;padding-bottom:1em;border-bottom:1px solid #eee">';
        echo '<p><label><strong style="display:block;">' . esc_html( $title ) . ':</strong><input type="text" name="' . esc_attr( $name ) . '[text]" placeholder="' . esc_attr( $ask ) . '" value="' . esc_attr( $text ) . '"></label></p>';
        echo '<ul>';
        for( $i = 0; $i < 3; $i++ ) {
            $check = checked( $i, isset( $question[ 'correct' ] ) ? intval( $question[ 'correct' ] ) : 0, false );
            $value = isset( $question[ 'answer' ][ $i ] ) ? esc_attr( $question[ 'answer' ][ $i ] ) : '';

            echo '<li>';
            echo '<input type="text" name="' . esc_attr( $name ) . '[answer][' . esc_attr( $i ) . ']" placeholder="' . esc_attr( $answer ) . '" value="' . esc_attr( $value ) . '">';
            echo '<label><input type="radio" name="' . esc_attr( $name ) . '[correct]" value="' . esc_attr( $i ) . '"' .$check . '> ' . esc_html( $correct ) . '</label>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}

add_action('save_post', 'nrkbetaquiz_save', 10, 3);
/**
 * Saves a post's commenting quiz to the database on post save.
 *
 * @param int $post_id
 * @param WP_Post $post
 * @param bool $update
 *
 * @link https://developer.wordpress.org/reference/hooks/save_post/
 */
function nrkbetaquiz_save( $post_id, $post, $update ) {
    if( isset( $_POST[NRKBCQ], $_POST[NRKBCQ_NONCE] ) && wp_verify_nonce( $_POST[NRKBCQ_NONCE], NRKBCQ ) ) {
        // Clean up previous quiz meta
        delete_post_meta( $post_id, NRKBCQ );
        foreach( $_POST[NRKBCQ] as $k => $v ) {
            // Only save filled in questions
            if( $v['text'] && array_filter( $v['answer'], 'strlen' ) ) {
                add_post_meta( $post_id, NRKBCQ, $v );
            }
        }
    }
}

/**
 * Determines whether or not a given post has an associated quiz.
 *
 * @param WP_Post $post
 *
 * @return bool
 */
function nrkbetaquiz_post_has_quiz( $post ) {
    return ( empty( get_post_meta( $post->ID, NRKBCQ, true ) ) ) ? false : true;
}
