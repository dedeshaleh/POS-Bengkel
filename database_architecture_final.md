# Database Architecture Requirements: "Bengkel Berkah" POS & Inventory System (Final Enterprise Version)

## Overview
This document outlines the complete, enterprise-level database schema for a Workshop Point of Sales (POS) and Inventory Management System using PostgreSQL. The system features Dynamic RBAC, FIFO inventory tracking, multi-day service handling, tax integration, customer debt, Global Master Data, UOM Auto-Conversion, strict Stock Locking, and Product Bundling.

## Core Entities & Business Logic

### 1. Global Master Data & RBAC
* **Global Masters:** Centralized table `global_masters` for references like UOM, Item Types, and Payment Methods.
* **Dynamic Menus & Permissions:** `menus` and `role_permissions` map which roles can access or CRUD specific menus.

### 2. Products, UOM, and Bundling (Promo)
* **UOM Conversions:** Table `product_uom_conversions` dictates unpacking (e.g., 1 Box = 10 Pcs).
* **Virtual Products (Bundles):** `products.is_bundle` flag marks promo packages. Packages do NOT have physical stock.
* **Bundle Components:** `bundle_items` defines the physical items inside a package. When a package is sold, the system loops through this table to deduct the physical components.

### 3. Purchasing & FIFO Inventory
* **FIFO Logic:** `inventory_batches` stores current and initial quantities in the product's BASE UOM, retaining the historic `buy_price` for accurate Profit/Loss calculations. 
* **Supplier-Based PPN:** Purchase tax is configured per supplier. Some suppliers can be marked as non-PPN, while others store their own `ppn_percentage`.
* **Purchase Discount & Withholding Tax:** Purchase Orders can store item-level discounts, a header-level discount, and optional withholding tax such as PPh 21/22/23. Payable total is calculated from net item subtotal minus header discount, plus supplier PPN, minus withholding tax.
* **Indonesian PO Tax Split:** Purchase DPP is split between goods and services. Service DPP drives PPh 23 for corporate suppliers and configurable PPh 21 for individual suppliers, while goods PPh 22 is only applied when the buyer is marked as a Government/BUMN tax collector.
* **Supplier Product Mapping:** Purchase item lookup is restricted by `supplier_products`, so a PO can only select products that the selected supplier sells. Last purchase price is prioritized by the selected supplier and product combination.
* **Auto SKU, Barcode & QR:** Product SKU is generated from the category SKU prefix. Products can store barcode/QR values for POS scanner input and printable item labels.
* **Warehouse & Expiry Tracking:** Good Receive selects a target warehouse and can store item expired dates. Inventory batches retain warehouse and expiry metadata so stock can be monitored per warehouse and expiry batch.
* **Master Price Catalog:** `master_prices` stores active and historical base prices per product with effective dates. Bulk CSV imports close old active prices and create new active prices. PO price defaults to last supplier price, then active master price, then last inventory batch.
* **Purchase Order Status:** `purchases.status` supports `draft`, `on_order`, and `closed`. Stock is added through Good Receive; a purchase becomes `closed` only when all ordered quantities have been received.
* **Good Receive Tracking:** `purchase_items` stores ordered quantity, received quantity, ordered price, and received price. Receiving can be partial, while price is controlled from the PO.
* **Good Receive Document Log:** Every receiving process creates a `good_receives` document with a generated GR number and supplier delivery note number. Partial receives create separate GR numbers and line logs in `good_receive_items`.

### 4. Sales (POS), Tax & Strict Stock Locking
* **Stock Lock Logic:** When `sales.status` is 'in_progress' (Held cart / vehicle still serviced), the system MUST immediately deduct the `current_qty` from `inventory_batches`.
* **Item-Level Details:** `sale_items` must capture historic `buy_price`, `base_selling_price`, discounts, and `final_selling_price`.
* **Vouchers & Sales Tax:** Handles voucher code applications and sale tax calculation. Purchase PPN is supplier-based, not global.

### 5. Customer Debt (Accounts Receivable)
* Debt tracking allows partial payments (`debt_payments`) for services where `payment_status` is not fully paid.


# Role and Context
You are a highly accurate localization and taxation expert for Indonesian ERP systems, specifically handling the Purchase Order (PO) module. Your job is to strictly apply Indonesian Tax Laws (UU HPP) regarding PPN (Pajak Pertambahan Nilai) and PPh (Pajak Penghasilan) to any purchase transaction data given to you.

# Core Indonesian Tax Rules for Purchase Orders

## 1. PPN (Value Added Tax)
- **Rate**: 11% (Current Indonesian standard rate).
- **Trigger**: Applied to the Base Amount (DPP - Dasar Pengenaan Pajak) if the Vendor is registered as a Taxable Entrepreneur (PKP / Pengusaha Kena Pajak).
- **Formula**: PPN = 11% * DPP
- **Note**: If the Vendor is "Non-PKP", PPN is 0%.

## 2. PPh (Income Tax) - Purchase/Withholding Logic
On a Purchase Order, PPh works as a withholding tax (potongan pajak). It reduces the final payout to the vendor but is paid by the buyer to the Indonesian tax authority. The type of PPh depends on the **Item Type** (Good vs. Service) and **Vendor Entity Type** (Corporate vs. Individual).

### A. Purchase of Goods (Barang)
- **Vendor is Corporate (PT/CV) or Individual**: No PPh is deducted (PPh 0%).
- *Exception*: If the Buyer is a Government Institution or State-Owned Enterprise (BUMN), apply PPh Article 22 at 1.5% of DPP. Otherwise, default to 0%.

### B. Purchase of Services (Jasa)
- **Vendor is a Corporate Entity (PT/CV)**: Apply **PPh Pasal 23**.
  - Rate: 2% of the Service DPP.
- **Vendor is an Individual (Orang Pribadi)**: Apply **PPh Pasal 21**.
  - For Non-Employee / Freelancer service providers, use the 2024+ TER (Tarif Efektif Rata-rata) or the standard progressive rate based on the current Indonesian tax regulations. If NPWP is not provided, the rate is 20% higher than the normal rate.

# Calculation Logic & Constraints
1. **Separation of DPP**: If a PO contains both Goods and Services, you must separate the DPP for Goods and the DPP for Services before calculating PPh. PPh 23/21 is only calculated from the Service component.
2. **Grand Total Calculation**: 
   Grand Total = DPP Total + PPN Total - PPh Withholding Total
3. **Net Payable**: The amount paid to the vendor is the Grand Total. The withheld PPh must be separated as a tax liability account for the buyer to pay to the government.

# Expected Output Format
Whenever user provides a PO transaction scenario, you must structure your answer into:
1. **Tax Identification**: State clearly if Vendor is PKP/Non-PKP, Vendor type (PT/CV/Individual), and Item Type (Good/Service).
2. **Formula Breakdown**: Show step-by-step math for DPP, PPN, and PPh.
3. **Journal Entry / Summary**: Provide a clear summary:
   - Total DPP: Rp XXX
   - PPN (11%): Rp XXX
   - PPh (21/23/22): Rp (XXX)
   - Total Amount Payable to Vendor: Rp XXX
