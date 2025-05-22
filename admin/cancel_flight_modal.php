<?php
// Include currency helper if not already included
if (!function_exists('getCurrencySymbol')) {
    require_once '../includes/currency_helper.php';
}
// Get currency symbol
$currency_symbol = getCurrencySymbol($conn);
?>

<!-- Cancel Modal -->
<div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="refundModalLabel">Process Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="refundForm" action="booking_actions.php" method="post">
                    <input type="hidden" name="action" value="process_refund">
                    <input type="hidden" id="refund_booking_id" name="booking_id" value="">
                    <div class="mb-3">
                        <label for="refund_type" class="form-label">Refund Type</label>
                        <select class="form-select" id="refund_type" name="refund_type" required>
                            <option value="full">Full Refund</option>
                            <option value="partial">Partial Refund</option>
                        </select>
                    </div>
                    <div class="mb-3" id="partial_amount_group">
                        <label for="partial_amount" class="form-label">Refund Amount</label>
                        <div class="input-group">
                            <span class="input-group-text"><?php echo $currency_symbol; ?></span>
                            <input type="number" step="0.01" class="form-control" id="partial_amount" name="amount" value="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="refund_reason" class="form-label">Refund Reason</label>
                        <textarea class="form-control" id="refund_reason" name="reason" rows="3" required></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="refund_notify_customer" name="notify_customer" value="1" checked>
                        <label class="form-check-label" for="refund_notify_customer">
                            Notify customer via email
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('refundForm').submit()">Process Refund</button>
            </div>
        </div>
    </div>
</div>
