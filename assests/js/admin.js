function toggle (radioButton) {
   if (radioButton.hasAttribute('radio-custom')) {
        buttonNumber = radioButton.getAttribute('radio-custom');
        select = document.querySelector('[select-category="' + buttonNumber + '"]');
        select.removeAttribute('disabled');
     } else if (radioButton.hasAttribute('radio-default')) {
        buttonNumber = radioButton.getAttribute('radio-default');
        select = document.querySelector('[select-category="' + buttonNumber + '"]');
        select.setAttribute('disabled', '');
    }
}