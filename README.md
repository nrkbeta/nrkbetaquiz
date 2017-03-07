# NRKbeta Know2Comment

Require the user to pass a quiz about the story before being able to comment.

This plugin disables the comment form until a user has passed a quiz about the story he's about to comment on.

The plugin is made for Wordpress, but the JavaScript component can easily be implemented into other CMS systems as well.

## Usage

1. [Download](https://github.com/nrkbeta/nrkbetaquiz/archive/master.zip) the plugin
1. Rename the downloaded folder from `nrkbetaquiz-master` to `nrkbetaquiz`
1. Upload it to your plugins-folder
1. Enable the plugin in your wordpress backend
1. Fill out the quiz questions and answers in the post admin interface
1. Start using it!

## Usage (outside of Wordpress)

If you're using a different content management system, you can still use the JavaScript component to enable this functionality on your site.

The component requires a `DIV` right before the container which holds your comment form. The `DIV` has two data-attributes: `data-nrkbetaquiz` and `data-nrkbetaquiz-error`. The `DIV` also needs the class `nrkbetaquiz`.

`data-nrkbetaquiz-error` is a string with the error message in case the user has answered the quiz wrongfully.
`data-nrkbetaquiz` is an array with the following structure:

	[{
	    text: 'Who is the current president of The Unites States?'
	    answers: ['Barack Obama', 'Donald Trump', 'Steve Bannon'],
	    correct: 2
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
	      correct: 2
	    }, {
	      text: 'What is the radius of Earth?'
	      answers: ['6 371 kilometers', '371 kilometers', '200 kilometers'],
	      correct: 0
	  }]"
	  data-nrkbetaquiz-error="You fail">
	</div>

	<div id="YOUR_COMMENT_FORM_CONTAINER"></div>
