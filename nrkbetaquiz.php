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

define('NRKBCQ', 'nrkbetaquiz');
define('NRKBCQ_NONCE', NRKBCQ . '-nonce');

add_action('wp_enqueue_scripts', function(){
  wp_enqueue_script(NRKBCQ, plugins_url('nrkbetaquiz.js', __FILE__));
  wp_enqueue_style(NRKBCQ, plugins_url('nrkbetaquiz.css', __FILE__));
});

add_action('comment_form_before', 'nrkbetaquiz_form');
function nrkbetaquiz_form(){ ?>
  <div class="<?php esc_attr_e(NRKBCQ); ?>"
    data-<?php esc_attr_e(NRKBCQ); ?>="<?php echo esc_attr(urlencode(json_encode(get_post_meta(get_the_ID(), 'nrkbetaquiz')))); ?>"
    data-<?php esc_attr_e(NRKBCQ); ?>-error="<?php esc_attr_e('You have not answered the quiz correctly. Try again.', 'nrkbetaquiz'); ?>">
    <h2><?php esc_html_e('Would you like to comment? Please answer some quiz questions from the story.', 'nrkbetaquiz');?></h2>
    <p><?php esc_html_e("
      We care about our comments.
      That's why we want to make sure that everyone who comments have actually read the story.
      Answer a couple of questions from the story to unlock the comment form.
    ", 'nrkbetaquiz');?></p>
    <noscript><?php _e(sprintf(esc_html('Please %1$senable javascript%2$s to comment'), '<a href="http://enable-javascript.com/" target="_blank" style="text-decoration:underline">', '</a>'), 'nrkbetaquiz');?></noscript>
  </div>
<?php }

add_action('add_meta_boxes', 'nrkbetaquiz_add');
function nrkbetaquiz_add(){
  add_meta_box(NRKBCQ, 'CommentQuiz', 'nrkbetaquiz_edit', 'post', 'side', 'high');
}

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
