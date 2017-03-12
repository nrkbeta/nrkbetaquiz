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

add_action( 'comment_form_before', 'nrkbetaquiz_form' );
/**
 * Prints the commenting quiz before WordPress's comment form.
 *
 * @TODO This functionality should be moved into the `nrkbetaquiz_form_top()`
 *       function, but I don't want to move it quite yet because I'm not yet
 *       touching any of the JavaScript, and a lot of the code still uses
 *       DOM hierarchy to function properly.
 */
function nrkbetaquiz_form() {
    global $post;
    if ( nrkbetaquiz_post_has_quiz( $post ) ) {
?>
  <div class="<?php esc_attr_e( NRKBCQ ); ?>"
    data-<?php esc_attr_e( NRKBCQ ); ?>="<?php echo esc_attr( rawurlencode( json_encode( get_post_meta( get_the_ID(), 'nrkbetaquiz' ) ) ) ); ?>"
    data-<?php esc_attr_e( NRKBCQ ); ?>-error="<?php esc_attr_e( 'You have not answered the quiz correctly. Try again.', 'nrkbetaquiz' ); ?>">
    <h2><?php esc_html_e( 'Would you like to comment? Please answer some quiz questions from the story.', 'nrkbetaquiz' );?></h2>
    <p><?php esc_html_e( "
      We care about our comments.
      That's why we want to make sure that everyone who comments have actually read the story.
      Answer a short quiz about the story to post your comment.
    ", 'nrkbetaquiz' ); ?></p>
  </div>
<?php
    }
}

add_action( 'comment_form_top', 'nrkbetaquiz_form_top' );
/**
 * Prints the commenting quiz at the top of the WordPress comment form.
 */
function nrkbetaquiz_form_top() {
    global $post;
    if ( nrkbetaquiz_post_has_quiz( $post ) ) {
        $quiz = get_post_meta( $post->ID, NRKBCQ );
        // TODO: Remove this CSS once JS-dependant quiz is not needed.
        echo '<style>#respond { height: auto; }</style>';
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

    $quiz = get_post_meta( $post_id, NRKBCQ );

    // Collect correct answers.
    $correct_answers = array();
    foreach ( $quiz as $i => $questions ) {
        $correct_answers[ NRKBCQ . $i ] = $questions[ 'correct' ];
    }
    $answers = array_intersect_key( $_POST, $correct_answers );
    if ( array_diff( $answers, $correct_answers ) ) {
        // The user did not answer all quiz question(s) correctly.
        $redirect = get_permalink( $post_id );
        $redirect .= '?' . rawurlencode( NRKBCQ . '_quiz_error' ) . '=1';
        $redirect .= '&' . rawurlencode( NRKBCQ . '_comment_content' ) . '=' . rawurlencode( $_POST[ 'comment' ] );
        wp_safe_redirect( $redirect . '#respond' );
        exit();
    } else {
        // The user answered every question correctly. Proceed. :)
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
  add_meta_box( NRKBCQ, 'CommentQuiz', 'nrkbetaquiz_edit', 'post', 'side', 'high' );
}

/**
 * Prints the quiz's Meta Box when editing a post.
 *
 * @param WP_Post $post
 */
function nrkbetaquiz_edit($post){
  $questions = array_pad(get_post_meta($post->ID, NRKBCQ), 1, array());
  $addmore = esc_html(__('Add question +', 'nrkbetaquiz'));
  $correct = esc_html(__('Correct', 'nrkbetaquiz'));
  $answer = esc_attr(__('Answer', 'nrkbetaquiz'));

  foreach($questions as $index => $question){
    $title = __('Question', 'nrkbetaquiz') . ' ' . ($index + 1);
    $text = esc_attr(empty($question['text'])? '' : $question['text']);
    $name = NRKBCQ . '[' . $index . ']';

    echo '<div style="margin-bottom:1em;padding-bottom:1em;border-bottom:1px solid #eee">';
    echo '<label><strong>' . esc_html($title) . ':</strong><br><input type="text" name="' . esc_attr($name) . '[text]" value="' . esc_attr($text) . '"></label>';
    for($i = 0; $i<3; $i++){
      $check = checked($i, isset($question['correct'])? intval($question['correct']) : 0, false);
      $value = isset($question['answer'][$i])? esc_attr($question['answer'][$i]) : '';

      echo '<br><input type="text" name="' . esc_attr($name) . '[answer][' . esc_attr($i) . ']" placeholder="' . esc_attr($answer) . '" value="' . esc_attr($value) . '">';
      echo '<label><input type="radio" name="' . esc_attr($name) . '[correct]" value="' . esc_attr($i) . '"' .$check . '> ' . esc_html($correct) . '</label>';
    }
    echo '</div>';
  }
  echo '<button class="button" type="button" data-' . esc_attr(NRKBCQ) . '>' . esc_html($addmore) . '</button>';

  ?><script>
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
  <?php wp_nonce_field(NRKBCQ, NRKBCQ_NONCE);
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
function nrkbetaquiz_save($post_id, $post, $update){
  if(isset($_POST[NRKBCQ], $_POST[NRKBCQ_NONCE]) && wp_verify_nonce($_POST[NRKBCQ_NONCE], NRKBCQ)){
    delete_post_meta($post_id, NRKBCQ);                         //Clean up previous quiz meta
    foreach($_POST[NRKBCQ] as $k=>$v){
      if($v['text'] && array_filter($v['answer'], 'strlen')){   //Only save filled in questions
        add_post_meta($post_id, NRKBCQ, $v);
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
