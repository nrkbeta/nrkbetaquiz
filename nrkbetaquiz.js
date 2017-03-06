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
      question.answer
        .map(function(key, val){return key && buildAnswer(key, NRKBCQ + index, val)})
        .sort(function(){return 0.5 - Math.random()})
        .forEach(function(node){node && container.appendChild(node)});
    });

    quizNode.appendChild(container);
    quizNode.addEventListener('change', function(){
      var checked = questions.map(function(q,i){return container.querySelector('input[name="' + NRKBCQ + i + '"]:checked')});
      var correct = questions.every(function(q,i){return checked[i] && Number(checked[i].value) === Number(q.correct)});
      var failure = !correct && checked.filter(Boolean).length === questions.length;
      
      if(correct){
        localStorage.setItem(correctId, correctId);
        removeQuiz(quizNode, formNode);
      }else if(failure){
        container.appendChild(errorNode);
      }
    });
  };
  
  [].forEach.call(document.querySelectorAll('.' + NRKBCQ), buildQuiz);
});