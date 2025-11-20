document.addEventListener('DOMContentLoaded', function() {
   document.querySelectorAll('[radio-custom], [radio-default]').forEach(function(radioButton) {
      radioButton.addEventListener('click', function() {
         toggle(radioButton);
      });
   });

   // Reset category settings button
   const resetButton = document.getElementById('reset-category-settings');
   if (resetButton) {
      resetButton.addEventListener('click', function(e) {
         e.preventDefault();

         // Confirm with user before resetting
         if (!confirm('Are you sure you want to reset all category settings to default? This action cannot be undone.')) {
            return;
         }

         // Disable button during request
         resetButton.disabled = true;
         resetButton.textContent = 'Resetting...';

         // Make AJAX request
         const formData = new FormData();
         formData.append('action', 'reset_category_settings');
         formData.append('nonce', categoryIndexerAjax.nonce);

         fetch(categoryIndexerAjax.ajax_url, {
            method: 'POST',
            body: formData
         })
         .then(response => response.json())
         .then(data => {
            if (data.success) {
               alert(data.data.message);
               // Reload the page to show updated settings
               window.location.reload();
            } else {
               alert(data.data.message || 'An error occurred.');
               resetButton.disabled = false;
               resetButton.textContent = 'Reset All Categories to Default';
            }
         })
         .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while resetting settings.');
            resetButton.disabled = false;
            resetButton.textContent = 'Reset All Categories to Default';
         });
      });
   }
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
