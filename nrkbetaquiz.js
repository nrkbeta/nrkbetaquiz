document.addEventListener('DOMContentLoaded', function(){
  var NRKBCQ = 'nrkbetaquiz';
  
  var parseQuiz = function(str){
    try{return JSON.parse(decodeURIComponent(str || ''))}
    catch(err){return []}
  };

  var removeQuiz = function(quizNode, formNode){
    quizNode.style.height = quizNode.offsetHeight + 'px';
    formNode.style.height = formNode.scrollHeight + 'px';
    quizNode.style.height = '0px';
    setTimeout(function(){
      quizNode.style.display = 'none';
      formNode.style.height = 'auto';
    }, 500);
  };

  var buildAnswer = function(text, name, value){
    var label = document.createElement('label');
    var input = label.appendChild(document.createElement('input'));
    var title = label.appendChild(document.createTextNode(text));

    input.type = 'radio';
    input.name = name;
    input.value = value;
    return label;
  };
  
  var buildQuiz = function(quizNode){
    var formNode = quizNode.nextElementSibling;
    var errorText = quizNode.getAttribute('data-' + NRKBCQ + '-error');
    var questions = parseQuiz(quizNode.getAttribute('data-' + NRKBCQ));
    var correctId = NRKBCQ + location.pathname + questions.map(function(q){return q.correct}).join('');
    var errorNode = document.createElement('h3').appendChild(document.createTextNode(errorText)).parentNode;
    var container = document.createElement('div');
    
    if(localStorage.getItem(correctId) === correctId){  //Skip quiz if already solved
      return quizNode.parentNode.removeChild(quizNode);
    }

    questions.forEach(function(question, index){
      container.appendChild(document.createElement('h2')).textContent = question.text;  //Render title
      question.answer.forEach(function(answer, value){
        answer && container.appendChild(buildAnswer(answer, NRKBCQ + index, value));
      });
    });

    quizNode.appendChild(container);
    quizNode.addEventListener('change', function(){
      var done = questions.every(function(question, index){
        var input = container.querySelector('input[name="' + NRKBCQ + index + '"]:checked');
        return input && Number(input.value) === Number(question.correct);
      });
      if(done){
        localStorage.setItem(correctId, correctId);
        removeQuiz(quizNode, formNode);
      }else{
        container.appendChild(errorNode);
      }
    });
  };
  
  [].forEach.call(document.querySelectorAll('.' + NRKBCQ), buildQuiz);
});
