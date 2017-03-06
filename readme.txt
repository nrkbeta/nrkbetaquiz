=== Plugin Name ===
Contributors: henriklied, eirikbacker
Tags: comments, quiz, commentquiz, nrkbeta
Requires at least: 4.6
Tested up to: 4.7
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Require the user to pass a quiz about the story before being able to comment.

== Description ==

This plugin disables the comment form until a user has passed a quiz about the story he's about to comment on.

The plugin is made for Wordpress, but the JavaScript component can easily be implemented into other CMS systems as well.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Fill out the quiz questions and answers in the post admin interface
1. Start using it!


== Frequently Asked Questions ==

If you're using a different content management system, you can still use the JavaScript component to enable this functionality on your site.

The component requires a DIV right before the container which holds your comment form. The DIV has two data-attributes: data-nrkbetaquiz and data-nrkbetaquiz-error. 

data-nrkbetaquiz-error is a string with the error message in case the user has answered the quiz wrongfully.
data-nrkbetaquiz is an array with the following structure:

	[{
	    text: 'Who is the current president of The Unites States?'
	    answers: ['Barack Obama', 'Donald Trump', 'Steve Bannon'],
	    correct: 1
	  }, {
	    text: 'What is the radius of Earth?'
	    answers: ['6 371 kilometers', '371 kilometers', '200 kilometers'],
	    correct: 0
	}]


Here's a full example of the implementation:

	<script src="nrkbetaquiz.js"></script>
	<div class="nrkbetaquiz"
	  data-nrkbetaquiz="[{
	      text: 'Who is the current president of The Unites States?'
	      answers: ['Barack Obama', 'Donald Trump', 'Steve Bannon'],
	      correct: 1
	    }, {
	      text: 'What is the radius of Earth?'
	      answers: ['6 371 kilometers', '371 kilometers', '200 kilometers'],
	      correct: 0
	  }]"
	  data-nrkbetaquiz-error="You fail">
	</div>

	<div id="YOUR_COMMENT_FORM_CONTAINER"></div>