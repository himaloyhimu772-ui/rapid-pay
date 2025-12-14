/**
 * Rapid Pay Checkout Scripts
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		// Format phone number input
		$('#rapid_pay_sender_phone').on('input', function () {
			var value = $(this).val().replace(/\D/g, '');
			$(this).val(value);
		});

		// Validate phone number on blur
		$('#rapid_pay_sender_phone').on('blur', function () {
			var value = $(this).val();
			var pattern = /^01[0-9]{9}$/;

			if (value && !pattern.test(value)) {
				$(this).addClass('input-error');
				showFieldError($(this), 'Please enter a valid mobile number (e.g., 01712345678)');
			} else {
				$(this).removeClass('input-error');
				hideFieldError($(this));
			}
		});

		// Validate transaction ID
		$('#rapid_pay_transaction_id').on('blur', function () {
			var value = $(this).val();

			if (value && value.length < 5) {
				$(this).addClass('input-error');
				showFieldError($(this), 'Transaction ID must be at least 5 characters long');
			} else {
				$(this).removeClass('input-error');
				hideFieldError($(this));
			}
		});

		// Handle payment method change for dynamic instructions
		$('#rapid_pay_method').on('change', function () {
			var select = $(this);
			var selectedOption = select.find('option:selected');
			var method = selectedOption.val();
			var phone = selectedOption.data('phone');
			var instructionBox = $('#rapid-pay-dynamic-instructions');

			if (method) {
				select.removeClass('input-error');
				hideFieldError(select);

				var methodName = selectedOption.text();
				var instructionHtml = '<div class="rapid-pay-dynamic-instruction">' +
					'<strong>Payment Instruction for ' + methodName + '</strong>' +
					'<p class="payment-info">Please send the total amount to the ' + methodName + ' number: <span>' + (phone ? phone : 'N/A') + '</span> and submit the Transaction ID (TrxID) below.</p>' +
					'</div>';
				
				// Clear and insert the dynamic instruction, then show the container
				instructionBox.html(instructionHtml).slideDown(200);
			} else {
				// If no method is selected, hide the instruction box
				instructionBox.slideUp(200).html('');
			}
		}).trigger('change'); // Trigger on load to show initial state

		// Form validation before submit
		$('form.checkout').on('checkout_place_order_rapid_pay', function () {
			var isValid = true;

			// Check payment method
			var method = $('#rapid_pay_method').val();
			if (!method) {
				$('#rapid_pay_method').addClass('input-error');
				showFieldError($('#rapid_pay_method'), 'Please select a payment method');
				isValid = false;
			}

			// Check sender phone
			var phone = $('#rapid_pay_sender_phone').val();
			var phonePattern = /^01[0-9]{9}$/;
			if (!phone || !phonePattern.test(phone)) {
				$('#rapid_pay_sender_phone').addClass('input-error');
				showFieldError($('#rapid_pay_sender_phone'), 'Please enter a valid mobile number');
				isValid = false;
			}

			// Check transaction ID
			var trxId = $('#rapid_pay_transaction_id').val();
			if (!trxId || trxId.length < 5) {
				$('#rapid_pay_transaction_id').addClass('input-error');
				showFieldError($('#rapid_pay_transaction_id'), 'Please enter a valid transaction ID');
				isValid = false;
			}

			return isValid;
		});
	});

	/**
	 * Show field error message
	 */
	function showFieldError(field, message) {
		hideFieldError(field);

		var errorHtml = '<span class="rapid-pay-field-error" style="color: #dc3545; font-size: 12px; display: block; margin-top: 5px;">' + message + '</span>';
		field.after(errorHtml);
	}

	/**
	 * Hide field error message
	 */
	function hideFieldError(field) {
		field.next('.rapid-pay-field-error').remove();
	}

})(jQuery);
