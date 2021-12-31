function runmewhenfinished(form) {
  showMessage("Hello.. I am the callback from Form with id: '"+form.attr('id')+'\'. Oww and I am sticky, I will not leave untill you click me!!', 'info',false,true);
  showMessage("Not me... I will leave soon..", 'success',false,true);
}
