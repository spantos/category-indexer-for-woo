document.addEventListener('DOMContentLoaded', function() {
   document.querySelectorAll('[radio-custom], [radio-default]').forEach(function(radioButton) {
      radioButton.addEventListener('click', function() {
         toggle(radioButton);
      });
   });

   // Handle custom settings checkboxes
   const customSettingsCheckboxes = document.querySelectorAll('.ci-use-custom-settings-checkbox');

   if (customSettingsCheckboxes.length > 0) {
      customSettingsCheckboxes.forEach(function(checkbox) {
         // Set initial state on page load
         toggleCategoryRadios(checkbox);

         // Add event listener for changes
         checkbox.addEventListener('change', function() {
            toggleCategoryRadios(checkbox);
         });
      });
   }

   /**
    * Toggles radio buttons for a category based on custom settings checkbox state
    */
   function toggleCategoryRadios(checkbox) {
      const categoryId = checkbox.getAttribute('data-category-id');
      const isChecked = checkbox.checked;

      // Find all radio buttons for this category
      const categoryRadios = document.querySelectorAll('input[type="radio"][data-category-id="' + categoryId + '"]');

      if (categoryRadios.length > 0) {
         categoryRadios.forEach(function(radio) {
            if (isChecked) {
               // Enable radio buttons when checkbox is checked
               radio.disabled = false;
               radio.removeAttribute('disabled');
               if (radio.parentElement) {
                  radio.parentElement.classList.remove('disabled');
               }
            } else {
               // Disable radio buttons when checkbox is unchecked
               radio.disabled = true;
               radio.setAttribute('disabled', 'disabled');
               if (radio.parentElement) {
                  radio.parentElement.classList.add('disabled');
               }
            }
         });
      }
   }

   // Reset category settings button
   const resetButton = document.getElementById('reset-category-settings');
   if (resetButton) {
      resetButton.addEventListener('click', function(e) {
         e.preventDefault();

         // Confirm with user before resetting
         if (!confirm(categoryIndexerAjax.i18n.confirmReset)) {
            return;
         }

         // Disable button during request
         resetButton.disabled = true;
         resetButton.textContent = categoryIndexerAjax.i18n.resetting;

         // Make AJAX request
         const formData = new FormData();
         formData.append('action', 'reset_category_settings');
         formData.append('nonce', categoryIndexerAjax.reset_nonce);

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
               alert(data.data.message || categoryIndexerAjax.i18n.errorOccurred);
               resetButton.disabled = false;
               resetButton.textContent = categoryIndexerAjax.i18n.resetButton;
            }
         })
         .catch(error => {
            console.error('Error:', error);
            alert(categoryIndexerAjax.i18n.errorResetting);
            resetButton.disabled = false;
            resetButton.textContent = categoryIndexerAjax.i18n.resetButton;
         });
      });
   }

   // Clear category cache button
   const clearCacheButton = document.getElementById('clear-category-cache');
   if (clearCacheButton) {
      clearCacheButton.addEventListener('click', function(e) {
         e.preventDefault();

         // Confirm with user before clearing cache
         if (!confirm(categoryIndexerAjax.i18n.confirmClearCache)) {
            return;
         }

         // Disable button during request
         clearCacheButton.disabled = true;
         clearCacheButton.textContent = categoryIndexerAjax.i18n.clearing;

         // Make AJAX request
         const formData = new FormData();
         formData.append('action', 'clear_category_cache');
         formData.append('nonce', categoryIndexerAjax.clear_cache_nonce);

         fetch(categoryIndexerAjax.ajax_url, {
            method: 'POST',
            body: formData
         })
         .then(response => response.json())
         .then(data => {
            if (data.success) {
               // Reload with success flag to show notice and rebuild cache
               const url = new URL(window.location.href);
               url.searchParams.set('cache_cleared', '1');
               url.searchParams.set('cache_cleared_nonce', categoryIndexerAjax.clear_cache_nonce);
               window.location.href = url.toString();
            } else {
               alert(data.data.message || categoryIndexerAjax.i18n.errorOccurred);
               clearCacheButton.disabled = false;
               clearCacheButton.textContent = categoryIndexerAjax.i18n.clearCacheButton;
            }
         })
         .catch(error => {
            console.error('Error:', error);
            alert(categoryIndexerAjax.i18n.errorClearing);
            clearCacheButton.disabled = false;
            clearCacheButton.textContent = categoryIndexerAjax.i18n.clearCacheButton;
         });
      });
   }

   // Categories per page selector
   const perPageSelector = document.getElementById('categories-per-page');
   if (perPageSelector) {
      perPageSelector.addEventListener('change', function(e) {
         const perPage = e.target.value;

         // Disable selector during request
         perPageSelector.disabled = true;

         // Make AJAX request
         const formData = new FormData();
         formData.append('action', 'update_categories_per_page');
         formData.append('nonce', categoryIndexerAjax.nonce);
         formData.append('per_page', perPage);

         fetch(categoryIndexerAjax.ajax_url, {
            method: 'POST',
            body: formData
         })
         .then(response => response.json())
         .then(data => {
            if (data.success) {
               // Reload the page and reset to page 1 to show updated pagination
               const url = new URL(window.location.href);
               url.searchParams.delete('paged');
               window.location.href = url.toString();
            } else {
               alert(data.data.message || categoryIndexerAjax.i18n.errorOccurred);
               perPageSelector.disabled = false;
            }
         })
         .catch(error => {
            console.error('Error:', error);
            alert(categoryIndexerAjax.i18n.errorUpdating);
            perPageSelector.disabled = false;
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
