document.addEventListener('DOMContentLoaded', function() {
   document.querySelectorAll('[radio-custom], [radio-default]').forEach(function(radioButton) {
      radioButton.addEventListener('click', function() {
         toggle(radioButton);
      });
   });
});

function toggle(radioButton) {
   let buttonNumber;
   let select;

   if (radioButton.hasAttribute('radio-custom')) {
        buttonNumber = radioButton.getAttribute('radio-custom');
        select = document.querySelector('[select-category="' + buttonNumber + '"]');
        if (select) select.removeAttribute('disabled');
   } else if (radioButton.hasAttribute('radio-default')) {
        buttonNumber = radioButton.getAttribute('radio-default');
        select = document.querySelector('[select-category="' + buttonNumber + '"]');
        if (select) select.setAttribute('disabled', '');
   }
}
