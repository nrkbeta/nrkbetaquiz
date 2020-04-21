<?php
/*
Plugin Name: NRKBeta Know2Comment
Version: 1.0.0
Plugin URI: https://nrkbeta.no/
Author: Henrik Lied and Eirik Backer, Norwegian Broadcasting Corporation
Description: Require the user to answer a quiz to be able to post comments.
*/

define('NRKBCQ', 'nrkbetaquiz');
define('NRKBCQ_NONCE', NRKBCQ . '-nonce');

add_action('wp_enqueue_scripts', function(){
  wp_enqueue_script(NRKBCQ, plugins_url('nrkbetaquiz.js', __FILE__));
  wp_enqueue_style(NRKBCQ, plugins_url('nrkbetaquiz.css', __FILE__));
});

add_action('comment_form_before', 'nrkbetaquiz_form');
function nrkbetaquiz_form(){ ?>
  <div class="<?php echo NRKBCQ; ?>"
    data-<?php echo NRKBCQ; ?>="<?php echo esc_attr(rawurlencode(json_encode(get_post_meta(get_the_ID(), NRKBCQ)))); ?>"
    data-<?php echo NRKBCQ; ?>-error="<?php echo esc_attr(__('You have not answered the quiz correctly. Try again.', NRKBCQ)); ?>">
    <h2>Would you like to comment? Please answer some quiz questions from the story.</h2>
    <p>
      We care about our comments.
      That's why we want to make sure that everyone who comments have actually read the story.
      Answer a couple of questions from the story to unlock the comment form.
    </p>
    <noscript>Please <a href="http://enable-javascript.com/" target="_blank" style="text-decoration:underline">enable javascript</a> to comment</noscript>
  </div>
<?php }

add_action('add_meta_boxes', 'nrkbetaquiz_add');
function nrkbetaquiz_add(){
  add_meta_box(NRKBCQ, 'CommentQuiz', 'nrkbetaquiz_edit', 'post', 'side', 'high');
}

function nrkbetaquiz_edit($post){
  $questions = array_pad(get_post_meta($post->ID, NRKBCQ), 1, array());
  $addmore = esc_html(__('Add question +', NRKBCQ));
  $correct = esc_html(__('Correct', NRKBCQ));
  $answer = esc_attr(__('Answer', NRKBCQ));

  foreach($questions as $index => $question){
    $title = __('Question', NRKBCQ) . ' ' . ($index + 1);
    $text = esc_attr(empty($question['text'])? '' : $question['text']);
    $name = NRKBCQ . '[' . $index . ']';

    echo '<div style="margin-bottom:1em;padding-bottom:1em;border-bottom:1px solid #eee">';
    echo '<label><strong>' . $title . ':</strong><br><input type="text" name="' . $name . '[text]" value="' . $text . '"></label>';
    for($i = 0; $i<3; $i++){
      $check = checked($i, isset($question['correct'])? intval($question['correct']) : 0, false);
      $value = isset($question['answer'][$i])? esc_attr($question['answer'][$i]) : '';

      echo '<br><input type="text" name="' . $name . '[answer][' . $i . ']" placeholder="' . $answer . '" value="' . $value . '">';
      echo '<label><input type="radio" name="' . $name . '[correct]" value="' . $i . '"' . $check . '> ' . $correct . '</label>';
    }
    echo '</div>';
  }
  echo '<button class="button" type="button" data-' . NRKBCQ . '>' . $addmore . '</button>';

  ?><script>
    document.addEventListener('click', function(event){
      if(event.target.hasAttribute('data-<?php echo NRKBCQ; ?>')){
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

        // Sanitizing data input
        foreach ( $v as $key => $value ) {
          $key = sanitize_text_field( $key ); // Best to remove any tags, line breaks, etc

          $cleaned_answers = array();
          foreach( $v['answer'] as $answer_value ) {
              if( $answer_value )
                $cleaned_answers[] = sanitize_text_field( $answer_value ); // And best to remove any tags, line breaks, etc, from answers too
          }
          $v['answer'] = $cleaned_answers;

          $v[$key] = $value;
        }

        add_post_meta($post_id, NRKBCQ, $v);
      }
    }
  }
}
