/**
 * Rapid Pay Admin Scripts
 */

(function ($) {
	'use strict';

	$(document).ready(function () {
		// Initialize chart
		initChart();

		// Handle status change
		$('.rapid-pay-status-select').on('change', function () {
			var select = $(this);
			var orderId = select.data('order-id');
			var newStatus = select.val();

			if (!newStatus) {
				return;
			}

			if (!confirm('Are you sure you want to change the order status?')) {
				select.val('');
				return;
			}

			updateOrderStatus(orderId, newStatus, select);
		});
	});

	/**
	 * Initialize income chart
	 */
	function initChart() {
		var canvas = document.getElementById('rapid-pay-chart');
		if (!canvas || typeof rapidPayChartData === 'undefined') {
			return;
		}

		var ctx = canvas.getContext('2d');

		// Create gradient
		var gradient = ctx.createLinearGradient(0, 0, 0, 300);
		gradient.addColorStop(0, 'rgba(34, 113, 177, 0.3)');
		gradient.addColorStop(1, 'rgba(34, 113, 177, 0.05)');

		// Simple chart implementation (no external dependencies)
		drawChart(ctx, rapidPayChartData, gradient);
	}

	/**
	 * Draw chart on canvas
	 */
	function drawChart(ctx, data, gradient) {
		var canvas = ctx.canvas;
		var width = canvas.width;
		var height = canvas.height;
		var padding = 40;
		var chartWidth = width - padding * 2;
		var chartHeight = height - padding * 2;

		// Clear canvas
		ctx.clearRect(0, 0, width, height);

		// Get max value and round up to the nearest significant figure
		var maxValue = Math.max.apply(null, data.data);
		if (maxValue === 0) {
			maxValue = 100;
		} else {
			// Round up to the nearest 100, 500, 1000, etc. for cleaner Y-axis
			var power = Math.floor(Math.log10(maxValue));
			var factor = Math.pow(10, power);
			maxValue = Math.ceil(maxValue / factor) * factor;
		}

		// Calculate points
		var points = [];
		var stepX = chartWidth / (data.labels.length - 1);

		for (var i = 0; i < data.data.length; i++) {
			var x = padding + i * stepX;
			var y = padding + chartHeight - (data.data[i] / maxValue) * chartHeight;
			points.push({ x: x, y: y });
		}

		// Draw Y-axis labels and grid lines
		ctx.strokeStyle = '#e0e0e0';
		ctx.fillStyle = '#666';
		ctx.lineWidth = 1;
		ctx.textAlign = 'right';
		ctx.font = '10px Arial';
		var numLabels = 5;
		for (var j = 0; j <= numLabels; j++) {
			var gridY = padding + (chartHeight / numLabels) * j;
			var value = maxValue - (maxValue / numLabels) * j;

			// Draw grid line
			ctx.beginPath();
			ctx.moveTo(padding, gridY);
			ctx.lineTo(width - padding, gridY);
			ctx.stroke();

			// Draw Y-axis label
			if (j < numLabels) {
				ctx.fillText(value.toFixed(0), padding - 5, gridY + 3);
			}
		}

		// Draw area
		ctx.fillStyle = gradient;
		ctx.beginPath();
		ctx.moveTo(points[0].x, chartHeight + padding);
		for (var k = 0; k < points.length; k++) {
			ctx.lineTo(points[k].x, points[k].y);
		}
		ctx.lineTo(points[points.length - 1].x, chartHeight + padding);
		ctx.closePath();
		ctx.fill();

		// Draw line
		ctx.strokeStyle = '#2271b1';
		ctx.lineWidth = 2;
		ctx.beginPath();
		ctx.moveTo(points[0].x, points[0].y);
		for (var l = 1; l < points.length; l++) {
			ctx.lineTo(points[l].x, points[l].y);
		}
		ctx.stroke();

		// Draw points
		ctx.fillStyle = '#2271b1';
		for (var m = 0; m < points.length; m++) {
			ctx.beginPath();
			ctx.arc(points[m].x, points[m].y, 4, 0, 2 * Math.PI);
			ctx.fill();
		}

		// Draw X-axis labels
		ctx.fillStyle = '#666';
		ctx.font = '11px Arial';
		ctx.textAlign = 'center';
		var labelStep = Math.ceil(data.labels.length / 10);
		for (var n = 0; n < data.labels.length; n++) {
			if (n % labelStep === 0) {
				ctx.fillText(data.labels[n], points[n].x, height - 10);
			}
		}
	}

	/**
	 * Update order status via AJAX
	 */
	function updateOrderStatus(orderId, newStatus, selectElement) {
		var row = selectElement.closest('tr');
		var statusCell = row.find('.rapid-pay-status');

		// Show loading
		selectElement.prop('disabled', true);
		statusCell.html('<span class="rapid-pay-loading"></span>');

		$.ajax({
			url: rapidPayAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'rapid_pay_update_order_status',
				nonce: rapidPayAdmin.nonce,
				order_id: orderId,
				status: newStatus
			},
			success: function (response) {
				if (response.success) {
					// Update status display
					var statusText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
					statusText = statusText.replace('-', ' ');
					statusCell.html('<span class="rapid-pay-status rapid-pay-status-' + newStatus + '">' + statusText + '</span>');

					// Show success message
					showNotice('success', response.data.message);

					// Reload page after 1 second
					setTimeout(function () {
						location.reload();
					}, 1000);
				} else {
					showNotice('error', response.data.message);
					statusCell.html('<span class="rapid-pay-status">Error</span>');
				}
			},
			error: function () {
				showNotice('error', 'An error occurred. Please try again.');
				statusCell.html('<span class="rapid-pay-status">Error</span>');
			},
			complete: function () {
				selectElement.prop('disabled', false);
				selectElement.val('');
			}
		});
	}

	/**
	 * Show admin notice
	 */
	function showNotice(type, message) {
		var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
		var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

		$('.rapid-pay-dashboard h1').after(notice);

		// Auto dismiss after 3 seconds
		setTimeout(function () {
			notice.fadeOut(function () {
				$(this).remove();
			});
		}, 3000);
	}

})(jQuery);
