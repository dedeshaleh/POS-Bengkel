@csrf
<div class="form-grid">
    <label>Company Name <input name="company_name" value="{{ old('company_name', $supplier->company_name ?? '') }}" required></label>
    <label>Contact Person <input name="contact_person" value="{{ old('contact_person', $supplier->contact_person ?? '') }}"></label>
    <label>Phone <input name="phone" value="{{ old('phone', $supplier->phone ?? '') }}"></label>
    <label>Email <input type="email" name="email" value="{{ old('email', $supplier->email ?? '') }}"></label>
    <label>NPWP <input name="tax_id_npwp" value="{{ old('tax_id_npwp', $supplier->tax_id_npwp ?? '') }}"></label>
    <label>Entity Type
        <select name="entity_type" required>
            <option value="corporate" {{ old('entity_type', $supplier->entity_type ?? 'corporate') === 'corporate' ? 'selected' : '' }}>Corporate / PT / CV</option>
            <option value="individual" {{ old('entity_type', $supplier->entity_type ?? '') === 'individual' ? 'selected' : '' }}>Individual / Orang Pribadi</option>
        </select>
    </label>
    <label>PPh 21 % <input type="number" step="0.01" min="0" max="100" name="pph21_percentage" value="{{ old('pph21_percentage', $supplier->pph21_percentage ?? 5) }}"></label>
    <label>PPN %
        <input type="number" step="0.01" min="0" max="100" name="ppn_percentage" value="{{ old('ppn_percentage', $supplier->ppn_percentage ?? 0) }}">
    </label>
    <label class="full"><span><input type="checkbox" name="is_ppn_enabled" value="1" style="width:auto" {{ old('is_ppn_enabled', $supplier->is_ppn_enabled ?? false) ? 'checked' : '' }}> Supplier uses PPN</span></label>
    <label>Bank Name <input name="bank_name" value="{{ old('bank_name', $supplier->bank_name ?? '') }}"></label>
    <label>Account Name <input name="bank_account_name" value="{{ old('bank_account_name', $supplier->bank_account_name ?? '') }}"></label>
    <label>Account Number <input name="bank_account_number" value="{{ old('bank_account_number', $supplier->bank_account_number ?? '') }}"></label>
    <label>Cabang Bank <input name="bank_branch" value="{{ old('bank_branch', $supplier->bank_branch ?? '') }}"></label>
    <label class="full">Bank Account Note <textarea name="bank_account_info">{{ old('bank_account_info', $supplier->bank_account_info ?? '') }}</textarea></label>
    <label class="full">Address <textarea name="address">{{ old('address', $supplier->address ?? '') }}</textarea></label>
</div>
