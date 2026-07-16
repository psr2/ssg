# Stock Adjustment Module: Edge Cases & Validation Rules

This document outlines all technical and operational edge cases identified for the **Stock Adjustment** module under the centralized immutable ledger architecture. These scenarios serve as the guidelines for our business operations and the acceptance criteria for our integration tests.

---

## 1. Safety Floor Enforcements (Negative Stock Protection)

The system enforces a hard safety floor constraint on negative adjustments to prevent recording quantities below physical reality.

### A. Core Rule
You cannot adjust a batch's stock below zero or below already-committed/sold amounts.
$$\text{Available Stock} = \text{Total Ledger Inflow} - \text{Total Ledger Outflow}$$
If the adjustment amount is negative (deduction), the absolute adjustment quantity must not exceed the current available stock for that specific batch at the selected location:
$$|\text{Adjustment Qty}| \le \text{Available Stock}$$
The resulting quantity (`new_qty`) must always satisfy:
$$\text{new\_qty} \ge 0.00$$

### B. Edge Cases & Handling
* **Deduction when Available Qty is Zero**:
  * *Scenario*: A batch has `0.00` available quantity at a location. An operator attempts to record a deduction (e.g., `-5.00 kg`).
  * *Handling*: Blocked immediately by the UI and the backend validator. Throws a `ValidationException` (Safety Floor Violation).
* **Deduction Exceeding Available Qty**:
  * *Scenario*: A batch has `10.00 kg` available. An operator attempts to record a deduction of `-12.00 kg` (setting `new_qty` to `-2.00 kg`).
  * *Handling*: Blocked. Both the UI and backend service throw a Safety Floor Violation.

---

## 2. Deviation Threshold Rules & Approval Workflows

To prevent human error or unauthorized large-scale inventory modifications, any adjustment that exceeds standard tolerance limits is routed to a `pending_approval` state.

### A. Threshold Limits
An adjustment triggers the manager approval workflow if it violates **either** of the following conditions:
1. **Deviation Percentage**: The absolute adjustment quantity exceeds **5%** of the currently held quantity at that location:
   $$\text{deviation} = \frac{|\text{Adjusted Qty}|}{\text{Available Qty}} \times 100 > 5\%$$
2. **Absolute Unit Limit**: The absolute adjustment quantity is greater than **100 units**:
   $$|\text{Adjusted Qty}| > 100$$

### B. Edge Cases & Handling
* **Positive Adjustment Starting from Zero**:
  * *Scenario*: A batch has `0.00` available stock, and the operator enters a new quantity of `5.00 kg` (e.g., stock discovered during audit).
  * *Handling*: The percentage deviation is mathematically calculated as $100\%$ (since the starting base was 0). This exceeds the $5\%$ threshold and is saved as `pending_approval`. It is written to the ledger only after a manager approves it.
* **Large Batches (Absolute Limit)**:
  * *Scenario*: A warehouse holds `5000.00 kg` of a product. The operator adjusts it by adding `150.00 kg`.
  * *Handling*: The deviation percentage is $\frac{150}{5000} \times 100 = 3\%$, which is within the $5\%$ limit. However, the absolute delta (`150.00 kg`) exceeds the `100 units` limit. The adjustment is flagged and queued as `pending_approval`.
* **Standard Small Adjustments**:
  * *Scenario*: A warehouse holds `100.00 kg` of a product. The operator adjusts it to `98.50 kg` (a `-1.50 kg` delta).
  * *Handling*: The deviation is $1.5\%$ (within $5\%$) and the absolute delta is $1.5$ (within $100$). The adjustment is marked as `approved` instantly and posted directly to the stock ledger.

---

## 3. Downstream Activity & Remediation Workflows

Under the append-only ledger system, historical records are immutable. Direct editing or deletion of transactions is blocked.

### A. Wrongful Transfer Remediation
* *Scenario*: $50\text{ kg}$ is wrongfully transferred from Location A to Location B. Location B then sells $20\text{ kg}$ of that stock. The operator attempts to cancel or adjust the transfer.
* *Handling*: The operator cannot void/reverse the $50\text{ kg}$ transfer because Location B only has $30\text{ kg}$ of that batch left. Attempting to reverse the transfer would drop Location B's stock to $-20\text{ kg}$ (Safety Floor Violation).
* *Correction Path*: 
  1. Void/Return the $20\text{ kg}$ sale at Location B first (restoring Location B's stock to $50\text{ kg}$).
  2. Reverse/Void the transfer.
  3. Re-record the correct entries.

### B. Wrongful Purchase Entry Remediation
* *Scenario*: A purchase of $100\text{ kg}$ was recorded, but only $90\text{ kg}$ was physically delivered (a $10\text{ kg}$ error). We have already sold $95\text{ kg}$ of that batch.
* *Handling*: We want to adjust the batch down by $-10\text{ kg}$ to reflect the physical error. However, available stock is only $5\text{ kg}$ ($100 - 95$). The system blocks the $-10\text{ kg}$ adjustment (Safety Floor Violation).
* *Correction Path*: 
  1. Void/Return some of the sales to free up at least $10\text{ kg}$ in the batch.
  2. Record the stock adjustment of $-10\text{ kg}$.
  3. Re-record the sales.

---

## 4. Location-Scoped Baselines

All calculations for both **Safety Floors** and **Deviation Thresholds** are scoped strictly to the selected location, rather than the global batch quantity.
* If Batch X exists at Warehouse A ($20\text{ kg}$) and Warehouse B ($80\text{ kg}$), an adjustment at Warehouse A is validated against the $20\text{ kg}$ baseline, not the global $100\text{ kg}$ total.