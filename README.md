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

The component requires a `DIV` to be inserted as a direct child of your comment form. The `DIV` has three `data-`attributes: `data-nrkbetaquiz`, `data-nrkbetaquiz-error`, and `data-nrkbetaquiz-correct`. The `DIV` also needs the class `nrkbetaquiz`.

* `data-nrkbetaquiz-error` is a string with the error message in case the user has answered the quiz wrongfully.
* `data-nrkbetaquiz-correct` is a string with the success message when the user answers the quiz correctly.
* `data-nrkbetaquiz` is a [JSON](http://json.org/) array with the following structure:  

  ```json
  [{
      "text": "Who is the current president of The Unites States?",
      "answers": ["Barack Obama", "Donald Trump", "Steve Bannon"],
      "correct": 1
    }, {
      "text": "What is the radius of Earth?",
      "answers": ["6,371 kilometers", "371 kilometers", "200 kilometers"],
      "correct": 0
  }]
  ```

Here's a full example of the implementation:

```html
<form id="YOUR_COMMENT_FORM_CONTAINER">
    <script src="nrkbetaquiz.js"></script>
    <div class="nrkbetaquiz"
        data-nrkbetaquiz='[{
                "text": "Who is the current president of The Unites States?",
                "answers": ["Barack Obama", "Donald Trump", "Steve Bannon"],
                "correct": 1
            }, {
                "text": "What is the radius of Earth?",
                "answers": ["6,371 kilometers", "371 kilometers", "200 kilometers"],
                "correct": 0
            }]'
        data-nrkbetaquiz-error="You fail"
        data-nrkbetaquiz-correct="You succeed"
    ></div>
</form>
```
