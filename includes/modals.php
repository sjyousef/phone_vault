<!-- Add/Edit Phone Modal -->
<div class="modal fade" id="phoneModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content pv-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="phoneModalTitle"><i class="fa-solid fa-mobile-screen me-2"></i>Add Phone</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="phoneForm" method="POST" action="/Second_Hand_Phone_Store/inventory.php">
                <div class="modal-body">
                    <input type="hidden" name="action" id="phoneAction" value="add">
                    <input type="hidden" name="phone_id" id="phoneId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Brand</label>
                            <input type="text" class="form-control pv-input" name="brand" id="phoneBrand" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Model</label>
                            <input type="text" class="form-control pv-input" name="model" id="phoneModel" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IMEI</label>
                            <input type="text" class="form-control pv-input" name="imei" id="phoneImei" maxlength="20" required>
                            <div class="form-text" id="imeiValidation"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Storage</label>
                            <input type="text" class="form-control pv-input" name="storage" id="phoneStorage" placeholder="128GB">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Color</label>
                            <input type="text" class="form-control pv-input" name="color" id="phoneColor">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Battery Health %</label>
                            <input type="number" class="form-control pv-input" name="battery_health" id="phoneBattery" min="1" max="100" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Condition Grade</label>
                            <select class="form-select pv-input" name="condition_grade" id="phoneGrade" required>
                                <option value="Grade A">Grade A</option>
                                <option value="Grade B">Grade B</option>
                                <option value="Grade C">Grade C</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select pv-input" name="status" id="phoneStatus">
                                <option value="Available">Available</option>
                                <option value="Sold">Sold</option>
                                <option value="Returned">Returned</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cost Price (₱)</label>
                            <input type="number" step="0.01" class="form-control pv-input" name="cost_price" id="phoneCost" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Selling Price (₱)</label>
                            <input type="number" step="0.01" class="form-control pv-input" name="selling_price" id="phonePrice" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn pv-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn pv-btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Save Phone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Process Refund Modal -->
<div class="modal fade" id="refundModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content pv-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-rotate-left me-2"></i>Process Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="refundForm" method="POST" action="/Second_Hand_Phone_Store/returns.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="process_refund">
                    <input type="hidden" name="refund_id" id="refundId">
                    <div class="mb-3">
                        <label class="form-label">Update Status</label>
                        <select class="form-select pv-input" name="status" required>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Refund Amount (₱)</label>
                        <input type="number" step="0.01" class="form-control pv-input" name="refund_amount" id="refundAmount">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn pv-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn pv-btn-primary">Update Claim</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Claim Warranty Modal -->
<div class="modal fade" id="warrantyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content pv-modal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-shield-halved me-2"></i>Warranty Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="warrantyModalBody">
                <div class="text-center py-4">
                    <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn pv-btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
