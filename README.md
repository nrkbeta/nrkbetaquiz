# NRKbeta Know2Comment

Require the user to pass a quiz about the story before being able to comment.

This plugin disables the comment form until a user has passed a quiz about the story he's about to comment on.

The plugin is made for Wordpress, but the JavaScript component can easily be implemented into other CMS systems as well.

## Usage

1. Install the plugin
2. Fill out the quiz questions and answers in the post admin interface
3. Start using it!

## Usage (outside of Wordpress)

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
